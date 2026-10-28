<?php
namespace App\Http\Controllers;

use App\Models\{NotificationApp, Projet, Sprint, Tache, User, UserStory};
use App\Services\{ClotureService, LecteurXlsx};
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();
        // IDs des projets dont ce collaborateur est responsable (délégation sprint)
        $projetsGeres = $user->aRole('collaborateur')
            ? Projet::where('responsable_projet_id', $user->id)->pluck('id')->all()
            : [];

        $projets = Projet::with(['sprints' => function ($q) use ($user, $projetsGeres) {
            $q->orderByDesc('numero');
            if ($user->aRole('collaborateur')) {
                // Le responsable projet voit tous les sprints de ses projets, les autres seulement les visibles
                $q->where(function ($sub) use ($projetsGeres) {
                    $sub->where('visible_collaborateurs', true);
                    if (!empty($projetsGeres)) {
                        $sub->orWhereIn('projet_id', $projetsGeres);
                    }
                });
            }
        }, 'sprints.taches', 'sprints.userStories', 'responsable'])
            ->orderBy('libelle')->get();

        // Filtrage per-collaborateur (exclusions individuelles) - sauf responsable projet
        if ($user->aRole('collaborateur')) {
            foreach ($projets as $p) {
                $estResponsable = $p->responsable_projet_id === $user->id;
                if (!$estResponsable) {
                    $p->setRelation('sprints', $p->sprints->filter(fn($s) => $s->visiblePour($user->id))->values());
                }
            }
        }

        $collaborateurs = $user->estAdmin() ? User::where('actif', true)->where('role', 'collaborateur')->orderBy('name')->get() : collect();
        // Espaces projets accessibles (y compris projets sans sprint : le backlog se prépare avant le 1er sprint)
        $espaces = $projets->filter(fn($p) => $p->estVisiblePour($user))->values();
        return view('sprints.index', compact('projets', 'collaborateurs', 'espaces'));
    }

    /** Page détail d'un sprint : tâches, import, ajout. */
    public function show(Request $r, Sprint $sprint)
    {
        $user = $r->user();
        // Collaborateur ne peut pas voir un sprint masqué ou dont il est exclu
        // Sauf s'il est responsable du projet (délégation)
        $estResponsable = $sprint->projet && $sprint->projet->responsable_projet_id === $user->id;
        if ($user->aRole('collaborateur') && !$estResponsable && !$sprint->visiblePour($user->id)) {
            abort(403, 'Ce sprint n\'est pas visible pour vous.');
        }
        $sprint->load(['projet.responsable', 'taches.user', 'taches.userStory', 'userStories.taches']);
        $users = User::where('actif', true)->orderBy('name')->get();
        $canEdit = $user->peutGererProjet($sprint->projet_id);
        $canEditSprint = $user->estPilote(); // seuls admin, charge_ops et direction modifient sprint/tâches

        // Sprint Planning : stories du sprint et stories du backlog encore disponibles
        $storiesSprint = UserStory::trierParNumero($sprint->userStories)->values();
        $backlogDispo = UserStory::trierParNumero(
            UserStory::where('projet_id', $sprint->projet_id)->where('statut', '!=', 'terminee')->whereNotIn('id', $storiesSprint->pluck('id'))->get()
        )->values();
        // Équipe du sprint (dynamique) : proposée en priorité pour l'attribution des tâches
        $equipe = \App\Services\ScrumService::membres($sprint);
        $retiresSprint = \App\Models\SprintEquipe::with(['user', 'par'])->where('sprint_id', $sprint->id)->where('statut', 'retire')->get();
        $rolesProjet = $sprint->projet->collaborateurs()->get()->keyBy('user_id');
        $voitProjet = $sprint->projet->estVisiblePour($user);

        return view('sprints.show', compact('sprint', 'users', 'canEdit', 'canEditSprint', 'storiesSprint', 'backlogDispo', 'equipe', 'retiresSprint', 'rolesProjet', 'voitProjet'));
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->peutGererProjet($r->input('projet_id')), 403);
        $r->validate([
            'projet_id' => 'required|exists:projets,id',
            'numero' => 'required|integer|min:1',
            'objectif' => 'required|string|max:1000',
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
        abort_unless($r->user()->estPilote(), 403);
        $r->validate([
            'objectif' => 'required|string|max:1000',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'engagement_points' => 'nullable|integer|min:1',
        ]);
        $sprint->update($r->only(['objectif', 'date_debut', 'date_fin', 'engagement_points']));
        // Dates modifiées : l'arbitrage de période ne vaut plus si la période retenue n'est plus couverte
        if ($sprint->periode_exercice && !$sprint->periodesCouvertes()->contains(fn($p) => $p->isSameDay($sprint->periode_exercice))) {
            $sprint->update(['periode_exercice' => null, 'periode_arbitree_par' => null, 'periode_arbitree_le' => null]);
        }
        return back()->with('succes', 'Sprint mis à jour.');
    }

    /** Démarrer un sprint planifié. */
    public function demarrer(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        if ($sprint->statut !== 'planifie') return back()->with('erreur', 'Ce sprint n\'est pas en statut planifié.');

        // Vérifier qu'il y a au moins une tâche
        if ($sprint->taches()->count() === 0) {
            return back()->with('erreur', 'Impossible de démarrer un sprint sans tâche. Ajoutez au moins une tâche.');
        }

        // Vérifier qu'aucun autre sprint du même projet n'est en cours
        $autreEnCours = Sprint::where('projet_id', $sprint->projet_id)
            ->where('id', '!=', $sprint->id)
            ->where('statut', 'en_cours')->exists();
        if ($autreEnCours) {
            return back()->with('erreur', 'Un autre sprint est déjà en cours sur ce projet. Clôturez-le d\'abord.');
        }

        $sprint->update(['statut' => 'en_cours']);

        // Passer le projet en "actif" s'il ne l'est pas déjà
        $projet = $sprint->projet;
        if ($projet && $projet->statut !== 'actif') {
            $projet->update(['statut' => 'actif']);
        }

        // Notifier tous les collaborateurs assignés
        $userIds = $sprint->taches()->whereNotNull('user_id')->pluck('user_id')->unique();
        foreach ($userIds as $uid) {
            NotificationApp::create([
                'user_id' => $uid,
                'type' => 'sprint_demarre',
                'contenu' => "Le sprint {$sprint->numero} du projet {$sprint->projet?->libelle} a démarré. Vos tâches sont maintenant actives.",
            ]);
        }

        // Notifier le responsable projet
        if ($sprint->projet?->responsable_projet_id && !$userIds->contains($sprint->projet->responsable_projet_id)) {
            NotificationApp::create([
                'user_id' => $sprint->projet->responsable_projet_id,
                'type' => 'sprint_demarre',
                'contenu' => "Le sprint {$sprint->numero} a démarré - {$sprint->taches()->count()} tâche(s).",
            ]);
        }

        return back()->with('succes', "Sprint {$sprint->numero} démarré. {$userIds->count()} collaborateur(s) notifié(s).");
    }

    /** Clôturer un sprint : qualifier toutes les tâches et passer en terminé. */
    public function cloturer(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        if ($sprint->statut === 'termine') return back()->with('erreur', 'Ce sprint est déjà clôturé.');

        $cloture = new ClotureService();
        $taches = $sprint->taches()->whereIn('statut', ['a_faire', 'en_cours', 'bloque', 'termine'])->get();

        $stats = ['a_temps' => 0, 'en_retard' => 0, 'anticipee' => 0, 'non_termine' => 0, 'total' => $taches->count()];

        foreach ($taches as $t) {
            if ($t->statut === 'termine') {
                // Qualifier si pas encore fait
                if (!$t->qualification) {
                    $cloture->cloturer($t, $t->date_fin_reelle);
                }
                $stats[$t->qualification] = ($stats[$t->qualification] ?? 0) + 1;
            } else {
                // Tâche non terminée à la clôture → marquée en retard
                $t->update([
                    'qualification' => 'non_termine_sprint',
                    'justification_requise' => true,
                ]);
                $stats['non_termine']++;
            }
        }

        $sprint->update(['statut' => 'termine']);

        // Résumé
        $pointsLivres = $sprint->pointsLivres();
        $engagement = $sprint->pointsEngages();
        $velocite = $engagement > 0 ? round(($pointsLivres / $engagement) * 100) : 0;

        // Notifier le responsable projet
        if ($sprint->projet?->responsable_projet_id) {
            NotificationApp::create([
                'user_id' => $sprint->projet->responsable_projet_id,
                'type' => 'sprint_cloture',
                'contenu' => "Sprint {$sprint->numero} clôturé - {$stats['a_temps']} à temps, {$stats['en_retard']} en retard, {$stats['non_termine']} non terminée(s). Vélocité : {$velocite}%.",
            ]);
        }

        return back()->with('succes', "Sprint clôturé. {$pointsLivres}/{$engagement} pts livrés. {$stats['a_temps']} à temps, {$stats['en_retard']} en retard, {$stats['non_termine']} non terminée(s).");
    }

    /** Arrêter un sprint en cours (avec justification obligatoire). */
    public function arreter(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        if ($sprint->statut !== 'en_cours') return back()->with('erreur', 'Ce sprint n\'est pas en cours.');

        $r->validate(['justification' => 'required|string|max:1000']);

        $sprint->update([
            'statut' => 'termine',
            'justification_arret' => $r->justification,
            'arrete_le' => now(),
        ]);

        // Notifier le responsable projet
        if ($sprint->projet?->responsable_projet_id) {
            NotificationApp::create([
                'user_id' => $sprint->projet->responsable_projet_id,
                'type' => 'sprint_arret',
                'contenu' => "Sprint {$sprint->numero} arrêté par " . $r->user()->nomComplet() . ". Motif : {$r->justification}",
            ]);
        }

        return back()->with('succes', "Sprint arrêté. Motif enregistré.");
    }

    /** Arbitrage de la période d'exercice d'un sprint à cheval sur deux périodes (pilote). */
    public function arbitrerPeriode(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $choix = $sprint->periodesCouvertes()->map->toDateString()->all();
        $v = $r->validate(['periode_exercice' => ['required', 'date', \Illuminate\Validation\Rule::in($choix)]],
            ['periode_exercice.in' => "Choisissez l'une des périodes d'exercice couvertes par le sprint."]);
        $sprint->update(['periode_exercice' => $v['periode_exercice'], 'periode_arbitree_par' => $r->user()->id, 'periode_arbitree_le' => now()]);
        $nom = \App\Services\PeriodeExercice::nom($v['periode_exercice']);
        \App\Models\AuditLog::log('arbitrage_periode', 'sprints', "Sprint #{$sprint->numero}", $nom . ' (' . \App\Services\PeriodeExercice::libelle($v['periode_exercice']) . ')');
        return back()->with('succes', "Sprint {$sprint->numero} rattaché à l'" . mb_strtolower($nom) . '.');
    }

    /** Basculer la visibilité globale d'un sprint pour les collaborateurs. */
    public function toggleVisibilite(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $sprint->update(['visible_collaborateurs' => !$sprint->visible_collaborateurs]);
        $etat = $sprint->visible_collaborateurs ? 'visible' : 'masqué';
        \App\Models\AuditLog::log('visibilite_sprint', 'sprints', "Sprint #{$sprint->numero}", "Sprint {$etat} pour les collaborateurs");
        return back()->with('succes', "Sprint n°{$sprint->numero} désormais {$etat} pour les collaborateurs.");
    }

    /** Mettre à jour la liste d'exclusions par collaborateur pour un sprint. */
    public function updateExclusions(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $exclus = $r->input('exclus', []);
        $exclus = array_map('intval', array_filter($exclus));
        $sprint->update(['collaborateurs_exclus' => json_encode(array_values($exclus))]);
        $nb = count($exclus);
        $label = $nb > 0 ? "{$nb} collaborateur(s) exclu(s)" : 'Aucune exclusion';
        \App\Models\AuditLog::log('exclusion_sprint', 'sprints', "Sprint #{$sprint->numero}", $label);
        return back()->with('succes', "Sprint n°{$sprint->numero} : {$label}.");
    }

    /** API JSON : sprints d'un projet (pour le select dynamique T1). */
    public function parProjet(Projet $projet)
    {
        return response()->json(
            $projet->sprints()->whereIn('statut', ['planifie', 'en_cours'])->orderBy('numero')->get(['id', 'numero', 'objectif', 'statut'])
        );
    }

    /** Ajouter une tâche directement au sprint. */
    public function ajouterTache(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        $r->validate([
            'intitule' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'priorite' => 'required|in:haute,moyenne,basse',
            'date_debut' => 'nullable|date',
            'echeance' => 'nullable|date|after_or_equal:date_debut',
            'points' => 'nullable|integer|min:1',
            'user_story_id' => ['nullable', \Illuminate\Validation\Rule::exists('user_stories', 'id')->where('projet_id', $sprint->projet_id)],
        ]);

        Tache::create([
            'sprint_id' => $sprint->id,
            'projet_id' => $sprint->projet_id,
            'user_id' => $r->user_id,
            'intitule' => $r->intitule,
            'type' => 'T1',
            'statut' => 'a_faire',
            'priorite' => $r->priorite,
            'date_debut' => $r->date_debut ?? today(),
            'echeance' => $r->echeance,
            'points' => $r->points,
            'user_story_id' => $r->user_story_id,
        ]);
        if ($r->user_story_id) $sprint->userStories()->syncWithoutDetaching([$r->user_story_id]);

        return back()->with('succes', 'Tâche ajoutée au sprint.');
    }

    /** Modifier une tâche existante du sprint. */
    public function modifierTache(Request $r, Sprint $sprint, Tache $tache)
    {
        abort_unless($r->user()->estPilote(), 403);
        abort_unless($tache->sprint_id === $sprint->id, 404);

        $r->validate([
            'intitule' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'priorite' => 'required|in:haute,moyenne,basse',
            'date_debut' => 'nullable|date',
            'echeance' => 'nullable|date|after_or_equal:date_debut',
            'points' => 'nullable|integer|min:1',
            'user_story_id' => ['nullable', \Illuminate\Validation\Rule::exists('user_stories', 'id')->where('projet_id', $sprint->projet_id)],
        ]);

        $tache->update($r->only(['intitule', 'user_id', 'priorite', 'date_debut', 'echeance', 'points', 'user_story_id']));
        if ($r->user_story_id) $sprint->userStories()->syncWithoutDetaching([$r->user_story_id]);

        return back()->with('succes', 'Tâche mise à jour.');
    }

    /* ═══════ SPRINT PLANNING : user stories du sprint ═══════ */

    /** Ajouter des user stories du Product Backlog au Sprint Backlog. */
    public function attacherUserStories(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        $r->validate([
            'user_stories' => 'required|array|min:1',
            'user_stories.*' => [\Illuminate\Validation\Rule::exists('user_stories', 'id')->where('projet_id', $sprint->projet_id)],
        ], ['user_stories.required' => 'Sélectionnez au moins une user story.']);
        $sprint->userStories()->syncWithoutDetaching($r->user_stories);
        UserStory::whereIn('id', $r->user_stories)->where('statut', 'a_faire')->update(['statut' => 'planifiee']);
        return back()->with('succes', count($r->user_stories) . ' user story(s) ajoutée(s) au Sprint Backlog.');
    }

    /** Avancement d'une user story dans le sprint (reporté sur le backlog). */
    public function statutUserStory(Request $r, Sprint $sprint, UserStory $userStory)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        $r->validate(['statut_sprint' => ['required', \Illuminate\Validation\Rule::in(array_keys(UserStory::STATUTS_SPRINT))]]);
        abort_unless($sprint->userStories()->whereKey($userStory->id)->exists(), 404);
        $sprint->userStories()->updateExistingPivot($userStory->id, ['statut_sprint' => $r->statut_sprint]);
        $userStory->update(['statut' => ['a_faire' => 'planifiee', 'en_cours' => 'en_cours', 'termine' => 'terminee'][$r->statut_sprint]]);
        return back()->with('succes', "{$userStory->numero} : " . UserStory::STATUTS_SPRINT[$r->statut_sprint] . '.');
    }

    /** Sprint Planning : story points et tâches techniques d'une user story du sprint. */
    public function planifierUserStory(Request $r, Sprint $sprint, UserStory $userStory)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        abort_unless($sprint->userStories()->whereKey($userStory->id)->exists(), 404);
        $v = $r->validate([
            'effort_estime' => ['sometimes', 'nullable', 'integer', \Illuminate\Validation\Rule::in(UserStory::POINTS)],
            'taches_techniques' => 'sometimes|nullable|string|max:20000',
        ], ['effort_estime.in' => 'Story points autorisés : ' . implode(', ', UserStory::POINTS) . '.']);
        if (array_key_exists('taches_techniques', $v)) $v['taches_techniques'] = \App\Services\TexteRiche::nettoyer($v['taches_techniques']);
        $userStory->update($v);
        \App\Models\AuditLog::log('planif_user_story', 'sprints', "Sprint {$sprint->numero}", "{$userStory->numero} : " . ($userStory->effort_estime ? $userStory->effort_estime . ' pt(s)' : 'non estimée'));
        if (!array_key_exists('effort_estime', $v)) return back()->with('succes', "{$userStory->numero} : tâches techniques mises à jour.");
        return back()->with('succes', "{$userStory->numero} : " . ($userStory->effort_estime ? $userStory->effort_estime . ' story point(s) - ' . $userStory->complexiteStory()['libelle'] : 'estimation retirée') . '.');
    }

    /** Retirer une user story du sprint : elle retourne au backlog, ses tâches restent dans le sprint. */
    public function detacherUserStory(Request $r, Sprint $sprint, UserStory $userStory)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        $sprint->userStories()->detach($userStory->id);
        if (!$userStory->sprints()->exists() && $userStory->statut === 'planifiee') $userStory->update(['statut' => 'a_faire']);
        return back()->with('succes', "{$userStory->numero} retirée du sprint (retour au backlog).");
    }

    /** Terminé → Terminé testé (TT), et inversement. */
    public function basculerTeste(Request $r, Sprint $sprint, Tache $tache)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        abort_unless($tache->sprint_id === $sprint->id, 404);
        if ($tache->statut !== 'termine') return back()->with('erreur', 'Seule une tâche terminée peut être marquée comme testée.');
        $tache->update(['teste' => !$tache->teste]);
        return back()->with('succes', "« {$tache->intitule} » : " . ($tache->teste ? 'terminée et testée (TT).' : 'test annulé (T).'));
    }

    /** Supprimer une tâche du sprint. */
    public function supprimerTache(Request $r, Sprint $sprint, Tache $tache)
    {
        abort_unless($r->user()->estPilote(), 403);
        abort_unless($tache->sprint_id === $sprint->id, 404);
        $r->validate(['motif_suppression' => 'required|string|min:5']);

        $intitule = $tache->intitule;
        $tache->delete();

        return back()->with('succes', "Tâche « {$intitule} » supprimée.");
    }

    /** Supprimer un sprint et toutes ses tâches. */
    public function destroy(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);

        if ($sprint->statut === 'en_cours') {
            return back()->with('erreur', 'Impossible de supprimer un sprint en cours. Arrêtez-le d\'abord.');
        }

        $numero = $sprint->numero;
        $projetLib = $sprint->projet?->libelle ?? '';

        // Supprimer les tâches rattachées
        $sprint->taches()->delete();
        $sprint->delete();

        return redirect()->route('sprints.index')->with('succes', "Sprint {$numero} ({$projetLib}) supprimé.");
    }

    /** Import xlsx de tâches rattachées à un projet/sprint. */
    public function importerTaches(Request $r)
    {
        abort_unless($r->user()->peutGererProjet(), 403);

        // Vérifier le fichier
        if (!$r->hasFile('fichier')) {
            return back()->with('erreur', 'Aucun fichier sélectionné.');
        }

        $fichier = $r->file('fichier');
        $ext = strtolower($fichier->getClientOriginalExtension());
        if ($ext !== 'xlsx') {
            return back()->with('erreur', 'Format invalide. Seuls les fichiers .xlsx sont acceptés.');
        }

        try {
            $lecteur = new LecteurXlsx($fichier->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Impossible de lire le fichier Excel : ' . $e->getMessage());
        }

        try {
            // Détecter la ligne d'en-tête
            $brut = $lecteur->lire(1);
            if (empty($brut)) {
                return back()->with('erreur', 'Le fichier est vide.');
            }

            $ligneEntete = 1;
            foreach ($brut as $i => $row) {
                foreach ($row as $cell) {
                    if (is_string($cell) && preg_match('/intitul|tâche|tache/iu', $cell)) {
                        $ligneEntete = $i + 1;
                        break 2;
                    }
                }
            }

            $data = $lecteur->lire(1, $ligneEntete);
            if (empty($data)) {
                return back()->with('erreur', 'Aucune ligne de données trouvée après l\'en-tête.');
            }

            $importees = 0;
            $ignores = 0;
            $erreurs = [];

            foreach ($data as $idx => $row) {
                $numLigne = $idx + $ligneEntete + 1;

                try {
                    $intitule = trim($this->chercher($row, ['intitulé', 'intitule', 'tâche', 'tache', 'libellé', 'libelle']) ?? '');
                    if (empty($intitule)) { $ignores++; continue; }

                    $projetStr = trim($this->chercher($row, ['projet']) ?? '');
                    $assigneStr = trim($this->chercher($row, ['assigné', 'assigne', 'assigné_à', 'assigne_a', 'assigné à', 'collaborateur', 'responsable']) ?? '');
                    $dateDebutStr = trim($this->chercher($row, ['date_début', 'date_debut', 'date début', 'début', 'debut', 'date', 'début_prévu', 'debut_prevu']) ?? '');
                    $echeanceStr = trim($this->chercher($row, ['échéance', 'echeance', 'date_fin', 'date fin', 'fin', 'deadline', 'date_échéance', 'date_echeance']) ?? '');

                    // Champs obligatoires : assigné et échéance (date début optionnelle, défaut = aujourd'hui)
                    $manquants = [];
                    if (empty($assigneStr)) $manquants[] = 'Assigné à';
                    if (empty($echeanceStr)) $manquants[] = 'Échéance';
                    if (!empty($manquants)) {
                        $erreurs[] = "Ligne {$numLigne} : champ(s) obligatoire(s) manquant(s) - " . implode(', ', $manquants);
                        $ignores++;
                        continue;
                    }
                    $priorite = trim($this->chercher($row, ['priorité', 'priorite']) ?? '');
                    $points = trim($this->chercher($row, ['points', 'story_points']) ?? '');

                    // Trouver le projet
                    $projetId = null;
                    if ($projetStr) {
                        $projet = Projet::whereRaw('LOWER(libelle) = ?', [mb_strtolower($projetStr)])->first();
                        if (!$projet) {
                            $projet = Projet::create([
                                'code' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::slug($projetStr, '_')),
                                'libelle' => $projetStr,
                                'statut' => 'actif',
                            ]);
                        }
                        $projetId = $projet->id;
                    }

                    // Sprint rattaché → utiliser son projet_id si pas de projet dans le fichier
                    $sprintId = $r->sprint_id ?: null;
                    if ($sprintId && !$projetId) {
                        $sprint = Sprint::find($sprintId);
                        $projetId = $sprint?->projet_id;
                    }

                    // Trouver le collaborateur assigné
                    $userId = $r->user()->id;
                    if ($assigneStr) {
                        $parts = preg_split('/\s+/', $assigneStr, 2);
                        $nom = mb_strtolower($parts[0]);
                        $prenom = isset($parts[1]) ? mb_strtolower($parts[1]) : null;
                        $user = User::whereRaw('LOWER(name) = ?', [$nom])->first();
                        if (!$user && $prenom) {
                            $user = User::whereRaw('LOWER(prenom) = ?', [$prenom])->first();
                        }
                        if ($user) $userId = $user->id;
                    }

                    // Mapper priorité
                    $prioMapped = match (mb_strtolower($priorite)) {
                        'haute', 'élevée', 'high', 'elevee' => 'haute',
                        'basse', 'faible', 'low' => 'basse',
                        default => 'moyenne',
                    };

                    // Parser les dates (gère les dates texte ET les nombres sériels Excel)
                    $dateDebut = $this->parserDate($dateDebutStr);
                    $echeance = $this->parserDate($echeanceStr);

                    Tache::create([
                        'user_id' => $userId,
                        'projet_id' => $projetId,
                        'sprint_id' => $sprintId,
                        'intitule' => $intitule,
                        'type' => $sprintId ? 'T1' : 'T2',
                        'statut' => 'a_faire',
                        'priorite' => $prioMapped,
                        'date_debut' => $dateDebut ?? today()->format('Y-m-d'),
                        'echeance' => $echeance,
                        'points' => is_numeric($points) ? (int) $points : null,
                    ]);
                    $importees++;
                } catch (\Throwable $e) {
                    $erreurs[] = "Ligne {$numLigne} : {$e->getMessage()}";
                    $ignores++;
                    if (count($erreurs) >= 5) break;
                }
            }

            $msg = "{$importees} tâche(s) importée(s)";
            if ($ignores > 0) $msg .= ", {$ignores} ignorée(s)";
            if (!empty($erreurs)) {
                $msg .= '. Erreurs : ' . implode(' | ', array_slice($erreurs, 0, 3));
                return back()->with('erreur', $msg);
            }
            return back()->with('succes', $msg . '.');
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }

    /** Cherche une valeur dans un tableau associatif par clés possibles (accent-insensitive). */
    private function chercher(array $row, array $cles): ?string
    {
        // Normaliser les clés du row (retirer accents pour comparaison)
        $normalized = [];
        foreach ($row as $key => $val) {
            $normalized[$this->sansAccent($key)] = $val;
        }
        foreach ($cles as $cle) {
            $cleSans = $this->sansAccent($cle);
            // Chercher exact d'abord
            if (isset($row[$cle]) && $row[$cle] !== null) return (string) $row[$cle];
            // Chercher sans accent
            if (isset($normalized[$cleSans]) && $normalized[$cleSans] !== null) return (string) $normalized[$cleSans];
        }
        return null;
    }

    /** Retirer les accents d'une chaîne. */
    private function sansAccent(string $str): string
    {
        $str = mb_strtolower($str);
        $map = ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a',
                'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c'];
        return strtr($str, $map);
    }

    /** Parser une date (texte ou nombre sériel Excel). */
    private function parserDate(?string $val): ?string
    {
        if (!$val || $val === '') return null;
        // Nombre sériel Excel (ex: 45000 → date)
        if (is_numeric($val) && (int) $val > 30000 && (int) $val < 80000) {
            $timestamp = \Carbon\Carbon::createFromDate(1899, 12, 30)->addDays((int) $val);
            return $timestamp->format('Y-m-d');
        }
        try {
            return \Carbon\Carbon::parse($val)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
