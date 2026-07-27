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
            'colonnes' => ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'a_valider' => 'À valider', 'bloque' => 'Bloqué', 'termine' => 'Terminé'],
        ]);
    }

    /** Détail tâche : blocages, commentaires, métadonnées. */
    public function show(Request $r, Tache $tache)
    {
        abort_unless($tache->user_id === $r->user()->id || $r->user()->estPilote(), 403);
        $tache->load(['user', 'projet', 'sprint', 'blocages', 'commentaires.user']);
        $blocagesActifs = $tache->blocages->whereNull('date_levee')->count();
        return view('taches.show', compact('tache', 'blocagesActifs'));
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
        abort_unless($tache->user_id === $r->user()->id || $r->user()->estPilote(), 403);
        $r->validate(['statut' => 'required|in:a_faire,en_cours,bloque,termine,a_valider,reporte']);

        if ($r->statut === 'termine') {
            // T1 (sprint) → passe en a_valider au lieu de termine (sauf si c'est un pilote qui change)
            if ($tache->type === 'T1' && !$r->user()->estPilote()) {
                $tache->update(['statut' => 'a_valider']);
                // Notifier les pilotes
                $pilotes = \App\Models\User::where('actif', true)
                    ->whereIn('role', ['administrateur', 'charge_operations', 'direction'])->get();
                foreach ($pilotes as $p) {
                    \App\Models\NotificationApp::create([
                        'user_id' => $p->id, 'type' => 'validation',
                        'contenu' => $tache->user->nomComplet() . ' demande la validation de « ' . \Illuminate\Support\Str::limit($tache->intitule, 40) . ' »',
                        'lu' => false,
                    ]);
                }
                return response()->json(['ok' => true, 'a_valider' => true,
                    'message' => 'Tâche soumise à validation. Le pilote sera notifié.']);
            }

            // T2/T3 ou pilote → clôture directe
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

    /** Validation définitive par un pilote (tâche T1 en a_valider). */
    public function valider(Request $r, Tache $tache)
    {
        abort_unless($r->user()->estPilote(), 403);
        abort_unless($tache->statut === 'a_valider', 422, 'Cette tâche n\'est pas en attente de validation.');

        $r->validate(['decision' => 'required|in:valider,rejeter', 'motif' => 'nullable|string|max:1000']);

        if ($r->decision === 'valider') {
            $this->cloture->cloturer($tache);

            // Commentaire auto de validation
            $tache->commentaires()->create([
                'user_id' => $r->user()->id,
                'contenu' => '✓ Tâche validée par ' . $r->user()->nomComplet()
                    . ($r->motif ? ' — ' . $r->motif : ''),
            ]);

            // Notifier le collaborateur
            \App\Models\NotificationApp::create([
                'user_id' => $tache->user_id, 'type' => 'validation',
                'contenu' => 'Votre tâche « ' . \Illuminate\Support\Str::limit($tache->intitule, 40) . ' » a été validée.',
                'lu' => false,
            ]);

            $msg = match ($tache->qualification) {
                'a_temps'   => 'Validée à temps.',
                'anticipee' => 'Validée — terminée en avance.',
                'en_retard' => 'Validée — terminée en retard.',
                default     => 'Validée.',
            };
            return back()->with('succes', $msg);
        }

        // Rejet → retour en_cours
        $tache->update(['statut' => 'en_cours']);
        $motif = $r->motif ?? 'Aucun motif précisé';

        $tache->commentaires()->create([
            'user_id' => $r->user()->id,
            'contenu' => '✗ Validation rejetée par ' . $r->user()->nomComplet() . ' — ' . $motif,
        ]);

        \App\Models\NotificationApp::create([
            'user_id' => $tache->user_id, 'type' => 'validation',
            'contenu' => 'Votre tâche « ' . \Illuminate\Support\Str::limit($tache->intitule, 40) . ' » a été rejetée : ' . $motif,
            'lu' => false,
        ]);

        return back()->with('erreur', 'Tâche rejetée — renvoyée en cours.');
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
