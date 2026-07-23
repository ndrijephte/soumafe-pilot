<?php
namespace App\Http\Controllers;

use App\Models\{Objectif, User};
use Illuminate\Http\Request;

class ObjectifController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();
        $periodes = Objectif::select('periode')->distinct()->orderByDesc('periode')->pluck('periode');
        $periode = $r->get('periode', $periodes->first() ?? now()->format('Y-\TQ'));

        if ($user->estPilote()) {
            // Pilote voit tous les collaborateurs
            $filtreUser = $r->get('user_id');
            $collaborateurs = User::where('actif', true)
                ->whereNotIn('role', ['direction', 'invite'])
                ->orderBy('name')->get();

            $query = Objectif::with('user')->where('periode', $periode);
            if ($filtreUser) $query->where('user_id', $filtreUser);
            $objectifs = $query->orderBy('user_id')->orderByDesc('ponderation')->get();

            // Grouper par collaborateur
            $parCollab = $objectifs->groupBy('user_id');

            return view('objectifs.index', compact('objectifs', 'parCollab', 'collaborateurs', 'periodes', 'periode', 'filtreUser'));
        }

        // Collaborateur voit ses propres objectifs
        $objectifs = Objectif::where('user_id', $user->id)->where('periode', $periode)
            ->orderByDesc('ponderation')->get();

        return view('objectifs.index', compact('objectifs', 'periodes', 'periode'));
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate([
            'user_id' => 'required|exists:users,id',
            'libelle' => 'required|string|max:255',
            'ponderation' => 'required|integer|min:0|max:100',
            'periode' => 'required|string|max:20',
        ]);

        // Vérifier que la somme des pondérations ne dépasse pas 100
        $somme = Objectif::where('user_id', $r->user_id)->where('periode', $r->periode)->sum('ponderation');
        if ($somme + $r->ponderation > 100) {
            return back()->with('erreur', "Pondération totale dépasserait 100% (actuel : {$somme}%).")->withInput();
        }

        Objectif::create([
            'user_id' => $r->user_id,
            'source' => 'pilote',
            'libelle' => $r->libelle,
            'ponderation' => $r->ponderation,
            'periode' => $r->periode,
        ]);

        return back()->with('succes', 'Objectif ajouté.');
    }

    public function update(Request $r, Objectif $objectif)
    {
        $user = $r->user();

        // Collaborateur peut seulement mettre à jour l'avancement de ses propres objectifs
        if (!$user->estPilote()) {
            abort_unless($objectif->user_id === $user->id, 403);
            $r->validate(['avancement' => 'required|integer|min:0|max:100']);
            $objectif->update(['avancement' => $r->avancement]);
            return back()->with('succes', 'Avancement mis à jour.');
        }

        // Pilote peut tout modifier
        $r->validate([
            'libelle' => 'required|string|max:255',
            'ponderation' => 'required|integer|min:0|max:100',
            'avancement' => 'nullable|integer|min:0|max:100',
            'statut_validation' => 'nullable|in:en_attente,valide,rejete',
            'commentaire_pilote' => 'nullable|string|max:500',
        ]);

        // Vérifier somme pondérations (hors cet objectif)
        $somme = Objectif::where('user_id', $objectif->user_id)
            ->where('periode', $objectif->periode)
            ->where('id', '!=', $objectif->id)
            ->sum('ponderation');
        if ($somme + $r->ponderation > 100) {
            return back()->with('erreur', "Pondération totale dépasserait 100% (autres : {$somme}%).")->withInput();
        }

        $objectif->update($r->only(['libelle', 'ponderation', 'avancement', 'statut_validation', 'commentaire_pilote']));
        return back()->with('succes', 'Objectif mis à jour.');
    }

    public function destroy(Request $r, Objectif $objectif)
    {
        abort_unless($r->user()->estPilote(), 403);
        $objectif->delete();
        return back()->with('succes', 'Objectif supprimé.');
    }
}
