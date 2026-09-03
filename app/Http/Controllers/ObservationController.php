<?php

namespace App\Http\Controllers;

use App\Models\{Observation, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ObservationController extends Controller
{
    public function index(Request $r)
    {
        if (!Schema::hasTable('observations')) {
            return view('observations.index', [
                'observations' => collect(),
                'stats'        => ['total' => 0, 'nouveau' => 0, 'en_cours' => 0, 'resolu' => 0, 'bloque' => 0],
                'responsables' => collect(),
            ]);
        }

        $query = Observation::with(['responsable', 'declarant'])->orderByDesc('date_reception');

        if ($r->filled('statut'))   $query->where('statut', $r->statut);
        if ($r->filled('priorite')) $query->where('priorite', $r->priorite);
        if ($r->filled('type'))     $query->where('type_difficulte', $r->type);
        if ($r->filled('responsable')) $query->where('responsable_id', $r->responsable);

        $observations = $query->get();

        $all = Observation::all();
        $stats = [
            'total'    => $all->count(),
            'nouveau'  => $all->where('statut', 'nouveau')->count(),
            'en_cours' => $all->where('statut', 'en_cours')->count(),
            'resolu'   => $all->where('statut', 'resolu')->count(),
            'bloque'   => $all->where('statut', 'bloque')->count(),
        ];

        $responsables = User::where('actif', true)
            ->whereNotIn('role', ['invite'])
            ->orderBy('name')
            ->get();

        return view('observations.index', compact('observations', 'stats', 'responsables'));
    }

    /** Phase 1 — Déclaration */
    public function store(Request $r)
    {
        $r->validate([
            'description'      => 'required|string|max:2000',
            'type_difficulte'  => 'required|string|max:100',
            'priorite'         => 'required|string|max:30',
            'responsable_id'   => 'nullable|exists:users,id',
            'origine'          => 'required|string|max:100',
            'commentaires'     => 'nullable|string|max:2000',
        ]);

        Observation::create([
            'code'            => Observation::prochainCode(),
            'date_reception'  => now()->toDateString(),
            'description'     => $r->description,
            'type_difficulte' => $r->type_difficulte,
            'priorite'        => $r->priorite,
            'responsable_id'  => $r->responsable_id ?: null,
            'origine'         => $r->origine,
            'commentaires'    => $r->commentaires,
            'statut'          => 'nouveau',
            'declare_par'     => auth()->id(),
        ]);

        $this->auditer('declarer_observation', $r->description);

        return redirect()->route('observations.index')
            ->with('succes', 'Observation déclarée avec succès.');
    }

    /** Phase 2 — Résolution / mise à jour statut */
    public function update(Request $r, Observation $observation)
    {
        $r->validate([
            'statut'                  => 'required|in:nouveau,en_cours,resolu,bloque,rejete',
            'mode_execution'          => 'nullable|string|max:100',
            'date_livraison'          => 'nullable|date',
            'commentaire_resolution'  => 'nullable|string|max:2000',
            'responsable_id'          => 'nullable|exists:users,id',
        ]);

        $observation->statut                 = $r->statut;
        $observation->mode_execution         = $r->mode_execution;
        $observation->date_livraison         = $r->date_livraison;
        $observation->commentaire_resolution = $r->commentaire_resolution;

        if ($r->filled('responsable_id')) {
            $observation->responsable_id = $r->responsable_id;
        }

        if (in_array($r->statut, ['resolu', 'rejete']) && !$observation->resolu_le) {
            $observation->resolu_par = auth()->id();
            $observation->resolu_le  = now();
        }

        $observation->save();
        $this->auditer('traiter_observation', $observation->code . ' → ' . $observation->statutLibelle());

        return redirect()->route('observations.index')
            ->with('succes', 'Observation mise à jour.');
    }

    /** Suppression */
    public function destroy(Observation $observation)
    {
        $code = $observation->code;
        $observation->delete();
        $this->auditer('supprimer_observation', $code);

        return redirect()->route('observations.index')
            ->with('succes', 'Observation supprimée.');
    }

    private function auditer(string $action, string $cible)
    {
        if (Schema::hasTable('audit_logs') && class_exists(\App\Models\AuditLog::class)) {
            \App\Models\AuditLog::log($action, 'observations', $cible);
        }
    }
}
