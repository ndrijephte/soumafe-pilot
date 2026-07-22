<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Projet, User};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjetController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        return view('admin.projets.index', [
            'projets' => Projet::with('chefProjet')->withCount('taches')->orderBy('libelle')->get(),
            'chefs' => User::where('actif', true)->whereIn('role', ['chef_projet', 'administrateur', 'direction'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'code' => 'required|string|max:20|unique:projets',
            'libelle' => 'required|string|max:150',
            'client' => 'nullable|string|max:100',
            'chef_projet_id' => 'nullable|exists:users,id',
            'statut' => 'required|in:actif,en_pause,termine,annule',
            'date_debut' => 'nullable|date',
            'date_cible' => 'nullable|date|after_or_equal:date_debut',
        ]);
        Projet::create($r->only(['code', 'libelle', 'client', 'chef_projet_id', 'statut', 'date_debut', 'date_cible']));
        return back()->with('succes', 'Projet créé.');
    }

    public function update(Request $r, Projet $projet)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('projets')->ignore($projet->id)],
            'libelle' => 'required|string|max:150',
            'client' => 'nullable|string|max:100',
            'chef_projet_id' => 'nullable|exists:users,id',
            'statut' => 'required|in:actif,en_pause,termine,annule',
            'date_debut' => 'nullable|date',
            'date_cible' => 'nullable|date|after_or_equal:date_debut',
        ]);
        $projet->update($r->only(['code', 'libelle', 'client', 'chef_projet_id', 'statut', 'date_debut', 'date_cible']));
        return back()->with('succes', 'Projet mis à jour.');
    }
}
