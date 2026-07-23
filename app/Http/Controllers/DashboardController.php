<?php
namespace App\Http\Controllers;

use App\Models\{ArbitrageT3, Evenement, Projet, Sprint, Tache, JourFerie, JourSaisie, User};
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();

        // Aiguiller selon rôle
        if ($user->aRole('direction')) return $this->dashboardDG($user);
        if ($user->projetsResponsable()->exists()) return $this->dashboardChef($user);
        return $this->dashboardCollab($user);
    }

    /* ─── Dashboard collaborateur (US-16) ─── */
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

        // Jauge d'assiduité (US-15)
        $assiduite = $this->calculerAssiduite($userId);

        return view('dashboard.index', compact(
            'tachesDuJour', 'total', 'terminees', 'enCours', 'bloquees',
            'enRetard', 'tauxCompletion', 'alertes', 'termineesSemaine', 'assiduite'
        ));
    }

    /* ─── Dashboard DG (US-17) ─── */
    private function dashboardDG(User $user)
    {
        // KPI globaux
        $totalTaches = Tache::count();
        $terminees = Tache::where('statut', 'termine')->count();
        $enCours = Tache::where('statut', 'en_cours')->count();
        $bloquees = Tache::where('statut', 'bloque')->count();
        $enRetard = Tache::whereNull('date_fin_reelle')->whereNotNull('echeance')
            ->whereDate('echeance', '<', today())->count();
        $tauxGlobal = $totalTaches > 0 ? round(($terminees / $totalTaches) * 100) : 0;

        // Arbitrages en attente
        $arbitragesEnAttente = ArbitrageT3::where('decision', 'en_attente')->count();

        // Collaborateurs sans saisie hier
        $hier = today()->subDay();
        $sanseSaisieHier = 0;
        if (!$hier->isWeekend() && !JourFerie::whereDate('date', $hier)->exists()) {
            $sanseSaisieHier = JourSaisie::whereDate('date', $hier)->where('statut', 'a_justifier')->count();
        }

        // Sprints en cours
        $sprintsEnCours = Sprint::where('statut', 'en_cours')->with('projet')->get();

        // Top 5 tâches bloquées
        $topBloquees = Tache::with(['user', 'projet'])->where('statut', 'bloque')
            ->orderByDesc('updated_at')->limit(5)->get();

        // Répartition par projet
        $parProjet = Projet::withCount([
            'taches',
            'taches as terminees_count' => fn ($q) => $q->where('statut', 'termine'),
        ])->having('taches_count', '>', 0)->get();

        // Événements récents (7 derniers jours)
        $evenements = Evenement::with('user')
            ->whereDate('date', '>=', today()->subDays(7))
            ->orderByDesc('date')->orderByDesc('created_at')
            ->limit(10)->get();

        return view('dashboard.dg', compact(
            'totalTaches', 'terminees', 'enCours', 'bloquees', 'enRetard', 'tauxGlobal',
            'arbitragesEnAttente', 'sanseSaisieHier', 'sprintsEnCours', 'topBloquees', 'parProjet', 'evenements'
        ));
    }

    /* ─── Dashboard responsable projet (US-18) ─── */
    private function dashboardChef(User $user)
    {
        $projets = Projet::where('responsable_projet_id', $user->id)->pluck('id');

        $totalTaches = Tache::whereIn('projet_id', $projets)->count();
        $terminees = Tache::whereIn('projet_id', $projets)->where('statut', 'termine')->count();
        $enCours = Tache::whereIn('projet_id', $projets)->where('statut', 'en_cours')->count();
        $bloquees = Tache::whereIn('projet_id', $projets)->where('statut', 'bloque')->count();
        $enRetard = Tache::whereIn('projet_id', $projets)->whereNull('date_fin_reelle')
            ->whereNotNull('echeance')->whereDate('echeance', '<', today())->count();
        $tauxGlobal = $totalTaches > 0 ? round(($terminees / $totalTaches) * 100) : 0;

        // Sprints en cours de mes projets
        $sprintsEnCours = Sprint::whereIn('projet_id', $projets)->where('statut', 'en_cours')
            ->with('projet')->get();

        // Membres d'équipe sur mes projets
        $membresIds = Tache::whereIn('projet_id', $projets)->distinct()->pluck('user_id');
        $membres = User::whereIn('id', $membresIds)->get()->map(function ($m) {
            $m->taches_en_cours = Tache::where('user_id', $m->id)->where('statut', 'en_cours')->count();
            $m->taches_bloquees = Tache::where('user_id', $m->id)->where('statut', 'bloque')->count();
            return $m;
        });

        // Tâches bloquées de mes projets
        $topBloquees = Tache::with(['user', 'projet'])->whereIn('projet_id', $projets)
            ->where('statut', 'bloque')->orderByDesc('updated_at')->limit(5)->get();

        return view('dashboard.chef', compact(
            'totalTaches', 'terminees', 'enCours', 'bloquees', 'enRetard', 'tauxGlobal',
            'sprintsEnCours', 'membres', 'topBloquees'
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
                $saisi = JourSaisie::where('user_id', $userId)->whereDate('date', $cursor)
                    ->whereIn('statut', ['saisi', 'justifie'])->exists();
                if ($saisi) $joursSaisis++;
            }
            $cursor->addDay();
        }
        $taux = $joursOuvres > 0 ? round(($joursSaisis / $joursOuvres) * 100) : 100;
        return ['taux' => $taux, 'jours_ouvres' => $joursOuvres, 'jours_saisis' => $joursSaisis];
    }
}
