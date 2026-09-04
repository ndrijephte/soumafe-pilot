<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domaine;
use Illuminate\Http\Request;

class DomaineController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        return view('admin.domaines.index', [
            'domaines' => Domaine::withCount('services')->orderBy('libelle')->get(),
        ]);
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['libelle' => 'required|string|max:100|unique:domaines']);
        Domaine::create($r->only('libelle'));
        return back()->with('succes', 'Domaine ajouté.');
    }

    public function update(Request $r, Domaine $domaine)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['libelle' => 'required|string|max:100|unique:domaines,libelle,' . $domaine->id]);
        $domaine->update($r->only('libelle'));
        return back()->with('succes', 'Domaine mis à jour.');
    }

    public function destroy(Request $r, Domaine $domaine)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        if ($domaine->services()->exists()) return back()->with('erreur', 'Impossible : des services sont rattachés à ce domaine.');
        $domaine->delete();
        return back()->with('succes', 'Domaine supprimé.');
    }
}
