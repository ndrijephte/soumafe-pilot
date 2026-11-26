<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TypeBlocage;
use Illuminate\Http\Request;

class TypeBlocageController extends Controller
{
    /** Écran remplacé par Administration > Listes de choix. */
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        return redirect()->route('admin.listes.index', ['liste' => 'type_blocage']);
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'code' => 'required|string|max:50|unique:types_blocage|regex:/^[a-z_]+$/',
            'libelle' => 'required|string|max:100',
        ]);
        TypeBlocage::create($r->only(['code', 'libelle']));
        return back()->with('succes', 'Type de blocage ajouté.');
    }

    public function update(Request $r, TypeBlocage $type)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['libelle' => 'required|string|max:100']);
        $type->update($r->only(['libelle']));
        return back()->with('succes', 'Type de blocage mis à jour.');
    }

    public function destroy(Request $r, TypeBlocage $type)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        if ($type->systeme) return back()->with('erreur', 'Impossible : type système protégé.');
        $n = \App\Models\Blocage::where('type', $type->code)->count();
        if ($n) return back()->with('erreur', "Impossible : « {$type->libelle} » est utilisé par {$n} blocage(s). Renommez-le plutôt.");
        $type->delete();
        return back()->with('succes', 'Type de blocage supprimé.');
    }
}
