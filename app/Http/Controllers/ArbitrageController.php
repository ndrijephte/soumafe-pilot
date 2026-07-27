<?php
namespace App\Http\Controllers;

use App\Models\{ArbitrageT3, Evenement, NotificationApp, Tache};
use Illuminate\Http\Request;

class ArbitrageController extends Controller
{
    /** Collaborateur demande un arbitrage T3 (interruption sprint). */
    public function demander(Request $r, Tache $tache)
    {
        abort_unless((int) $tache->user_id === (int) $r->user()->id && $tache->type === 'T3', 403);
        if ($tache->arbitrage) return back()->with('erreur', 'Un arbitrage est déjà en cours pour cette tâche.');

        $tache->arbitrage()->create([
            'demandeur_id' => $r->user()->id,
            'decision' => 'en_attente',
        ]);

        // Notifier la DG
        $dgs = \App\Models\User::where('role', 'direction')->where('actif', true)->get();
        foreach ($dgs as $dg) {
            NotificationApp::create([
                'user_id' => $dg->id,
                'type' => 'arbitrage_t3',
                'contenu' => $r->user()->nomComplet() . " demande un arbitrage T3 : « {$tache->intitule} ».",
            ]);
        }
        return back()->with('succes', 'Demande d\'arbitrage transmise à la direction.');
    }

    /** DG : liste des arbitrages en attente. */
    public function index(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $arbitrages = ArbitrageT3::with(['tache.user', 'demandeur'])
            ->orderByRaw("FIELD(decision,'en_attente','accorde','refuse')")
            ->orderByDesc('created_at')->get();

        // Événements marquants récents (non traités en priorité)
        $evenements = Evenement::with('user')
            ->orderByRaw("FIELD(recommandation, '', NULL) DESC")
            ->orderByDesc('date')->orderByDesc('created_at')
            ->limit(30)->get();

        return view('arbitrages.index', compact('arbitrages', 'evenements'));
    }

    /** DG décide. */
    public function decider(Request $r, ArbitrageT3 $arbitrage)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['decision' => 'required|in:accorde,refuse']);
        $arbitrage->update([
            'decision' => $r->decision,
            'decideur_id' => $r->user()->id,
        ]);

        $msg = $r->decision === 'accorde'
            ? 'Arbitrage T3 accordé — l\'impact sprint est neutralisé.'
            : 'Arbitrage T3 refusé — la tâche reste à la charge du collaborateur.';

        NotificationApp::create([
            'user_id' => $arbitrage->demandeur_id,
            'type' => 'arbitrage_t3_decision',
            'contenu' => "Votre demande d'arbitrage T3 « {$arbitrage->tache->intitule} » a été " . ($r->decision === 'accorde' ? 'accordée' : 'refusée') . '.',
        ]);

        return back()->with('succes', $msg);
    }

    /** Pilote recommande / réagit à un événement marquant. */
    public function recommander(Request $r, Evenement $evenement)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['recommandation' => 'required|string|max:1000']);

        $evenement->update([
            'recommandation' => $r->recommandation,
            'traite_par_id' => $r->user()->id,
            'traite_le' => now(),
        ]);

        // Notifier le collaborateur qui a signalé l'événement
        NotificationApp::create([
            'user_id' => $evenement->user_id,
            'type' => 'evenement_recommandation',
            'contenu' => $r->user()->nomComplet() . " a réagi à votre événement « {$evenement->description} ».",
        ]);

        return back()->with('succes', 'Recommandation enregistrée.');
    }
}
