<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TypeBlocage;
use Illuminate\Http\Request;

class TypeBlocageController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        return view('admin.types_blocage.index', [
            'types' => TypeBlocage::orderBy('libelle')->get(),
        ]);
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
        if ($type->systeme) return back()->with('erreur', 'Impossible : type système protégé.');
        $type->delete();
        return back()->with('succes', 'Type de blocage supprimé.');
    }
}
