<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Role, User};
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $roles = Role::orderBy('libelle')->get()->map(function ($role) {
            $role->users_count = User::where('role', $role->code)->count();
            return $role;
        });
        return view('admin.roles.index', compact('roles'));
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'code' => 'required|string|max:50|unique:roles|regex:/^[a-z_]+$/',
            'libelle' => 'required|string|max:100',
        ]);
        Role::create($r->only(['code', 'libelle']));
        return back()->with('succes', 'Rôle ajouté.');
    }

    public function update(Request $r, Role $role)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'libelle' => 'required|string|max:100',
        ]);
        $role->update(['libelle' => $r->libelle]);
        return back()->with('succes', 'Rôle mis à jour.');
    }

    public function destroy(Request $r, Role $role)
    {
        abort_unless($r->user()->estAdmin(), 403);
        if ($role->systeme) return back()->with('erreur', 'Impossible : rôle système protégé.');
        if (User::where('role', $role->code)->exists()) return back()->with('erreur', 'Impossible : des utilisateurs ont ce rôle.');
        $role->delete();
        return back()->with('succes', 'Rôle supprimé.');
    }
}
