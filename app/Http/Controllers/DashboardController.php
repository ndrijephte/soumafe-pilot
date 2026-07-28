<?php
namespace App\Http\Controllers;

use App\Models\{ArbitrageT3, ConfirmationTache, Evenement, Projet, Sprint, Tache, JourFerie, JourSaisie, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();

        // Direction + Pilotes opérationnels → même dashboard pilote
        if ($user->aRole('direction') || $user->estPilote()) return $this->dashboardPilote($user);

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
                ->with('projet')->get();

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
            'estResponsable', 'projetData', 'mesTachesActives'
        ));
    }

    /* ─── Dashboard DG (US-17) ─── */
    private function dashboardDG(User $user)
    {
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
            $joursSaisisHier = JourSaisie::whereDate('date', $hier)
                ->whereIn('statut', ['saisi', 'justifie'])->distinct('user_id')->count('user_id');
            $sanseSaisieHier = max(0, $totalActifs - $joursSaisisHier);
        }

        $sprintsEnCours = Sprint::where('statut', 'en_cours')->with('projet')->get();
        $topBloquees = Tache::with(['user', 'projet'])->where('statut', 'bloque')
            ->orderByDesc('updated_at')->limit(5)->get();
        $parProjet = Projet::whereHas('taches')->withCount([
            'taches',
            'taches as terminees_count' => fn ($q) => $q->where('statut', 'termine'),
        ])->get();

        $evenements = collect();
        if (Schema::hasTable('evenements')) {
            $evenements = Evenement::with('user')
                ->whereDate('date', '>=', today()->subDays(30))
                ->orderByDesc('date')->orderByDesc('created_at')
                ->limit(15)->get();
        }

        return view('dashboard.dg', compact(
            'totalTaches', 'terminees', 'enCours', 'bloquees', 'enRetard', 'tauxGlobal',
            'arbitragesEnAttente', 'sanseSaisieHier', 'sprintsEnCours', 'topBloquees', 'parProjet', 'evenements'
        ));
    }

    /* ─── Dashboard pilote opérationnel (charge_operations, administrateur) ─── */
    private function dashboardPilote(User $user)
    {
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
            $joursSaisisHier = JourSaisie::whereDate('date', $hier)
                ->whereIn('statut', ['saisi', 'justifie'])->distinct('user_id')->count('user_id');
            $sanseSaisieHier = max(0, $totalActifs - $joursSaisisHier);
        }
        if ($sanseSaisieHier > 0) $alertes[] = ['type' => 'warn', 'msg' => "{$sanseSaisieHier} collaborateur(s) sans saisie hier."];
        if ($bloquees > 0) $alertes[] = ['type' => 'ko', 'msg' => "{$bloquees} tâche(s) bloquée(s) sur tous les projets."];
        if ($enRetard > 0) $alertes[] = ['type' => 'warn', 'msg' => "{$enRetard} tâche(s) en retard."];

        $arbitragesEnAttente = ArbitrageT3::where('decision', 'en_attente')->count();

        // Sprints en cours
        $sprintsEnCours = Sprint::where('statut', 'en_cours')->with('projet')->get();

        // Performance par collaborateur
        $collaborateurs = User::where('actif', true)->whereNotIn('role', ['invite', 'direction'])
            ->orderBy('name')->get()->map(function ($c) {
                $c->taches_total = Tache::where('user_id', $c->id)->count();
                $c->taches_terminees = Tache::where('user_id', $c->id)->where('statut', 'termine')->count();
                $c->taches_en_cours = Tache::where('user_id', $c->id)->where('statut', 'en_cours')->count();
                $c->taches_bloquees = Tache::where('user_id', $c->id)->where('statut', 'bloque')->count();
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

        return view('dashboard.pilote', compact(
            'totalTaches', 'terminees', 'enCours', 'bloquees', 'enRetard', 'tauxGlobal',
            'termineesSemaine', 'alertes', 'sanseSaisieHier', 'arbitragesEnAttente',
            'sprintsEnCours', 'collaborateurs', 'topBloquees', 'tachesEnRetard',
            'parProjet', 'evenements', 'mesTachesActives', 'aValider'
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
}
