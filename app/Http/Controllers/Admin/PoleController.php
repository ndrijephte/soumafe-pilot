<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Pole, Service};
use Illuminate\Http\Request;

class PoleController extends Controller
{
    /** Écran remplacé par Administration > Organisation > Structure. */
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        return redirect()->route('admin.organisation.index');
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'libelle' => 'required|string|max:100|unique:poles',
            'service_id' => 'nullable|exists:services,id',
        ]);
        Pole::create($r->only(['libelle', 'service_id']));
        return back()->with('succes', 'Pôle ajouté.');
    }

    public function update(Request $r, Pole $pole)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'libelle' => 'required|string|max:100|unique:poles,libelle,' . $pole->id,
            'service_id' => 'nullable|exists:services,id',
        ]);
        $pole->update($r->only(['libelle', 'service_id']));
        return back()->with('succes', 'Pôle mis à jour.');
    }

    public function destroy(Request $r, Pole $pole)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        if ($pole->users()->exists()) return back()->with('erreur', 'Impossible : des collaborateurs sont rattachés à ce pôle.');
        $pole->delete();
        return back()->with('succes', 'Pôle supprimé.');
    }
}
