<?php
namespace App\Http\Controllers;

use App\Models\{ArbitrageT3, ConfirmationTache, Evenement, MaintenancePreventive, Panne, Penalite, Projet, RecetteVehicule, Sprint, Tache, JourFerie, JourSaisie, User, Vehicule};
use App\Services\AlerteParcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();

        // Direction générale → dashboard stratégique
        if ($user->aRole('direction')) return $this->dashboardDG($user);

        // Pilotes opérationnels → dashboard pilote
        if ($user->estPilote()) return $this->dashboardPilote($user);

        // Collaborateur (enrichi si responsable projet)
        return $this->dashboardCollab($user);
    }

    /* ─── Dashboard collaborateur (US-16) — enrichi si responsable projet ─── */
    private function dashboardCollab(User $user)
    {
        $userId = $user->id;
        $tachesDuJour = Tache::with('projet')
            ->where('user_id', $userId)
            ->whereIn('statut', ['en_cours', 'a_faire'])
            ->whereDate('date_debut', '<=', today())
            ->orderByRaw("FIELD(priorite,'haute','moyenne','basse')")
            ->get();

        $total = Tache::where('user_id', $userId)->count();
        $terminees = Tache::where('user_id', $userId)->where('statut', 'termine')->count();
        $enCours = Tache::where('user_id', $userId)->where('statut', 'en_cours')->count();
        $bloquees = Tache::where('user_id', $userId)->where('statut', 'bloque')->count();
        $enRetard = Tache::where('user_id', $userId)->whereNull('date_fin_reelle')
            ->whereNotNull('echeance')->whereDate('echeance', '<', today())->count();
        $tauxCompletion = $total > 0 ? round(($terminees / $total) * 100) : 0;

        $alertes = [];
        if ($enRetard > 0) $alertes[] = ['type' => 'warn', 'msg' => "{$enRetard} tâche(s) en retard."];
        if ($bloquees > 0) $alertes[] = ['type' => 'ko', 'msg' => "{$bloquees} tâche(s) bloquée(s)."];

        $aSaisiAujourdhui = Tache::where('user_id', $userId)->whereDate('date_debut', today())->exists();
        $estJourOuvre = !today()->isWeekend() && !JourFerie::whereDate('date', today())->exists();
        if (!$aSaisiAujourdhui && $estJourOuvre) {
            $alertes[] = ['type' => 'info', 'msg' => 'Aucune tâche saisie aujourd\'hui.'];
        }

        $termineesSemaine = Tache::where('user_id', $userId)->where('statut', 'termine')
            ->whereBetween('date_fin_reelle', [today()->startOfWeek(), today()->endOfWeek()])->count();

        $assiduite = $this->calculerAssiduite($userId);

        // Points livrés (tâches sprint terminées)
        $pointsLivres = (int) Tache::where('user_id', $userId)->where('statut', 'termine')
            ->whereNotNull('sprint_id')->sum('points');
        $pointsTotalSprint = (int) Tache::where('user_id', $userId)->whereNotNull('sprint_id')->sum('points');

        // Tendance hebdo (4 dernières semaines)
        $tendanceHebdo = [];
        for ($w = 3; $w >= 0; $w--) {
            $debut = today()->copy()->subWeeks($w)->startOfWeek();
            $fin = $debut->copy()->endOfWeek();
            $tendanceHebdo[] = [
                'label' => 'S' . $debut->weekOfYear,
                'count' => Tache::where('user_id', $userId)->where('statut', 'termine')
                    ->whereBetween('date_fin_reelle', [$debut, $fin])->count(),
            ];
        }
        $tendanceMax = max(1, max(array_column($tendanceHebdo, 'count')));

        // Répartition par priorité
        $parPriorite = [
            'haute' => Tache::where('user_id', $userId)->where('priorite', 'haute')->count(),
            'moyenne' => Tache::where('user_id', $userId)->where('priorite', 'moyenne')->count(),
            'basse' => Tache::where('user_id', $userId)->where('priorite', 'basse')->count(),
        ];
        $totalPrio = max(1, array_sum($parPriorite));

        // Toutes mes tâches actives
        $mesTachesActives = Tache::with('projet')
            ->where('user_id', $userId)
            ->whereIn('statut', ['a_valider', 'a_faire', 'en_cours', 'bloque'])
            ->orderByRaw("FIELD(statut,'a_valider','bloque','en_cours','a_faire')")
            ->orderByRaw("FIELD(priorite,'haute','moyenne','basse')")
            ->orderBy('echeance')
            ->get();

        // --- Section responsable projet (dynamique) ---
        $estResponsable = $user->projetsResponsable()->exists();
        $projetData = null;

        if ($estResponsable) {
            $projetIds = Projet::where('responsable_projet_id', $userId)->pluck('id');
            $mesProjets = Projet::whereIn('id', $projetIds)->withCount([
                'taches',
                'taches as terminees_count' => fn ($q) => $q->where('statut', 'termine'),
                'taches as en_retard_count' => fn ($q) => $q->whereNull('date_fin_reelle')->whereNotNull('echeance')->whereDate('echeance', '<', today()),
                'taches as bloquees_count' => fn ($q) => $q->where('statut', 'bloque'),
            ])->get();

            $sprintsEnCours = Sprint::whereIn('projet_id', $projetIds)->where('statut', 'en_cours')
                ->with('projet')->get()->map(function ($s) {
                    $total = Tache::where('sprint_id', $s->id)->count();
                    $term = Tache::where('sprint_id', $s->id)->where('statut', 'termine')->count();
                    $s->stats_total = $total;
                    $s->stats_terminees = $term;
                    $s->progression = $total > 0 ? round(($term / $total) * 100) : 0;
                    $s->jours_restants = $s->date_fin->gte(today()) ? (int) today()->diffInDays($s->date_fin) : 0;
                    return $s;
                });

            $membresIds = Tache::whereIn('projet_id', $projetIds)->distinct()->pluck('user_id');
            $membres = User::whereIn('id', $membresIds)->get()->map(function ($m) use ($projetIds) {
                $m->taches_en_cours = Tache::where('user_id', $m->id)->whereIn('projet_id', $projetIds)->where('statut', 'en_cours')->count();
                $m->taches_bloquees = Tache::where('user_id', $m->id)->whereIn('projet_id', $projetIds)->where('statut', 'bloque')->count();
                $m->taches_terminees = Tache::where('user_id', $m->id)->whereIn('projet_id', $projetIds)->where('statut', 'termine')->count();
                $m->taches_total = Tache::where('user_id', $m->id)->whereIn('projet_id', $projetIds)->count();
                $m->taux = $m->taches_total > 0 ? round(($m->taches_terminees / $m->taches_total) * 100) : 0;
                return $m;
            });

            $topBloquees = Tache::with(['user', 'projet'])->whereIn('projet_id', $projetIds)
                ->where('statut', 'bloque')->orderByDesc('updated_at')->limit(5)->get();

            $projetData = compact('mesProjets', 'sprintsEnCours', 'membres', 'topBloquees');
        }

        return view('dashboard.index', compact(
            'tachesDuJour', 'total', 'terminees', 'enCours', 'bloquees',
            'enRetard', 'tauxCompletion', 'alertes', 'termineesSemaine', 'assiduite',
            'estResponsable', 'projetData', 'mesTachesActives',
            'pointsLivres', 'pointsTotalSprint', 'tendanceHebdo', 'tendanceMax',
            'parPriorite', 'totalPrio'
        ));
    }

    /* ─── Dashboard DG (US-17) ─── */
    private function dashboardDG(User $user)
    {
        AlerteParcService::verifier();
        $totalTaches = Tache::count();
        $terminees = Tache::where('statut', 'termine')->count();
        $enCours = Tache::where('statut', 'en_cours')->count();
        $bloquees = Tache::where('statut', 'bloque')->count();
        $enRetard = Tache::whereNull('date_fin_reelle')->whereNotNull('echeance')
            ->whereDate('echeance', '<', today())->count();
        $tauxGlobal = $totalTaches > 0 ? round(($terminees / $totalTaches) * 100) : 0;

        $arbitragesEnAttente = ArbitrageT3::where('decision', 'en_attente')->count();

        $hier = today()->subDay();
        $sanseSaisieHier = 0;
        if (!$hier->isWeekend() && !JourFerie::whereDate('date', $hier)->exists()) {
            $totalActifs = User::where('actif', true)->whereNotIn('role', ['direction', 'invite'])->count();
            $idsCouverts = JourSaisie::whereDate('date', $hier)
                ->whereIn('statut', ['saisi', 'justifie'])->pluck('user_id')->map(fn($v) => (int)$v)->toArray();
            if (Schema::hasTable('confirmation_taches')) {
                $idsCouverts = array_merge($idsCouverts, ConfirmationTache::whereDate('date', $hier)->pluck('user_id')->map(fn($v) => (int)$v)->toArray());
            }
            if (Schema::hasTable('absences')) {
                $idsCouverts = array_merge($idsCouverts, \App\Models\Absence::whereDate('date_debut', '<=', $hier)->whereDate('date_fin', '>=', $hier)->pluck('user_id')->map(fn($v) => (int)$v)->toArray());
            }
            if (Schema::hasTable('taches')) {
                $idsCouverts = array_merge($idsCouverts, Tache::where('statut', 'termine')->whereDate('date_fin_reelle', $hier)->pluck('user_id')->map(fn($v) => (int)$v)->toArray());
            }
            $sanseSaisieHier = max(0, $totalActifs - count(array_unique($idsCouverts)));
        }

        $termineesSemaine = Tache::where('statut', 'termine')
            ->whereBetween('date_fin_reelle', [today()->startOfWeek(), today()->endOfWeek()])->count();

        $sprintsEnCours = Sprint::where('statut', 'en_cours')->with('projet')->get()->map(function ($s) {
            $total = Tache::where('sprint_id', $s->id)->count();
            $term = Tache::where('sprint_id', $s->id)->where('statut', 'termine')->count();
            $s->stats_total = $total;
            $s->stats_terminees = $term;
            $s->progression = $total > 0 ? round(($term / $total) * 100) : 0;
            $s->jours_restants = $s->date_fin->gte(today()) ? (int) today()->diffInDays($s->date_fin) : 0;
            return $s;
        });
        $topBloquees = Tache::with(['user', 'projet'])->where('statut', 'bloque')
            ->orderByDesc('updated_at')->limit(5)->get();
        $parProjet = Projet::whereHas('taches')->withCount([
            'taches',
            'taches as terminees_count' => fn ($q) => $q->where('statut', 'termine'),
            'taches as en_retard_count' => fn ($q) => $q->whereNull('date_fin_reelle')->whereNotNull('echeance')->whereDate('echeance', '<', today()),
        ])->get();

        // Tendance hebdo (4 semaines)
        $tendanceHebdo = [];
        for ($w = 3; $w >= 0; $w--) {
            $deb = today()->copy()->subWeeks($w)->startOfWeek();
            $fi = $deb->copy()->endOfWeek();
            $tendanceHebdo[] = [
                'label' => 'S' . $deb->weekOfYear,
                'count' => Tache::where('statut', 'termine')
                    ->whereBetween('date_fin_reelle', [$deb, $fi])->count(),
            ];
        }
        $tendanceMax = max(1, max(array_column($tendanceHebdo, 'count')));

        // Taux livraison sprints terminés
        $sprintsTermines = Sprint::where('statut', 'termine')->with('projet')->get()->map(function ($s) {
            $totalT = Tache::where('sprint_id', $s->id)->count();
            $termT = Tache::where('sprint_id', $s->id)->where('statut', 'termine')->count();
            $s->taux_livraison = $totalT > 0 ? round(($termT / $totalT) * 100) : 0;
            $s->total_taches = $totalT;
            $s->taches_terminees = $termT;
            return $s;
        })->sortByDesc('date_fin')->take(6)->values();

        // Performance collaborateurs
        $collaborateurs = User::where('actif', true)->whereNotIn('role', ['invite', 'direction'])
            ->orderBy('name')->get()->map(function ($c) {
                $c->taches_total = Tache::where('user_id', $c->id)->count();
                $c->taches_terminees = Tache::where('user_id', $c->id)->where('statut', 'termine')->count();
                $c->taches_en_cours = Tache::where('user_id', $c->id)->where('statut', 'en_cours')->count();
                $c->taches_bloquees = Tache::where('user_id', $c->id)->where('statut', 'bloque')->count();
                $c->taux = $c->taches_total > 0 ? round(($c->taches_terminees / $c->taches_total) * 100) : 0;
                return $c;
            });

        $evenements = collect();
        if (Schema::hasTable('evenements')) {
            $evenements = Evenement::with('user')
                ->whereDate('date', '>=', today()->subDays(30))
                ->orderByDesc('date')->orderByDesc('created_at')
                ->limit(15)->get();
        }

        // ─── Parc automobile (résumé DG) ───
        $parc = null;
        if (Schema::hasTable('vehicules')) {
            $moisDebut = today()->startOfMonth();
            $moisFin = today()->endOfMonth();

            $vehiculesTotal = Vehicule::count();
            $vehiculesActifs = Vehicule::where('statut', 'en_service')->count();

            $recetteMois = (int) RecetteVehicule::whereBetween('date', [$moisDebut, $moisFin])->sum('recette');
            $objectifMois = (int) RecetteVehicule::whereBetween('date', [$moisDebut, $moisFin])->sum('objectif');
            $tauxRecette = $objectifMois > 0 ? round(($recetteMois / $objectifMois) * 100) : 0;

            $coutPannesMois = (int) Panne::whereBetween('date_declaration', [$moisDebut, $moisFin])->sum('cout_reel');
            $pannesOuvertes = Panne::whereIn('statut', ['declaree', 'en_diagnostic', 'en_intervention'])->count();

            $montantPenalitesMois = 0;
            $penalitesNonPayees = 0;
            if (Schema::hasTable('penalites')) {
                $montantPenalitesMois = (int) Penalite::whereBetween('date_infraction', [$moisDebut, $moisFin])->sum('montant');
                $penalitesNonPayees = Penalite::where('statut', 'non_payee')->count();
            }

            $maintenancesRetard = MaintenancePreventive::whereNotNull('prochaine_echeance')
                ->whereDate('prochaine_echeance', '<', today())->count();

            // Top 5 véhicules par recette du mois
            $topVehicules = RecetteVehicule::whereBetween('date', [$moisDebut, $moisFin])
                ->select('vehicule_id', \Illuminate\Support\Facades\DB::raw('SUM(recette) as total'), \Illuminate\Support\Facades\DB::raw('SUM(objectif) as obj'))
                ->groupBy('vehicule_id')->orderByDesc('total')->limit(5)->get()
                ->map(function ($r) {
                    $v = Vehicule::find($r->vehicule_id);
                    return [
                        'immat' => $v ? $v->immatriculation : '?',
                        'categorie' => $v ? $v->categorieLabel() : '?',
                        'recette' => (int) $r->total,
                        'objectif' => (int) $r->obj,
                        'taux' => $r->obj > 0 ? round(($r->total / $r->obj) * 100) : 0,
                    ];
                });

            $resultatNet = $recetteMois - $coutPannesMois - $montantPenalitesMois;

            $parc = compact(
                'vehiculesTotal', 'vehiculesActifs',
                'recetteMois', 'objectifMois', 'tauxRecette',
                'coutPannesMois', 'pannesOuvertes',
                'montantPenalitesMois', 'penalitesNonPayees',
                'maintenancesRetard', 'topVehicules', 'resultatNet'
            );
        }

        return view('dashboard.dg', compact(
            'totalTaches', 'terminees', 'enCours', 'bloquees', 'enRetard', 'tauxGlobal',
            'termineesSemaine', 'arbitragesEnAttente', 'sanseSaisieHier',
            'sprintsEnCours', 'topBloquees', 'parProjet', 'evenements',
            'tendanceHebdo', 'tendanceMax', 'sprintsTermines', 'collaborateurs',
            'parc'
        ));
    }

    /* ─── Dashboard pilote opérationnel (charge_operations, administrateur) ─── */
    private function dashboardPilote(User $user)
    {
        AlerteParcService::verifier();
        // KPI globaux
        $totalTaches = Tache::count();
        $terminees = Tache::where('statut', 'termine')->count();
        $enCours = Tache::where('statut', 'en_cours')->count();
        $bloquees = Tache::where('statut', 'bloque')->count();
        $enRetard = Tache::whereNull('date_fin_reelle')->whereNotNull('echeance')
            ->whereDate('echeance', '<', today())->count();
        $tauxGlobal = $totalTaches > 0 ? round(($terminees / $totalTaches) * 100) : 0;

        $termineesSemaine = Tache::where('statut', 'termine')
            ->whereBetween('date_fin_reelle', [today()->startOfWeek(), today()->endOfWeek()])->count();

        // Alertes
        $alertes = [];
        $hier = today()->subDay();
        $sanseSaisieHier = 0;
        if (!$hier->isWeekend() && !JourFerie::whereDate('date', $hier)->exists()) {
            $totalActifs = User::where('actif', true)->whereNotIn('role', ['direction', 'invite'])->count();
            $idsCouverts = JourSaisie::whereDate('date', $hier)
                ->whereIn('statut', ['saisi', 'justifie'])->pluck('user_id')->map(fn($v) => (int)$v)->toArray();
            if (Schema::hasTable('confirmation_taches')) {
                $idsCouverts = array_merge($idsCouverts, ConfirmationTache::whereDate('date', $hier)->pluck('user_id')->map(fn($v) => (int)$v)->toArray());
            }
            if (Schema::hasTable('absences')) {
                $idsCouverts = array_merge($idsCouverts, \App\Models\Absence::whereDate('date_debut', '<=', $hier)->whereDate('date_fin', '>=', $hier)->pluck('user_id')->map(fn($v) => (int)$v)->toArray());
            }
            if (Schema::hasTable('taches')) {
                $idsCouverts = array_merge($idsCouverts, Tache::where('statut', 'termine')->whereDate('date_fin_reelle', $hier)->pluck('user_id')->map(fn($v) => (int)$v)->toArray());
            }
            $sanseSaisieHier = max(0, $totalActifs - count(array_unique($idsCouverts)));
        }
        if ($sanseSaisieHier > 0) $alertes[] = ['type' => 'warn', 'msg' => "{$sanseSaisieHier} collaborateur(s) sans saisie hier."];
        if ($bloquees > 0) $alertes[] = ['type' => 'ko', 'msg' => "{$bloquees} tâche(s) bloquée(s) sur tous les projets."];
        if ($enRetard > 0) $alertes[] = ['type' => 'warn', 'msg' => "{$enRetard} tâche(s) en retard."];

        $arbitragesEnAttente = ArbitrageT3::where('decision', 'en_attente')->count();

        // Auto-fix ENUM si pas encore migré
        try {
            $sprintsEnCours = Sprint::where('statut', 'en_cours')->with('projet')->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE sprints MODIFY COLUMN statut VARCHAR(30) NOT NULL DEFAULT 'planifie'");
            \Illuminate\Support\Facades\DB::table('sprints')->where('statut', 'clos')->update(['statut' => 'termine']);
            $sprintsEnCours = Sprint::where('statut', 'en_cours')->with('projet')->get();
        }

        // Calculer progression, stats et jours restants pour chaque sprint
        $sprintsEnCours = $sprintsEnCours->map(function ($s) {
            $total = Tache::where('sprint_id', $s->id)->count();
            $term = Tache::where('sprint_id', $s->id)->where('statut', 'termine')->count();
            $s->stats_total = $total;
            $s->stats_terminees = $term;
            $s->progression = $total > 0 ? round(($term / $total) * 100) : 0;
            $s->jours_restants = $s->date_fin->gte(today()) ? (int) today()->diffInDays($s->date_fin) : 0;
            return $s;
        });

        // Performance par collaborateur (enrichi : retards + points)
        $collaborateurs = User::where('actif', true)->whereNotIn('role', ['invite', 'direction'])
            ->orderBy('name')->get()->map(function ($c) {
                $c->taches_total = Tache::where('user_id', $c->id)->count();
                $c->taches_terminees = Tache::where('user_id', $c->id)->where('statut', 'termine')->count();
                $c->taches_en_cours = Tache::where('user_id', $c->id)->where('statut', 'en_cours')->count();
                $c->taches_bloquees = Tache::where('user_id', $c->id)->where('statut', 'bloque')->count();
                $c->taches_en_retard = Tache::where('user_id', $c->id)->whereNull('date_fin_reelle')
                    ->whereNotNull('echeance')->whereDate('echeance', '<', today())
                    ->whereIn('statut', ['en_cours', 'a_faire'])->count();
                $c->points_livres = (int) Tache::where('user_id', $c->id)->where('statut', 'termine')
                    ->whereNotNull('sprint_id')->sum('points');
                $c->taux = $c->taches_total > 0 ? round(($c->taches_terminees / $c->taches_total) * 100) : 0;
                return $c;
            });

        // Tâches bloquées
        $topBloquees = Tache::with(['user', 'projet'])->where('statut', 'bloque')
            ->orderByDesc('updated_at')->limit(8)->get();

        // Tâches en retard
        $tachesEnRetard = Tache::with(['user', 'projet'])->whereNull('date_fin_reelle')
            ->whereNotNull('echeance')->whereDate('echeance', '<', today())
            ->whereIn('statut', ['en_cours', 'a_faire'])
            ->orderBy('echeance')->limit(8)->get();

        // Répartition par projet
        $parProjet = Projet::whereHas('taches')->withCount([
            'taches',
            'taches as terminees_count' => fn ($q) => $q->where('statut', 'termine'),
            'taches as en_retard_count' => fn ($q) => $q->whereNull('date_fin_reelle')->whereNotNull('echeance')->whereDate('echeance', '<', today()),
        ])->get();

        // Événements récents
        $evenements = collect();
        if (Schema::hasTable('evenements')) {
            $evenements = Evenement::with('user')
                ->whereDate('date', '>=', today()->subDays(30))
                ->orderByDesc('date')->orderByDesc('created_at')
                ->limit(10)->get();
        }

        // Tâches en attente de validation (par tous les collaborateurs)
        $aValider = Tache::with(['user', 'projet', 'sprint'])
            ->where('statut', 'a_valider')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Mes tâches actives (personnel du pilote)
        $mesTachesActives = Tache::with('projet')
            ->where('user_id', $user->id)
            ->whereIn('statut', ['a_valider', 'a_faire', 'en_cours', 'bloque'])
            ->orderByRaw("FIELD(statut,'a_valider','bloque','en_cours','a_faire')")
            ->orderByRaw("FIELD(priorite,'haute','moyenne','basse')")
            ->orderBy('echeance')
            ->get();

        // Tendance hebdo globale (4 semaines)
        $tendanceHebdoGlobal = [];
        for ($w = 3; $w >= 0; $w--) {
            $deb = today()->copy()->subWeeks($w)->startOfWeek();
            $fi = $deb->copy()->endOfWeek();
            $tendanceHebdoGlobal[] = [
                'label' => 'S' . $deb->weekOfYear,
                'count' => Tache::where('statut', 'termine')
                    ->whereBetween('date_fin_reelle', [$deb, $fi])->count(),
            ];
        }
        $tendanceMaxGlobal = max(1, max(array_column($tendanceHebdoGlobal, 'count')));

        // Taux livraison sprints terminés
        $sprintsTermines = Sprint::where('statut', 'termine')->with('projet')->get()->map(function ($s) {
            $totalT = Tache::where('sprint_id', $s->id)->count();
            $termT = Tache::where('sprint_id', $s->id)->where('statut', 'termine')->count();
            $s->taux_livraison = $totalT > 0 ? round(($termT / $totalT) * 100) : 0;
            $s->total_taches = $totalT;
            $s->taches_terminees = $termT;
            return $s;
        })->sortByDesc('date_fin')->take(6)->values();

        return view('dashboard.pilote', compact(
            'totalTaches', 'terminees', 'enCours', 'bloquees', 'enRetard', 'tauxGlobal',
            'termineesSemaine', 'alertes', 'sanseSaisieHier', 'arbitragesEnAttente',
            'sprintsEnCours', 'collaborateurs', 'topBloquees', 'tachesEnRetard',
            'parProjet', 'evenements', 'mesTachesActives', 'aValider',
            'tendanceHebdoGlobal', 'tendanceMaxGlobal', 'sprintsTermines'
        ));
    }

    /* ─── Calcul assiduité (US-15) ─── */
    private function calculerAssiduite(int $userId): array
    {
        $debut = today()->subDays(30);
        $joursOuvres = 0;
        $joursSaisis = 0;
        $cursor = $debut->copy();
        while ($cursor->lte(today())) {
            if (!$cursor->isWeekend() && !JourFerie::whereDate('date', $cursor)->exists()) {
                $joursOuvres++;

                // 1. JourSaisie existe avec statut saisi/justifie
                $saisi = JourSaisie::where('user_id', $userId)->whereDate('date', $cursor)
                    ->whereIn('statut', ['saisi', 'justifie'])->exists();

                // 2. Ou le collaborateur a confirmé/infirmé au moins une tâche ce jour
                if (!$saisi && Schema::hasTable('confirmation_taches')) {
                    $saisi = ConfirmationTache::where('user_id', $userId)
                        ->whereDate('date', $cursor)->exists();
                }

                // 3. Ou une absence déclarée couvre ce jour
                if (!$saisi && Schema::hasTable('absences')) {
                    $saisi = \App\Models\Absence::pourDate($userId, $cursor)->exists();
                }

                if ($saisi) $joursSaisis++;
            }
            $cursor->addDay();
        }
        $taux = $joursOuvres > 0 ? round(($joursSaisis / $joursOuvres) * 100) : 100;
        return ['taux' => $taux, 'jours_ouvres' => $joursOuvres, 'jours_saisis' => $joursSaisis];
    }

    /**
     * Recherche globale (JSON) — tâches, projets, sprints, collaborateurs
     */
    public function recherche(Request $r)
    {
        $q = trim($r->get('q', ''));
        if (mb_strlen($q) < 2) return response()->json([]);

        $like = "%{$q}%";
        $results = [];

        // Tâches
        $taches = Tache::where('intitule', 'like', $like)
            ->orderByDesc('updated_at')->limit(5)->get();
        foreach ($taches as $t) {
            $results[] = [
                'type'  => 'tache',
                'label' => $t->intitule,
                'sub'   => ucfirst(str_replace('_', ' ', $t->statut)),
                'url'   => route('taches.show', $t),
            ];
        }

        // Projets
        $projets = Projet::where('libelle', 'like', $like)
            ->orderBy('libelle')->limit(5)->get();
        foreach ($projets as $p) {
            $results[] = [
                'type'  => 'projet',
                'label' => $p->libelle,
                'sub'   => ucfirst($p->statut),
                'url'   => route('admin.projets.index'),
            ];
        }

        // Sprints
        if (Schema::hasTable('sprints')) {
            $sprints = Sprint::with('projet')
                ->where(function ($qb) use ($like) {
                    $qb->where('numero', 'like', $like)
                        ->orWhereHas('projet', fn ($pq) => $pq->where('libelle', 'like', $like));
                })
                ->orderByDesc('date_debut')->limit(5)->get();
            foreach ($sprints as $s) {
                $results[] = [
                    'type'  => 'sprint',
                    'label' => "Sprint {$s->numero} — " . ($s->projet?->libelle ?? ''),
                    'sub'   => ucfirst(str_replace('_', ' ', $s->statut)),
                    'url'   => route('sprints.show', $s),
                ];
            }
        }

        // Collaborateurs (pilotes uniquement)
        if ($r->user()->estPilote()) {
            $users = User::where(function ($qb) use ($like) {
                $qb->where('name', 'like', $like)
                   ->orWhere('prenom', 'like', $like)
                   ->orWhere('email', 'like', $like);
            })->where('actif', true)->orderBy('name')->limit(5)->get();

            foreach ($users as $u) {
                $results[] = [
                    'type'  => 'collaborateur',
                    'label' => $u->nomComplet(),
                    'sub'   => $u->roleLibelle(),
                    'url'   => $r->user()->estAdmin() ? route('admin.utilisateurs.edit', $u) : '#',
                ];
            }
        }

        return response()->json(array_slice($results, 0, 15));
    }
}
