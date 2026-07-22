<?php
namespace App\Http\Controllers;

use App\Models\{Tache, JourFerie};
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        $userId = $r->user()->id;

        // Tâches du jour
        $tachesDuJour = Tache::with('projet')
            ->where('user_id', $userId)
            ->whereIn('statut', ['en_cours', 'a_faire'])
            ->whereDate('date_debut', '<=', today())
            ->orderByRaw("FIELD(priorite,'haute','moyenne','basse')")
            ->get();

        // KPI cards
        $total = Tache::where('user_id', $userId)->count();
        $terminees = Tache::where('user_id', $userId)->where('statut', 'termine')->count();
        $enCours = Tache::where('user_id', $userId)->where('statut', 'en_cours')->count();
        $bloquees = Tache::where('user_id', $userId)->where('statut', 'bloque')->count();
        $enRetard = Tache::where('user_id', $userId)->whereNull('date_fin_reelle')
            ->whereNotNull('echeance')->whereDate('echeance', '<', today())->count();

        // Taux de complétion
        $tauxCompletion = $total > 0 ? round(($terminees / $total) * 100) : 0;

        // Alertes
        $alertes = [];
        if ($enRetard > 0) $alertes[] = ['type' => 'warn', 'msg' => "{$enRetard} tâche(s) en retard — pensez à les mettre à jour."];
        if ($bloquees > 0) $alertes[] = ['type' => 'ko', 'msg' => "{$bloquees} tâche(s) bloquée(s) — un blocage peut-il être levé ?"];

        // Vérifier si saisie du jour faite
        $aSaisiAujourdhui = Tache::where('user_id', $userId)
            ->whereDate('date_debut', today())->exists();
        $estJourOuvre = !today()->isWeekend() && !JourFerie::whereDate('date', today())->exists();
        if (!$aSaisiAujourdhui && $estJourOuvre) {
            $alertes[] = ['type' => 'info', 'msg' => 'Aucune tâche saisie aujourd\'hui — la saisie quotidienne est recommandée.'];
        }

        // Tâches terminées cette semaine (pour le mini-bilan)
        $termineesSemaine = Tache::where('user_id', $userId)
            ->where('statut', 'termine')
            ->whereBetween('date_fin_reelle', [today()->startOfWeek(), today()->endOfWeek()])
            ->count();

        return view('dashboard.index', compact(
            'tachesDuJour', 'total', 'terminees', 'enCours', 'bloquees',
            'enRetard', 'tauxCompletion', 'alertes', 'termineesSemaine'
        ));
    }
}
