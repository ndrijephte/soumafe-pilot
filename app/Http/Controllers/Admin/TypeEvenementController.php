<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TypeEvenement;
use Illuminate\Http\Request;

class TypeEvenementController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        return view('admin.types_evenement.index', [
            'types' => TypeEvenement::orderBy('libelle')->get(),
        ]);
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'code' => 'required|string|max:50|unique:types_evenement|regex:/^[a-z_]+$/',
            'libelle' => 'required|string|max:100',
            'couleur_fond' => 'required|string|max:20',
            'couleur_texte' => 'required|string|max:20',
        ]);
        TypeEvenement::create($r->only(['code', 'libelle', 'couleur_fond', 'couleur_texte']));
        return back()->with('succes', 'Type d\'événement ajouté.');
    }

    public function update(Request $r, TypeEvenement $type)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'libelle' => 'required|string|max:100',
            'couleur_fond' => 'required|string|max:20',
            'couleur_texte' => 'required|string|max:20',
        ]);
        $type->update($r->only(['libelle', 'couleur_fond', 'couleur_texte']));
        return back()->with('succes', 'Type d\'événement mis à jour.');
    }

    public function destroy(Request $r, TypeEvenement $type)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        if ($type->systeme) return back()->with('erreur', 'Impossible : type système protégé.');
        $type->delete();
        return back()->with('succes', 'Type d\'événement supprimé.');
    }
}
