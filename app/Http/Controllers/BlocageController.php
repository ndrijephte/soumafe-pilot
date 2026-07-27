<?php
namespace App\Http\Controllers;

use App\Models\{Blocage, NotificationApp, Tache, User};
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlocageController extends Controller
{
    /** Signaler un blocage sur une tâche. */
    public function store(Request $r, Tache $tache)
    {
        abort_unless($tache->user_id === $r->user()->id || $r->user()->estPilote(), 403);
        $r->validate([
            'motif'             => 'required|string|max:500',
            'type'              => 'nullable|string|in:technique,ressource,dependance,decision,externe',
            'tache_liee_id'     => 'nullable|exists:taches,id',
            'recommandation'    => 'nullable|string|max:1000',
            'leveur_pressenti'  => 'nullable|string|max:100',
        ]);
        $tache->blocages()->create([
            'motif'            => $r->motif,
            'type'             => $r->type ?? 'technique',
            'tache_liee_id'    => $r->tache_liee_id,
            'recommandation'   => $r->recommandation,
            'leveur_pressenti' => $r->leveur_pressenti,
            'date_blocage'     => now(),
        ]);
        $tache->update(['statut' => 'bloque']);

        // Notifier les pilotes
        $pilotes = User::where('actif', true)->whereIn('role', ['administrateur', 'charge_operations', 'direction'])->get();
        $typeLabel = Blocage::TYPES[$r->type ?? 'technique'] ?? $r->type;
        foreach ($pilotes as $p) {
            if ($p->id === $r->user()->id) continue;
            NotificationApp::create([
                'user_id' => $p->id,
                'type'    => 'blocage',
                'contenu' => $r->user()->nomComplet() . ' a signalé un blocage (' . $typeLabel . ') sur « ' . Str::limit($tache->intitule, 35) . ' »',
                'lu'      => false,
            ]);
        }

        return back()->with('succes', 'Blocage signalé — la tâche passe en « Bloqué ».');
    }

    /** Lever un blocage. */
    public function lever(Request $r, Blocage $blocage)
    {
        $tache = $blocage->tache;
        abort_unless($tache->user_id === $r->user()->id || $r->user()->estPilote(), 403);
        $blocage->update(['date_levee' => now()]);
        // Repasser en cours si plus aucun blocage actif
        if (!$tache->blocages()->whereNull('date_levee')->exists()) {
            $tache->update(['statut' => 'en_cours']);
        }
        $delai = round($blocage->delaiHeures(), 1);
        return back()->with('succes', "Blocage levé (durée : {$delai}h).");
    }
}
