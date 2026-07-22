<?php
namespace App\Http\Controllers;

use App\Http\Requests\TacheRequest;
use App\Models\{Projet, Sprint, Tache};
use App\Services\ClotureService;
use Illuminate\Http\Request;

class TacheController extends Controller
{
    public function __construct(private ClotureService $cloture) {}

    /** US-07 : mes tâches en kanban (défaut) ou liste, filtres projet/type/priorité. */
    public function index(Request $r)
    {
        $taches = Tache::with(['projet', 'sprint'])
            ->where('user_id', $r->user()->id)
            ->when($r->projet, fn ($q) => $q->where('projet_id', $r->projet))
            ->when($r->type, fn ($q) => $q->where('type', $r->type))
            ->when($r->priorite, fn ($q) => $q->where('priorite', $r->priorite))
            ->orderByRaw("FIELD(priorite,'haute','moyenne','basse')")
            ->orderBy('echeance')
            ->get()
            ->groupBy('statut');

        return view('taches.index', [
            'taches'  => $taches,
            'vue'     => $r->get('vue', 'kanban'),
            'projets' => Projet::orderBy('libelle')->get(),
            'colonnes' => ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'bloque' => 'Bloqué', 'termine' => 'Terminé'],
        ]);
    }

    /** US-06 : création rapide — valeurs par défaut intelligentes. */
    public function store(TacheRequest $r)
    {
        $r->user()->taches()->create($r->validated());
        return back()->with('succes', 'Tâche ajoutée.');
    }

    public function update(TacheRequest $r, Tache $tache)
    {
        abort_unless($tache->user_id === $r->user()->id, 403);
        $tache->update($r->validated());
        return back()->with('succes', 'Tâche mise à jour.');
    }

    /** Changement de statut (glisser-déposer kanban). Clôture = règles v1.5. */
    public function statut(Request $r, Tache $tache)
    {
        abort_unless($tache->user_id === $r->user()->id, 403);
        $r->validate(['statut' => 'required|in:a_faire,en_cours,bloque,termine,reporte']);

        if ($r->statut === 'termine') {
            $this->cloture->cloturer($tache);
            $msg = match ($tache->qualification) {
                'a_temps'   => 'Tâche terminée à temps — bravo.',
                'anticipee' => 'Terminée en avance : un mot sur la raison ? (aide à calibrer nos estimations)',
                'en_retard' => 'Terminée après l\'échéance : merci d\'ajouter une justification en commentaire.',
                default     => 'Tâche terminée.',
            };
            if ($tache->effort_volontaire) $msg .= ' Effort week-end/férié comptabilisé.';
            return response()->json(['ok' => true, 'qualification' => $tache->qualification,
                'justification_requise' => $tache->justification_requise, 'message' => $msg]);
        }

        $tache->update(['statut' => $r->statut]);
        return response()->json(['ok' => true, 'message' => 'Statut mis à jour.']);
    }

    /** Duplication de la veille (US-08 — anticipée ici tant la demande est fréquente). */
    public function dupliquerVeille(Request $r)
    {
        $veille = Tache::where('user_id', $r->user()->id)
            ->whereDate('date_debut', today()->subDay())
            ->whereNotIn('statut', ['termine'])
            ->get();
        foreach ($veille as $t) {
            $r->user()->taches()->create(array_merge(
                $t->only(['type', 'intitule', 'projet_id', 'sprint_id', 'activite', 'priorite', 'points']),
                ['date_debut' => today(), 'echeance' => $t->echeance, 'statut' => $t->statut === 'bloque' ? 'bloque' : 'en_cours']
            ));
        }
        return back()->with('succes', $veille->count() . ' tâche(s) reprise(s) de la veille.');
    }
}
