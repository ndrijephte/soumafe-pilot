<?php
namespace App\Http\Controllers;

use App\Models\{Blocage, Tache};
use Illuminate\Http\Request;

class BlocageController extends Controller
{
    /** Signaler un blocage sur une tâche. */
    public function store(Request $r, Tache $tache)
    {
        abort_unless($tache->user_id === $r->user()->id, 403);
        $r->validate([
            'motif' => 'required|string|max:500',
            'leveur_pressenti' => 'nullable|string|max:100',
        ]);
        $tache->blocages()->create([
            'motif' => $r->motif,
            'leveur_pressenti' => $r->leveur_pressenti,
            'date_blocage' => now(),
        ]);
        $tache->update(['statut' => 'bloque']);
        return back()->with('succes', 'Blocage signalé — la tâche passe en « Bloqué ».');
    }

    /** Lever un blocage. */
    public function lever(Request $r, Blocage $blocage)
    {
        $tache = $blocage->tache;
        abort_unless($tache->user_id === $r->user()->id || $r->user()->aRole('administrateur', 'direction', 'chef_projet'), 403);
        $blocage->update(['date_levee' => now()]);
        // Repasser en cours si plus aucun blocage actif
        if (!$tache->blocages()->whereNull('date_levee')->exists()) {
            $tache->update(['statut' => 'en_cours']);
        }
        $delai = round($blocage->delaiHeures(), 1);
        return back()->with('succes', "Blocage levé (durée : {$delai}h).");
    }
}
