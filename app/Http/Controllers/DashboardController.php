<?php
namespace App\Http\Controllers;

use App\Models\{ArbitrageT3, Chauffeur, ConfirmationTache, Evenement, MaintenancePreventive, Objectif, Panne, Penalite, Pointage, Projet, RecetteVehicule, Sprint, Tache, JourFerie, JourSaisie, User, Vehicule};
use App\Services\AlerteParcService;
use App\Services\ExerciceParc;
use App\Services\PerformanceEquipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();

        // Direction générale → dashboard stratégique
        if ($user->aRole('direction')) return $this->dashboardDG($user, $this->periode($r));

        // Pilotes opérationnels → dashboard pilote
        if ($user->estPilote()) return $this->dashboardPilote($user, $this->periode($r));

        // Collaborateur (enrichi si responsable projet)
        return $this->dashboardCollab($user);
    }

    /** Blocs « Sprints de la période », « Tâches bloquées », « Tâches en retard » sur leur propre période (du ... au). */
    public function bloc(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $v = $r->validate([
            'bloc' => 'required|in:sprints,bloquees,retard',
            'du' => 'required|date_format:Y-m-d', 'au' => 'required|date_format:Y-m-d|after_or_equal:du',
            'def_du' => 'nullable|date_format:Y-m-d', 'def_au' => 'nullable|date_format:Y-m-d',
        ]);
        $ind = $this->indicateursPeriode(\Carbon\Carbon::parse($v['du'])->startOfDay(), \Carbon\Carbon::parse($v['au'])->endOfDay(), 8);
        return view('dashboard._bloc', [
            'bloc' => $v['bloc'], 'du' => $v['du'], 'au' => $v['au'],
            'defDu' => $v['def_du'] ?? $v['du'], 'defAu' => $v['def_au'] ?? $v['au'],
            'plein' => $r->boolean('plein'), 'classe' => $r->classe === 'bloc-dg' ? 'bloc-dg' : '',
            'sprintsEnCours' => $ind['sprintsEnCours'], 'topBloquees' => $ind['topBloquees'], 'bloquees' => $ind['bloquees'],
            'tachesEnRetard' => $ind['tachesEnRetard'], 'enRetard' => $ind['nbTachesEnRetard'],
        ]);
    }

    /** Bloc « Performance équipe » pour la période choisie (du ... au). */
    public function performanceEquipe(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $v = $r->validate(['du' => 'required|date_format:Y-m-d', 'au' => 'required|date_format:Y-m-d|after_or_equal:du']);
        return view('dashboard._performance_lignes', ['performance' => PerformanceEquipe::calculer($v['du'], $v['au'])]);
    }

    /**
     * Période consultée : du ... au ... (paramètres « du » et « au »), par défaut l'exercice en cours
     * (du 05 du mois au 04 du mois suivant).
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */
    private function periode(Request $r): array
    {
        [$d, $f] = \App\Services\PeriodeExercice::bornes(today());
        try {
            if ($r->filled('du') && $r->filled('au')) {
                $du = \Carbon\Carbon::createFromFormat('Y-m-d', $r->du)->startOfDay();
                $au = \Carbon\Carbon::createFromFormat('Y-m-d', $r->au)->endOfDay();
                if ($au->gte($du)) return [$du, $au];
            }
        } catch (\Throwable $e) {}
        return [$d->copy()->startOfDay(), $f->copy()->endOfDay()];
    }

    /**
     * Indicateurs des tableaux de bord sur une période. Tâches de la période : échéance (à défaut date de début) comprise
     * dans la période. Taux de retard : tâches échues en retard (non finies, ou finies après l'échéance) / tâches échues.
     * Bloquées : tâches de la période bloquées, ou dont un blocage a été déclaré pendant la période.
     */
    private function indicateursPeriode(\Carbon\Carbon $du, \Carbon\Carbon $au, int $limite): array
    {
        $bornes = [$du->toDateString(), $au->toDateString()];
        $periode = fn($q) => $q->where('statut', '!=', 'reporte')->whereRaw('DATE(COALESCE(echeance, date_debut)) BETWEEN ? AND ?', $bornes);
        $taches = Tache::query()->tap($periode);

        $totalTaches = (clone $taches)->count();
        $terminees = (clone $taches)->where('statut', 'termine')->count();
        $enCours = (clone $taches)->where('statut', 'en_cours')->count();
        $enRetard = (clone $taches)->enRetard()->count();
        $tauxGlobal = $totalTaches > 0 ? (int) round($terminees / $totalTaches * 100) : 0;

        // Retard : sur les tâches déjà échues de la période
        $limiteEchue = min($au->toDateString(), today()->subDay()->toDateString());
        // Les tâches restées inachevées dans un sprint clôturé relèvent de la livraison du sprint, pas du retard (comme la tuile « En retard »)
        $echues = (clone $taches)->whereNotNull('echeance')->whereDate('echeance', '<=', $limiteEchue)
            ->whereNot(fn($q) => $q->where('statut', '!=', 'termine')->whereHas('sprint', fn($sp) => $sp->where('statut', 'termine')));
        $nbEchues = (clone $echues)->count();
        $nbEchuesRetard = (clone $echues)->where(fn($q) => $q
            ->where(fn($t) => $t->where('statut', 'termine')->whereColumn('date_fin_reelle', '>', 'echeance'))
            ->orWhere(fn($t) => $t->whereNotIn('statut', ['termine', 'reporte'])->whereNull('date_fin_reelle')))->count();
        $tauxRetard = $nbEchues > 0 ? (int) round($nbEchuesRetard / $nbEchues * 100) : 0;

        $bloqueesQ = Tache::where(fn($q) => $q->where(fn($b) => $periode($b->where('statut', 'bloque')))
            ->orWhereHas('blocages', fn($b) => $b->whereBetween('date_blocage', [$du, $au])));
        $bloquees = (clone $bloqueesQ)->count();
        $topBloquees = (clone $bloqueesQ)->with(['user', 'projet'])->orderByRaw("statut = 'bloque' DESC")->orderByDesc('updated_at')->limit($limite)->get();

        $retardQ = (clone $taches)->enRetard()->whereIn('statut', ['en_cours', 'a_faire']);
        $nbTachesEnRetard = (clone $retardQ)->count();
        $tachesEnRetard = (clone $retardQ)->with(['user', 'projet'])->orderBy('echeance')->limit(8)->get();

        $parProjet = Projet::whereHas('taches', $periode)->withCount([
            'taches' => $periode,
            'taches as terminees_count' => fn($q) => $periode($q)->where('statut', 'termine'),
            'taches as en_retard_count' => fn($q) => $periode($q)->enRetard(),
        ])->get();

        // Livraison : sprints qui ont couru pendant la période, et sprints clôturés pendant la période
        $sprintsEnCours = Sprint::whereIn('statut', ['en_cours', 'termine'])->whereDate('date_debut', '<=', $au)->whereDate('date_fin', '>=', $du)
            ->with('projet')->orderByRaw("statut = 'en_cours' DESC")->orderByDesc('date_fin')->get()->map(function ($s) {
                $total = Tache::where('sprint_id', $s->id)->count();
                $term = Tache::where('sprint_id', $s->id)->where('statut', 'termine')->count();
                $s->stats_total = $total;
                $s->stats_terminees = $term;
                $s->progression = $total > 0 ? round(($term / $total) * 100) : 0;
                $s->jours_restants = $s->date_fin->gte(today()) ? (int) today()->diffInDays($s->date_fin) : 0;
                return $s;
            });
        $sprintsTermines = Sprint::where('statut', 'termine')->whereBetween('date_fin', $bornes)->with('projet')->get()->map(function ($s) {
            $totalT = Tache::where('sprint_id', $s->id)->count();
            $termT = Tache::where('sprint_id', $s->id)->where('statut', 'termine')->count();
            $s->taux_livraison = $totalT > 0 ? round(($termT / $totalT) * 100) : 0;
            $s->total_taches = $totalT;
            $s->taches_terminees = $termT;
            return $s;
        })->sortByDesc('date_fin')->take(6)->values();

        // Tendance : les 4 semaines qui précèdent la fin de la période (ou aujourd'hui)
        $fin = $au->copy()->min(today());
        $tendance = [];
        for ($w = 3; $w >= 0; $w--) {
            $deb = $fin->copy()->subWeeks($w)->startOfWeek();
            $tendance[] = ['label' => 'S' . $deb->weekOfYear, 'count' => Tache::where('statut', 'termine')->whereBetween('date_fin_reelle', [$deb, $deb->copy()->endOfWeek()])->count()];
        }

        $evenements = Schema::hasTable('evenements')
            ? Evenement::with('user')->whereBetween('date', $bornes)->orderByDesc('date')->orderByDesc('created_at')->limit(15)->get()
            : collect();

        $estExercice = [$du->toDateString(), $au->toDateString()] === array_map(fn($d) => $d->toDateString(), \App\Services\PeriodeExercice::bornes($du));
        $periodeTdb = [
            'du' => $du->toDateString(), 'au' => $au->toDateString(),
            'libelle' => $estExercice ? \App\Services\PeriodeExercice::nom($du) . ' (' . \App\Services\PeriodeExercice::libelle($du) . ')' : 'Du ' . $du->format('d/m/Y') . ' au ' . $au->format('d/m/Y'),
            'courante' => $du->isSameDay(\App\Services\PeriodeExercice::bornes(today())[0]) && $estExercice,
            'exercices' => collect(range(0, 11))->map(fn($i) => \App\Services\PeriodeExercice::bornes(today()->copy()->subMonthsNoOverflow($i)))
                ->map(fn($b) => ['du' => $b[0]->toDateString(), 'au' => $b[1]->toDateString(), 'nom' => \App\Services\PeriodeExercice::nom($b[0])])->unique('du')->values(),
        ];

        return compact('totalTaches', 'terminees', 'enCours', 'bloquees', 'enRetard', 'tauxGlobal', 'tauxRetard', 'nbEchues', 'nbEchuesRetard',
            'topBloquees', 'tachesEnRetard', 'nbTachesEnRetard', 'parProjet', 'sprintsEnCours', 'sprintsTermines', 'tendance', 'evenements', 'periodeTdb');
    }

    /* ─── Dashboard collaborateur (US-16) - enrichi si responsable projet ─── */
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
        $enRetard = Tache::where('user_id', $userId)->enRetard()->count();
        $tauxCompletion = $total > 0 ? round(($terminees / $total) * 100) : 0;

        // Les blocs du tableau de bord portent les signaux (voyants) et mènent au détail
        $estJourOuvre = (\App\Services\CalendrierTravail::estJourTravaille(today()) || (Schema::hasTable('jours_ouvrables_speciaux') && \App\Models\JourOuvrableSpecial::where('date', today()->toDateString())->exists())) && !JourFerie::whereDate('date', today())->exists();

        $termineesSemaine = Tache::where('user_id', $userId)->where('statut', 'termine')
            ->whereBetween('date_fin_reelle', [today()->startOfWeek(), today()->endOfWeek()])->count();

        $assiduite = $this->calculerAssiduite($userId);

        // Points livrés (tâches sprint terminées)
        // Points de mes tâches dans les sprints en cours (l'historique des sprints clôturés n'y figure plus)
        $sprintsOuverts = fn($q) => $q->where('statut', 'en_cours');
        $pointsLivres = (int) Tache::where('user_id', $userId)->where('statut', 'termine')->whereHas('sprint', $sprintsOuverts)->sum('points');
        $pointsTotalSprint = (int) Tache::where('user_id', $userId)->where('statut', '!=', 'reporte')->whereHas('sprint', $sprintsOuverts)->sum('points');

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
        $mesTachesActives = Tache::with(['projet', 'sprint'])
            ->where('user_id', $userId)
            ->whereIn('statut', ['a_valider', 'a_faire', 'en_cours', 'bloque'])
            ->nonArchivees()
            ->orderByRaw("FIELD(statut,'a_valider','bloque','en_cours','a_faire')")
            ->orderByRaw("FIELD(priorite,'haute','moyenne','basse')")
            ->orderBy('echeance')
            ->get()
            // Tâches du jour en tête (même définition que « Mes tâches » et la saisie quotidienne)
            ->sortBy(fn ($t) => \App\Http\Controllers\TacheController::estDuJour($t) ? 0 : 1)->values();

        // --- Section responsable projet (dynamique) ---
        $estResponsable = $user->projetsResponsable()->exists();
        $projetData = null;

        if ($estResponsable) {
            $projetIds = Projet::where('responsable_projet_id', $userId)->pluck('id');
            $mesProjets = Projet::whereIn('id', $projetIds)->withCount([
                'taches',
                'taches as terminees_count' => fn ($q) => $q->where('statut', 'termine'),
                'taches as en_retard_count' => fn ($q) => $q->enRetard(),
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

        // Pointage du jour - masqué si jour non ouvré
        $monPointage = null;
        $pointageActif = $estJourOuvre;
        if (Schema::hasTable('pointages') && $pointageActif) {
            $monPointage = Pointage::where('user_id', $userId)->where('date', today()->toDateString())->first();
            // Rediriger si retard non justifié
            if ($monPointage && $monPointage->statut === 'retard' && !$monPointage->motif_retard) {
                return redirect()->route('pointage.index')
                    ->with('demande_motif_retard', $monPointage->id);
            }
        }

        return view('dashboard.index', compact(
            'tachesDuJour', 'total', 'terminees', 'enCours', 'bloquees',
            'enRetard', 'tauxCompletion', 'termineesSemaine', 'assiduite',
            'estResponsable', 'projetData', 'mesTachesActives',
            'pointsLivres', 'pointsTotalSprint', 'tendanceHebdo', 'tendanceMax',
            'parPriorite', 'totalPrio', 'monPointage', 'pointageActif'
        ));
    }

    /* ─── Dashboard DG (US-17) ─── */
    private function dashboardDG(User $user, array $periode)
    {
        AlerteParcService::verifier();
        [$du, $au] = $periode;
        $ind = $this->indicateursPeriode($du, $au, 5);
        extract($ind);
        $tendanceHebdo = $tendance;

        $arbitragesEnAttente = ArbitrageT3::where('decision', 'en_attente')->count();

        $hier = today()->subDay();
        $sansSaisieHier = 0;
        if (\App\Services\CalendrierTravail::estJourTravaille($hier) && !JourFerie::whereDate('date', $hier)->exists()) {
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
            $sansSaisieHier = max(0, $totalActifs - count(array_unique($idsCouverts)));
        }

        $termineesSemaine = Tache::where('statut', 'termine')
            ->whereBetween('date_fin_reelle', [today()->startOfWeek(), today()->endOfWeek()])->count();

        $tendanceMax = max(1, max(array_column($tendanceHebdo, 'count')));
        $performance = PerformanceEquipe::calculer($du->toDateString(), $au->toDateString());

        // ─── Parc automobile (résumé DG) ───
        $parc = null;
        if (Schema::hasTable('vehicules')) {
            [$moisDebut, $moisFin] = ExerciceParc::courant();

            $vehiculesTotal = Vehicule::count();
            $vehiculesActifs = Vehicule::where('statut', 'en_service')->count();

            // Recette = recettes saisies + recouvrements de recette payés par les chauffeurs sur l'exercice
            $recouvParVehicule = Schema::hasTable('versements_recouvrement') && Schema::hasTable('recouvrements')
                ? \App\Models\VersementRecouvrement::recetteChauffeurs()->whereBetween('recouvrements.date_recette', [$moisDebut, $moisFin])
                    ->selectRaw('recouvrements.vehicule_id as vid, recouvrements.chauffeur_nom as nom, SUM(versements_recouvrement.montant) as total')
                    ->groupBy('recouvrements.vehicule_id', 'recouvrements.chauffeur_nom')->get()
                : collect();
            $recetteMois = (int) RecetteVehicule::whereBetween('date', [$moisDebut, $moisFin])->sum('recette') + (int) $recouvParVehicule->sum('total');
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

            $maintenancesRetard = MaintenancePreventive::where(fn($s) => $s->whereNull('statut')->orWhere('statut', '!=', 'realisee'))
                ->whereNotNull('prochaine_echeance')->whereDate('prochaine_echeance', '<', today())->count();

            // Top 5 véhicules par recette du mois
            $topVehicules = RecetteVehicule::whereBetween('date', [$moisDebut, $moisFin])
                ->select('vehicule_id', \Illuminate\Support\Facades\DB::raw('SUM(recette) as total'), \Illuminate\Support\Facades\DB::raw('SUM(objectif) as obj'))
                ->groupBy('vehicule_id')->orderByDesc('total')->limit(5)->get()
                ->map(function ($r) use ($recouvParVehicule) {
                    $v = Vehicule::find($r->vehicule_id);
                    $total = (int) $r->total + (int) $recouvParVehicule->where('vid', $r->vehicule_id)->sum('total');
                    return [
                        'immat' => $v ? $v->immatriculation : '?',
                        'categorie' => $v ? $v->categorieLabel() : '?',
                        'recette' => $total,
                        'objectif' => (int) $r->obj,
                        'taux' => $r->obj > 0 ? round(($total / $r->obj) * 100) : 0,
                    ];
                })->sortByDesc('recette')->values();

            $resultatNet = $recetteMois - $coutPannesMois - $montantPenalitesMois;

            // Top 3 chauffeurs du mois (performance individuelle)
            $topChauffeurs = collect();
            if (Schema::hasTable('chauffeurs') && Schema::hasTable('chauffeur_vehicule')) {
                $topChauffeurs = Chauffeur::with(['vehicules' => fn($q) => $q->wherePivot('actif', true)])
                    ->where('statut', 'actif')->get()
                    ->map(function ($c) use ($moisDebut, $moisFin, $recouvParVehicule) {
                        $nom = $c->nomComplet();
                        $cvIds = $c->vehicules->pluck('id');
                        // Toutes ses recettes de l'exercice, quel que soit le véhicule conduit
                        $recQ = RecetteVehicule::where('chauffeur', $nom)->whereBetween('date', [$moisDebut, $moisFin]);
                        $rec = (int) (clone $recQ)->sum('recette') + (int) $recouvParVehicule->where('nom', $nom)->sum('total');
                        $obj = (int) (clone $recQ)->sum('objectif');
                        $nbJ = (clone $recQ)->count();
                        if ($nbJ === 0) return null;
                        $pen = 0;
                        if (Schema::hasTable('penalites') && $cvIds->isNotEmpty()) {
                            $pen = (int) Penalite::whereIn('vehicule_id', $cvIds)->where('chauffeur', $nom)->whereBetween('date_infraction', [$moisDebut, $moisFin])->sum('montant');
                        }
                        $pan = $cvIds->isNotEmpty() ? (int) Panne::whereIn('vehicule_id', $cvIds)->whereBetween('date_declaration', [$moisDebut, $moisFin])->sum('cout_reel') : 0;
                        return [
                            'nom' => $nom,
                            'vehicules' => $c->vehicules->pluck('immatriculation')->join(', '),
                            'recette' => $rec,
                            'taux' => $obj > 0 ? round(($rec / $obj) * 100) : 0,
                            'resultat' => $rec - $pan - $pen,
                        ];
                    })->filter()->sortByDesc('resultat')->take(3)->values();
            }

            // Gestionnaire parc
            $gestionnaire = User::where('actif', true)->where('delegation_parc', true)->get()
                ->map(fn($u) => $u->nomComplet())->join(', ');

            $parc = compact(
                'vehiculesTotal', 'vehiculesActifs',
                'recetteMois', 'objectifMois', 'tauxRecette',
                'coutPannesMois', 'pannesOuvertes',
                'montantPenalitesMois', 'penalitesNonPayees',
                'maintenancesRetard', 'topVehicules', 'resultatNet',
                'topChauffeurs', 'gestionnaire'
            ) + ['exercice' => ExerciceParc::libelle(ExerciceParc::cle())];
        }

        // Pointage : stats du jour pour DG
        $pointageStats = null;
        if (Schema::hasTable('pointages')) {
            $today = today()->toDateString();
            $totalCollabs = User::where('actif', true)->whereNotIn('role', ['invite', 'direction', 'administrateur'])->count();
            $pointagesJour = Pointage::where('date', $today)->get();
            $presentsJour = $pointagesJour->whereNotIn('statut', ['non_pointe', 'absent'])->count();
            $retardsJour = $pointagesJour->where('statut', 'retard')->count();
            $absentsJour = $pointagesJour->where('statut', 'absent')->count();
            $tauxPresence = $totalCollabs > 0 ? round(($presentsJour / $totalCollabs) * 100) : 0;
            $pointageStats = compact('totalCollabs', 'presentsJour', 'retardsJour', 'absentsJour', 'tauxPresence');
        }

        return view('dashboard.dg', compact(
            'totalTaches', 'terminees', 'enCours', 'bloquees', 'enRetard', 'tauxGlobal',
            'termineesSemaine', 'arbitragesEnAttente', 'sansSaisieHier',
            'sprintsEnCours', 'topBloquees', 'parProjet', 'evenements',
            'tendanceHebdo', 'tendanceMax', 'sprintsTermines', 'performance',
            'parc', 'pointageStats', 'tauxRetard', 'nbEchues', 'nbEchuesRetard', 'periodeTdb'
        ));
    }

    /* ─── Dashboard pilote opérationnel (charge_operations, administrateur) ─── */
    private function dashboardPilote(User $user, array $periode)
    {
        AlerteParcService::verifier();
        // KPI de la période (par défaut l'exercice en cours : du 05 au 04)
        [$du, $au] = $periode;
        extract($this->indicateursPeriode($du, $au, 8));

        $termineesSemaine = Tache::where('statut', 'termine')
            ->whereBetween('date_fin_reelle', [today()->startOfWeek(), today()->endOfWeek()])->count();

        $hier = today()->subDay();
        $sansSaisieHier = 0;
        if (\App\Services\CalendrierTravail::estJourTravaille($hier) && !JourFerie::whereDate('date', $hier)->exists()) {
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
            $sansSaisieHier = max(0, $totalActifs - count(array_unique($idsCouverts)));
        }
        $arbitragesEnAttente = ArbitrageT3::where('decision', 'en_attente')->count();

        // Collaborateurs actifs (exclut admin, DG, invité) et leur performance sur la période d'exercice en cours
        $collaborateurs = User::where('actif', true)->whereNotIn('role', ['invite', 'direction', 'administrateur'])->orderBy('name')->get();
        $performance = PerformanceEquipe::calculer($du->toDateString(), $au->toDateString());

        // Tâches en attente de validation (par tous les collaborateurs)
        $aValider = Tache::with(['user', 'projet', 'sprint'])
            ->where('statut', 'a_valider')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Mes tâches actives (personnel du pilote)
        $mesTachesActives = Tache::with(['projet', 'sprint'])
            ->where('user_id', $user->id)
            ->whereIn('statut', ['a_valider', 'a_faire', 'en_cours', 'bloque'])
            ->nonArchivees()
            ->orderByRaw("FIELD(statut,'a_valider','bloque','en_cours','a_faire')")
            ->orderByRaw("FIELD(priorite,'haute','moyenne','basse')")
            ->orderBy('echeance')
            ->get()
            // Tâches du jour en tête (même définition que « Mes tâches » et la saisie quotidienne)
            ->sortBy(fn ($t) => \App\Http\Controllers\TacheController::estDuJour($t) ? 0 : 1)->values();

        $tendanceHebdoGlobal = $tendance;
        $tendanceMaxGlobal = max(1, max(array_column($tendanceHebdoGlobal, 'count')));

        // Pointage du jour (pilote) - masqué si jour non ouvré
        $monPointage = null;
        $estJourOuvreP = (\App\Services\CalendrierTravail::estJourTravaille(today()) || (Schema::hasTable('jours_ouvrables_speciaux') && \App\Models\JourOuvrableSpecial::where('date', today()->toDateString())->exists())) && !JourFerie::whereDate('date', today())->exists();
        $pointageActif = $estJourOuvreP;
        if (Schema::hasTable('pointages') && $pointageActif) {
            $monPointage = Pointage::where('user_id', $user->id)->where('date', today()->toDateString())->first();
            // Rediriger si retard non justifié
            if ($monPointage && $monPointage->statut === 'retard' && !$monPointage->motif_retard) {
                return redirect()->route('pointage.index')
                    ->with('demande_motif_retard', $monPointage->id);
            }
        }

        return view('dashboard.pilote', compact(
            'totalTaches', 'terminees', 'enCours', 'bloquees', 'enRetard', 'tauxGlobal',
            'termineesSemaine', 'sansSaisieHier', 'arbitragesEnAttente',
            'sprintsEnCours', 'collaborateurs', 'performance', 'topBloquees', 'tachesEnRetard', 'nbTachesEnRetard',
            'parProjet', 'evenements', 'mesTachesActives', 'aValider',
            'tendanceHebdoGlobal', 'tendanceMaxGlobal', 'sprintsTermines', 'monPointage', 'pointageActif',
            'tauxRetard', 'nbEchues', 'nbEchuesRetard', 'periodeTdb'
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
            if (\App\Services\CalendrierTravail::estJourTravaille($cursor) && !JourFerie::whereDate('date', $cursor)->exists()) {
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
     * Recherche globale (JSON) - tâches, projets, sprints, collaborateurs
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
                    'label' => "Sprint {$s->numero} - " . ($s->projet?->libelle ?? ''),
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
