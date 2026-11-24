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

        // Affichage : tâches actives (défaut), archives (sprints clôturés, terminées avant l'exercice) ou tout
        $affichage = in_array($r->affichage, ['archives', 'toutes'], true) ? $r->affichage : 'actives';
        $nbArchivees = Tache::where('user_id', $r->user()->id)->archivees()->count();

        $taches = Tache::with(['projet', 'sprint'])
            ->where('user_id', $r->user()->id)
            ->when($affichage === 'actives', fn ($q) => $q->nonArchivees())
            ->when($affichage === 'archives', fn ($q) => $q->archivees())
            ->when($r->projet, fn ($q) => $q->where('projet_id', $r->projet))
            ->when($r->type, fn ($q) => $q->where('type', $r->type))
            ->when($r->priorite, fn ($q) => $q->where('priorite', $r->priorite))
            ->get()
            // Toutes les colonnes : de la date la plus récente à la plus ancienne (date affichée sur la carte),
            // la dernière modification départageant les tâches d'un même jour
            ->sortBy([
                fn ($a, $b) => self::dateActivite($b)->format('Y-m-d') <=> self::dateActivite($a)->format('Y-m-d'),
                fn ($a, $b) => $b->updated_at <=> $a->updated_at,
            ])
            ->groupBy('statut')
            ->map->values();

        // À faire : ordre croissant (échéance la plus proche d'abord) ; les tâches du jour en tête
        // (même définition que la saisie quotidienne), puis les autres
        if (isset($taches['a_faire'])) {
            $taches['a_faire'] = $taches['a_faire']->sortBy([
                fn ($a, $b) => self::dateActivite($a)->format('Y-m-d') <=> self::dateActivite($b)->format('Y-m-d'),
                fn ($a, $b) => $a->id <=> $b->id,
            ])->values();
            if ($affichage !== 'archives') {
                [$duJour, $autres] = $taches['a_faire']->partition(fn ($t) => self::estDuJour($t));
                $taches['a_faire'] = $duJour->concat($autres)->values();
            }
        }

        return view('taches.index', [
            'taches'  => $taches,
            'affichage' => $affichage,
            'nbArchivees' => $nbArchivees,
            'debutExercice' => \App\Services\PeriodeExercice::debut(today()),
            'vue'     => $r->get('vue', 'kanban'),
            'projets' => Projet::orderBy('libelle')->get(),
            'colonnes' => ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'bloque' => 'Bloqué', 'termine' => 'Terminé'],
        ]);
    }

    /** Tâche du jour : commencée, échéance non dépassée, hors sprint ou sprint en cours (T3 : toujours). */
    public static function estDuJour(Tache $t): bool
    {
        if (in_array($t->statut, ['termine', 'reporte'], true)) return false;
        if ($t->sprint_id && $t->sprint?->statut !== 'en_cours') return false;
        if ($t->type === 'T3') return true;
        return $t->date_debut && $t->date_debut->lte(today()) && (!$t->echeance || $t->echeance->gte(today()));
    }

    /** Date d'une activité dans « Mes tâches » : fin réelle si terminée, sinon échéance, début ou création. */
    public static function dateActivite(Tache $t): \Carbon\CarbonInterface
    {
        return $t->statut === 'termine'
            ? ($t->date_fin_reelle ?? $t->updated_at)
            : ($t->echeance ?? $t->date_debut ?? $t->created_at);
    }

    /** Vue pilote : toutes les tâches de tous les collaborateurs, filtrées (période, personne, projet, sprint, statut…). */
    public function pilote(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);

        // Période : du … au …, appliquée selon le critère choisi
        $du = $r->filled('du') ? \Carbon\Carbon::parse($r->du)->toDateString() : null;
        $au = $r->filled('au') ? \Carbon\Carbon::parse($r->au)->toDateString() : null;
        if ($du && $au && $du > $au) [$du, $au] = [$au, $du];
        $critere = in_array($r->critere, ['active', 'echeance', 'debut', 'achevee'], true) ? $r->critere : 'active';

        $base = Tache::query()
            ->when($r->collaborateur, fn ($q) => $q->where('user_id', $r->collaborateur))
            ->when($r->projet, fn ($q) => $q->where('projet_id', $r->projet))
            ->when($r->sprint, fn ($q) => $q->where('sprint_id', $r->sprint))
            ->when($r->priorite, fn ($q) => $q->where('priorite', $r->priorite))
            ->when($r->type, fn ($q) => $q->where('type', $r->type))
            ->when($r->boolean('ajouts'), fn ($q) => $q->whereNotNull('ajoutee_en_cours_le'))
            ->when(trim((string) $r->q) !== '', fn ($q) => $q->where('intitule', 'like', '%' . trim($r->q) . '%'))
            ->when($du || $au, function ($q) use ($du, $au, $critere) {
                $du ??= '1900-01-01';
                $au ??= '2999-12-31';
                match ($critere) {
                    'echeance' => $q->whereBetween('echeance', [$du, $au]),
                    'debut'    => $q->whereBetween('date_debut', [$du, $au]),
                    'achevee'  => $q->where('statut', 'termine')->whereBetween('date_fin_reelle', [$du, $au]),
                    // Active sur la période : son intervalle début → échéance (ou fin réelle) chevauche la période
                    default    => $q->whereRaw('COALESCE(date_debut, echeance, DATE(created_at)) <= ?', [$au])
                        ->whereRaw('COALESCE(date_fin_reelle, echeance, date_debut, DATE(created_at)) >= ?', [$du]),
                };
            });

        // Compteurs par statut (avant le filtre de statut), pour les puces cliquables
        $compteurs = (clone $base)->selectRaw('statut, COUNT(*) n')->groupBy('statut')->pluck('n', 'statut');
        $compteurs['en_retard'] = (clone $base)->enRetard()->count();
        $compteurs['tout'] = $compteurs->except('en_retard')->sum();

        $tri = in_array($r->tri, ['echeance', 'debut', 'collaborateur', 'projet', 'recent'], true) ? $r->tri : 'statut';
        $query = (clone $base)->with(['user', 'projet', 'sprint', 'userStory:id,numero'])
            ->when($r->statut, fn ($q) => $r->statut === 'en_retard' ? $q->enRetard() : $q->where('statut', $r->statut));
        match ($tri) {
            'echeance'      => $query->orderByRaw('echeance IS NULL')->orderBy('echeance'),
            'debut'         => $query->orderByRaw('date_debut IS NULL')->orderByDesc('date_debut'),
            'collaborateur' => $query->orderBy(User::select('name')->whereColumn('users.id', 'taches.user_id'))->orderBy('echeance'),
            'projet'        => $query->orderBy(Projet::select('code')->whereColumn('projets.id', 'taches.projet_id'))->orderBy('echeance'),
            'recent'        => $query->orderByDesc('updated_at'),
            default         => $query->orderByRaw("FIELD(statut,'a_valider','bloque','en_cours','a_faire','termine')")
                ->orderByRaw("FIELD(priorite,'haute','moyenne','basse')")->orderBy('echeance'),
        };

        $parPage = in_array((int) $r->par_page, [25, 50, 100, 200], true) ? (int) $r->par_page : 50;
        $taches = $query->paginate($parPage)->withQueryString();

        return view('taches.pilote', [
            'taches'         => $taches,
            'compteurs'      => $compteurs,
            'projets'        => Projet::orderBy('libelle')->get(),
            'sprints'        => $r->projet ? Sprint::where('projet_id', $r->projet)->orderByDesc('numero')->get(['id', 'numero', 'statut']) : collect(),
            'collaborateurs' => User::whereNotIn('role', ['invite'])->orderByDesc('actif')->orderBy('name')->get(),
            'filtres'        => array_filter($r->only(['collaborateur', 'projet', 'sprint', 'statut', 'priorite', 'type', 'q', 'du', 'au', 'ajouts'])),
            'du' => $du, 'au' => $au, 'critere' => $critere, 'tri' => $tri, 'parPage' => $parPage,
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

    /** US-06 : création rapide - valeurs par défaut intelligentes. */
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
                'a_temps'   => 'Tâche terminée à temps - bravo.',
                'anticipee' => 'Terminée en avance : un mot sur la raison ? (aide à calibrer nos estimations)',
                'en_retard' => 'Terminée après l\'échéance : merci d\'ajouter une justification en commentaire.',
                default     => 'Tâche terminée.',
            };
            if ($tache->effort_volontaire) $msg .= ' Effort hors jour travaillé (repos/férié) comptabilisé.';
            return response()->json(['ok' => true, 'qualification' => $tache->qualification,
                'justification_requise' => $tache->justification_requise, 'message' => $msg]);
        }

        $tache->update(['statut' => $r->statut]);
        return response()->json(['ok' => true, 'message' => 'Statut mis à jour.']);
    }

    /**
     * Mes activités : une même opération sur plusieurs tâches (démarrer, terminer, remettre à faire, bloquer, commenter).
     * Chaque tâche suit les règles du glisser-déposer ; celles qui n'y satisfont pas sont écartées avec leur motif.
     */
    public function groupe(Request $r)
    {
        $u = $r->user();
        $v = $r->validate([
            'ids' => 'required|array|min:1|max:200', 'ids.*' => 'integer',
            'action' => 'required|in:demarrer,terminer,a_faire,bloquer,commenter',
            'texte' => 'required_if:action,bloquer,commenter|nullable|string|max:2000',
        ], [
            'ids.required' => 'Sélectionnez au moins une tâche.',
            'texte.required_if' => "Indiquez le motif du blocage ou le texte du commentaire.",
        ]);
        $cible = ['demarrer' => 'en_cours', 'terminer' => 'termine', 'a_faire' => 'a_faire', 'bloquer' => 'bloque'][$v['action']] ?? null;
        $texte = trim((string) ($v['texte'] ?? ''));
        $faites = 0; $ecartees = []; $retards = 0; $bloquees = collect();

        foreach (Tache::with('sprint')->whereIn('id', array_unique($v['ids']))->get() as $t) {
            $nom = \Illuminate\Support\Str::limit($t->intitule, 40);
            if ((int) $t->user_id !== (int) $u->id && !$u->estPilote()) { $ecartees[] = "{$nom} : tâche d'un autre collaborateur"; continue; }
            if ($v['action'] === 'commenter') {
                $t->commentaires()->create(['user_id' => $u->id, 'contenu' => $texte, 'contexte' => 'suivi']);
                $faites++;
                continue;
            }
            if ($t->sprint_id && $t->sprint?->statut !== 'en_cours' && !($t->sprint?->enRectification() && $u->peutRectifier($t->sprint->projet))) { $ecartees[] = "{$nom} : sprint " . ($t->sprint?->statut === 'termine' ? 'clôturé' : 'non démarré'); continue; }
            if ($t->statut === $cible) continue;
            if ($t->statut === 'termine' && !$u->estPilote()) { $ecartees[] = "{$nom} : déjà terminée (seul un pilote peut la rétrograder)"; continue; }
            if ($cible === 'termine') {
                $this->cloture->cloturer($t);
                if ($t->justification_requise && $t->qualification === 'en_retard') $retards++;
            } elseif ($cible === 'bloque') {
                $t->blocages()->create(['motif' => $texte, 'type' => 'technique', 'date_blocage' => now()]);
                $t->update(['statut' => 'bloque']);
                $bloquees->push($t);
            } else {
                $t->update(['statut' => $cible]);
            }
            $faites++;
        }

        // Un blocage groupé : les pilotes sont prévenus une fois
        if ($bloquees->isNotEmpty() && Schema::hasTable('notifications_app')) {
            foreach (User::where('actif', true)->whereIn('role', ['administrateur', 'charge_operations', 'direction'])->where('id', '!=', $u->id)->get() as $p) {
                \App\Models\NotificationApp::create(['user_id' => $p->id, 'type' => 'blocage', 'lu' => false,
                    'contenu' => $u->nomComplet() . ' a signalé un blocage sur ' . $bloquees->count() . ' tâche(s) : ' . \Illuminate\Support\Str::limit($texte, 80),
                    'lien' => route('taches.pilote', ['statut' => 'bloque'])]);
            }
        }

        $libelle = ['demarrer' => 'démarrée(s)', 'terminer' => 'terminée(s)', 'a_faire' => 'remise(s) à faire', 'bloquer' => 'bloquée(s)', 'commenter' => 'commentée(s)'][$v['action']];
        $msg = ($faites ? "{$faites} tâche(s) {$libelle}." : 'Aucune tâche modifiée.')
            . ($retards ? " {$retards} terminée(s) après l'échéance : ajoutez une justification dans leur journal." : '')
            . ($v['action'] === 'commenter' && $faites ? ' Le commentaire figure dans le journal de chaque tâche (repris dans le rapport de review).' : '');
        return redirect()->route('taches.index', $r->only(['affichage', 'projet', 'type']))
            ->with($faites ? 'succes' : 'erreur', $msg)->with('ecartees', $ecartees);
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
                    . ($r->motif ? ' - ' . $r->motif : ''),
                'contexte' => 'validation',
            ]);

            // Notifier le collaborateur
            \App\Models\NotificationApp::create([
                'user_id' => $tache->user_id, 'type' => 'tache',
                'contenu' => 'Votre tâche « ' . \Illuminate\Support\Str::limit($tache->intitule, 40) . ' » a été validée.',
                'lien' => route('taches.show', $tache),
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
                'anticipee' => 'Validée - terminée en avance.',
                'en_retard' => 'Validée - terminée en retard.',
                default     => 'Validée.',
            };
            return back()->with('succes', $msg);
        }

        // Rejet → retour en_cours
        $tache->update(['statut' => 'en_cours']);
        $motif = $r->motif ?? 'Aucun motif précisé';

        $tache->commentaires()->create([
            'user_id'  => $r->user()->id,
            'contenu'  => '✗ Validation rejetée par ' . $r->user()->nomComplet() . ' - ' . $motif,
            'contexte' => 'rejet',
        ]);

        \App\Models\NotificationApp::create([
            'user_id' => $tache->user_id, 'type' => 'tache',
            'contenu' => 'Votre tâche « ' . \Illuminate\Support\Str::limit($tache->intitule, 40) . ' » a été rejetée : ' . $motif,
            'lien' => route('taches.show', $tache),
            'lu' => false,
        ]);
        // E-mail
        $owner = User::find($tache->user_id);
        if ($owner) {
            MailService::notifier($owner, 'Tâche rejetée',
                "Votre tâche « {$tache->intitule} » a été rejetée par " . $r->user()->nomComplet() . ".\nMotif : {$motif}",
                route('taches.show', $tache));
        }

        return back()->with('erreur', 'Tâche rejetée - renvoyée en cours.');
    }

    /** Suppression d'une tâche - pilotes ou propriétaire. */
    public function destroy(Request $r, Tache $tache)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        $intitule = $tache->intitule;
        $tache->commentaires()->delete();
        $tache->blocages()->delete();
        $tache->delete();
        return redirect()->route('taches.index')->with('succes', "Tâche « {$intitule} » supprimée.");
    }

    /** Duplication de la veille (US-08 - anticipée ici tant la demande est fréquente). */
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
