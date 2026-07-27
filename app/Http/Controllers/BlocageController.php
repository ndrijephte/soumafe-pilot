<?php
namespace App\Http\Controllers;

use App\Models\{Blocage, NotificationApp, Tache, TypeBlocage, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BlocageController extends Controller
{
    /** Signaler un blocage sur une tâche. */
    public function store(Request $r, Tache $tache)
    {
        abort_unless((int) $tache->user_id === (int) $r->user()->id || $r->user()->estPilote(), 403);
        $r->validate([
            'motif'             => 'required|string|max:500',
            'type'              => 'nullable|string|max:50',
            'recommandation'    => 'nullable|string|max:1000',
            'intervenant_id'    => 'nullable|exists:users,id',
            'leveur_pressenti'  => 'nullable|string|max:100',
        ]);

        $typeCode = $r->type ?? 'technique';

        $tache->blocages()->create([
            'motif'            => $r->motif,
            'type'             => $typeCode,
            'recommandation'   => $r->recommandation,
            'intervenant_id'   => $r->intervenant_id,
            'leveur_pressenti' => $r->leveur_pressenti,
            'date_blocage'     => now(),
        ]);
        $tache->update(['statut' => 'bloque']);

        // Résoudre le libellé type
        $typeLabel = $typeCode;
        if (Schema::hasTable('types_blocage')) {
            $tb = TypeBlocage::where('code', $typeCode)->first();
            if ($tb) $typeLabel = $tb->libelle;
        }

        // Notifier les pilotes
        $pilotes = User::where('actif', true)->whereIn('role', ['administrateur', 'charge_operations', 'direction'])->get();
        foreach ($pilotes as $p) {
            if ($p->id === $r->user()->id) continue;
            NotificationApp::create([
                'user_id' => $p->id,
                'type'    => 'blocage',
                'contenu' => $r->user()->nomComplet() . ' a signalé un blocage (' . $typeLabel . ') sur « ' . Str::limit($tache->intitule, 35) . ' »',
                'lu'      => false,
            ]);
        }

        // Notifier l'intervenant proposé
        if ($r->intervenant_id && $r->intervenant_id != $r->user()->id) {
            NotificationApp::create([
                'user_id' => $r->intervenant_id,
                'type'    => 'blocage',
                'contenu' => 'Vous êtes sollicité pour un blocage (' . $typeLabel . ') sur « ' . Str::limit($tache->intitule, 35) . ' » — ' . $r->user()->nomComplet(),
                'lu'      => false,
            ]);
        }

        return back()->with('succes', 'Blocage signalé — la tâche passe en « Bloqué ».');
    }

    /** Lever un blocage. */
    public function lever(Request $r, Blocage $blocage)
    {
        $tache = $blocage->tache;
        abort_unless((int) $tache->user_id === (int) $r->user()->id || $r->user()->estPilote(), 403);
        $blocage->update(['date_levee' => now()]);
        // Repasser en cours si plus aucun blocage actif
        if (!$tache->blocages()->whereNull('date_levee')->exists()) {
            $tache->update(['statut' => 'en_cours']);
        }
        $delai = round($blocage->delaiHeures(), 1);
        return back()->with('succes', "Blocage levé (durée : {$delai}h).");
    }
}
