<?php
namespace App\Http\Controllers;

use App\Http\Requests\TacheRequest;
use App\Models\{Projet, Sprint, Tache, TypeBlocage, User};
use App\Services\{ClotureService, MailService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TacheController extends Controller
{
    public function __construct(private ClotureService $cloture) {}

    /** US-07 : mes tâches en kanban (défaut) ou liste, filtres projet/type/priorité. */
    public function index(Request $r)
    {
        if ($r->user()?->aRole('direction')) {
            return redirect()->route('dashboard');
        }

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

    /** Vue pilote : toutes les tâches de tous les collaborateurs avec filtres. */
    public function pilote(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);

        $query = Tache::with(['user', 'projet', 'sprint'])
            ->when($r->collaborateur, fn ($q) => $q->where('user_id', $r->collaborateur))
            ->when($r->projet, fn ($q) => $q->where('projet_id', $r->projet))
            ->when($r->statut, fn ($q) => $q->where('statut', $r->statut))
            ->when($r->priorite, fn ($q) => $q->where('priorite', $r->priorite))
            ->when($r->type, fn ($q) => $q->where('type', $r->type))
            ->orderByRaw("FIELD(statut,'a_valider','bloque','en_cours','a_faire','termine')")
            ->orderByRaw("FIELD(priorite,'haute','moyenne','basse')")
            ->orderBy('echeance');

        $taches = $query->paginate(50)->withQueryString();

        return view('taches.pilote', [
            'taches'         => $taches,
            'projets'        => Projet::orderBy('libelle')->get(),
            'collaborateurs' => User::where('actif', true)->whereNotIn('role', ['invite'])->orderBy('name')->get(),
            'filtres'        => $r->only(['collaborateur', 'projet', 'statut', 'priorite', 'type']),
        ]);
    }

    /** Détail tâche : blocages, commentaires, métadonnées. */
    public function show(Request $r, Tache $tache)
    {
        abort_unless((int) $tache->user_id === (int) $r->user()->id || $r->user()->estPilote(), 403);
        $tache->load(['user', 'projet', 'sprint', 'blocages.intervenant', 'commentaires.user']);
        $blocagesActifs = $tache->blocages->whereNull('date_levee')->count();

        // Types de blocage paramétrables
        $typesBlocage = Schema::hasTable('types_blocage')
            ? TypeBlocage::orderBy('libelle')->get()
            : collect();

        // Utilisateurs actifs pour le dropdown intervenant
        $utilisateurs = User::where('actif', true)->orderBy('name')->get(['id', 'name']);

        $projets = Projet::orderBy('libelle')->get();
        return view('taches.show', compact('tache', 'blocagesActifs', 'typesBlocage', 'utilisateurs', 'projets'));
    }

    /** US-06 : création rapide — valeurs par défaut intelligentes. */
    public function store(TacheRequest $r)
    {
        $r->user()->taches()->create($r->validated());
        return back()->with('succes', 'Tâche ajoutée.');
    }

    public function update(TacheRequest $r, Tache $tache)
    {
        abort_unless($r->user()->estPilote(), 403);
        $tache->update($r->validated());

        // Statut modifiable par le pilote (permet rétrogradation d'une tâche terminée)
        if ($r->filled('statut') && in_array($r->statut, ['a_faire', 'en_cours', 'bloque', 'termine', 'reporte'])) {
            if ($r->statut === 'termine' && $tache->statut !== 'termine') {
                $this->cloture->cloturer($tache);
            } else {
                $tache->update(['statut' => $r->statut, 'date_fin_reelle' => $r->statut === 'termine' ? $tache->date_fin_reelle : null]);
            }
        }

        return back()->with('succes', 'Tâche mise à jour.');
    }

    /** Changement de statut (glisser-déposer kanban). Clôture = règles v1.5. */
    public function statut(Request $r, Tache $tache)
    {
        if ((int) $tache->user_id !== (int) $r->user()->id && !$r->user()->estPilote()) {
            return response()->json(['ok' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cette tâche.'], 403);
        }

        // Bloquer si la tâche appartient à un sprint non en_cours
        if ($tache->sprint_id && $tache->sprint?->statut !== 'en_cours') {
            return response()->json(['ok' => false, 'message' => 'Le sprint de cette tâche n\'est pas en cours.'], 422);
        }

        $r->validate(['statut' => 'required|in:a_faire,en_cours,bloque,termine,reporte']);

        // Seul un pilote peut rétrograder une tâche terminée
        if ($tache->statut === 'termine' && $r->statut !== 'termine' && !$r->user()->estPilote()) {
            return response()->json(['ok' => false, 'message' => 'Seul un pilote (DG ou Chargé des opérations) peut rétrograder une tâche terminée.'], 403);
        }

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
                'user_id'  => $r->user()->id,
                'contenu'  => '✓ Tâche validée par ' . $r->user()->nomComplet()
                    . ($r->motif ? ' — ' . $r->motif : ''),
                'contexte' => 'validation',
            ]);

            // Notifier le collaborateur
            \App\Models\NotificationApp::create([
                'user_id' => $tache->user_id, 'type' => 'validation',
                'contenu' => 'Votre tâche « ' . \Illuminate\Support\Str::limit($tache->intitule, 40) . ' » a été validée.',
                'lu' => false,
            ]);
            // E-mail
            $owner = User::find($tache->user_id);
            if ($owner) {
                MailService::notifier($owner, 'Tâche validée',
                    "Votre tâche « {$tache->intitule} » a été validée par " . $r->user()->nomComplet() . ".",
                    route('taches.show', $tache));
            }

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
            'user_id'  => $r->user()->id,
            'contenu'  => '✗ Validation rejetée par ' . $r->user()->nomComplet() . ' — ' . $motif,
            'contexte' => 'rejet',
        ]);

        \App\Models\NotificationApp::create([
            'user_id' => $tache->user_id, 'type' => 'validation',
            'contenu' => 'Votre tâche « ' . \Illuminate\Support\Str::limit($tache->intitule, 40) . ' » a été rejetée : ' . $motif,
            'lu' => false,
        ]);
        // E-mail
        $owner = User::find($tache->user_id);
        if ($owner) {
            MailService::notifier($owner, 'Tâche rejetée',
                "Votre tâche « {$tache->intitule} » a été rejetée par " . $r->user()->nomComplet() . ".\nMotif : {$motif}",
                route('taches.show', $tache));
        }

        return back()->with('erreur', 'Tâche rejetée — renvoyée en cours.');
    }

    /** Suppression d'une tâche — pilotes ou propriétaire. */
    public function destroy(Request $r, Tache $tache)
    {
        abort_unless($r->user()->estPilote(), 403);
        $intitule = $tache->intitule;
        $tache->commentaires()->delete();
        $tache->blocages()->delete();
        $tache->delete();
        return redirect()->route('taches.index')->with('succes', "Tâche « {$intitule} » supprimée.");
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
