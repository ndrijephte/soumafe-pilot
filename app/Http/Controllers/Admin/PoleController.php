<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pole;
use Illuminate\Http\Request;

class PoleController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        return view('admin.poles.index', ['poles' => Pole::withCount('users')->orderBy('libelle')->get()]);
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['libelle' => 'required|string|max:100|unique:poles']);
        Pole::create($r->only('libelle'));
        return back()->with('succes', 'Pôle ajouté.');
    }

    public function update(Request $r, Pole $pole)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['libelle' => 'required|string|max:100|unique:poles,libelle,' . $pole->id]);
        $pole->update($r->only('libelle'));
        return back()->with('succes', 'Pôle mis à jour.');
    }

    public function destroy(Request $r, Pole $pole)
    {
        abort_unless($r->user()->estAdmin(), 403);
        if ($pole->users()->exists()) return back()->with('erreur', 'Impossible : des collaborateurs sont rattachés à ce pôle.');
        $pole->delete();
        return back()->with('succes', 'Pôle supprimé.');
    }
}
