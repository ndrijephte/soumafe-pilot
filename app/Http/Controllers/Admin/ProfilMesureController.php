<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ProfilMesure, User};
use Illuminate\Http\Request;

class ProfilMesureController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $profils = ProfilMesure::orderBy('libelle')->get()->map(function ($p) {
            $p->users_count = User::where('profil_mesure', $p->code)->count();
            return $p;
        });
        return view('admin.profils.index', compact('profils'));
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'code' => 'required|string|max:50|unique:profils_mesure|regex:/^[a-z_]+$/',
            'libelle' => 'required|string|max:100',
        ]);
        ProfilMesure::create($r->only(['code', 'libelle']));
        return back()->with('succes', 'Profil ajouté.');
    }

    public function update(Request $r, ProfilMesure $profil)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['libelle' => 'required|string|max:100']);
        $profil->update(['libelle' => $r->libelle]);
        return back()->with('succes', 'Profil mis à jour.');
    }

    public function destroy(Request $r, ProfilMesure $profil)
    {
        abort_unless($r->user()->estAdmin(), 403);
        if ($profil->systeme) return back()->with('erreur', 'Impossible : profil système protégé.');
        if (User::where('profil_mesure', $profil->code)->exists()) return back()->with('erreur', 'Impossible : des utilisateurs ont ce profil.');
        $profil->delete();
        return back()->with('succes', 'Profil supprimé.');
    }
}
