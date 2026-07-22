<?php
namespace App\Http\Controllers;

use App\Models\{Projet, Sprint};
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->aRole('administrateur', 'direction', 'chef_projet'), 403);
        $projets = Projet::with(['sprints' => fn ($q) => $q->orderByDesc('numero'), 'sprints.taches'])
            ->orderBy('libelle')->get();
        return view('sprints.index', compact('projets'));
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->aRole('administrateur', 'direction', 'chef_projet'), 403);
        $r->validate([
            'projet_id' => 'required|exists:projets,id',
            'numero' => 'required|integer|min:1',
            'objectif' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'engagement_points' => 'nullable|integer|min:1',
        ]);
        $exists = Sprint::where('projet_id', $r->projet_id)->where('numero', $r->numero)->exists();
        if ($exists) return back()->with('erreur', "Le sprint n°{$r->numero} existe déjà pour ce projet.");
        Sprint::create(array_merge($r->only(['projet_id', 'numero', 'objectif', 'date_debut', 'date_fin', 'engagement_points']), ['statut' => 'planifie']));
        return back()->with('succes', 'Sprint créé.');
    }

    public function update(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->aRole('administrateur', 'direction', 'chef_projet'), 403);
        $r->validate([
            'objectif' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'engagement_points' => 'nullable|integer|min:1',
            'statut' => 'required|in:planifie,en_cours,termine',
        ]);
        $sprint->update($r->only(['objectif', 'date_debut', 'date_fin', 'engagement_points', 'statut']));
        return back()->with('succes', 'Sprint mis à jour.');
    }
}
