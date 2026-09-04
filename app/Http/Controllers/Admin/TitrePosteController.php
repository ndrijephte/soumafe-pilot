<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TitrePoste;
use Illuminate\Http\Request;

class TitrePosteController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        return view('admin.titres_poste.index', [
            'titres' => TitrePoste::orderBy('libelle')->get(),
        ]);
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['libelle' => 'required|string|max:100|unique:titres_poste']);
        TitrePoste::create($r->only('libelle'));
        return back()->with('succes', 'Titre de poste ajouté.');
    }

    public function update(Request $r, TitrePoste $titre)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['libelle' => 'required|string|max:100|unique:titres_poste,libelle,' . $titre->id]);
        $titre->update($r->only('libelle'));
        return back()->with('succes', 'Titre de poste mis à jour.');
    }

    public function destroy(Request $r, TitrePoste $titre)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        $titre->delete();
        return back()->with('succes', 'Titre de poste supprimé.');
    }
}
