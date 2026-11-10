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
    /** Sprint Planning : les tâches du sprint. */
    public function planning(Request $r, Sprint $sprint)
    {
        return $this->show($r, $sprint, 'planning');
    }

    /** Sprint Backlog : l'équipe et les user stories du sprint ($vue = « planning » : les tâches). */
    public function show(Request $r, Sprint $sprint, string $vue = 'backlog')
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
        $backlogDispo = UserStory::trierParNumero(UserStory::where('projet_id', $sprint->projet_id)->disponibles()->get())->values();
        // Équipe du sprint (dynamique) : proposée en priorité pour l'attribution des tâches
        $equipe = \App\Services\ScrumService::membres($sprint);
        $retiresSprint = \App\Models\SprintEquipe::with(['user', 'par'])->where('sprint_id', $sprint->id)->where('statut', 'retire')->get();
        $rolesProjet = $sprint->projet->collaborateurs()->get()->keyBy('user_id');
        $voitProjet = $sprint->projet->estVisiblePour($user);

        $peutPuiser = \App\Services\CeremoniesScrum::peutPuiser($user, $sprint);
        $peutAlimenter = $sprint->projet->peutAlimenterBacklog($user);
        $peutRattacher = ($canEdit || $peutAlimenter) && $sprint->statut !== 'termine';

        return view('sprints.show', compact('sprint', 'users', 'canEdit', 'canEditSprint', 'storiesSprint', 'backlogDispo', 'equipe', 'retiresSprint', 'rolesProjet', 'voitProjet', 'peutPuiser', 'peutAlimenter', 'peutRattacher', 'vue'));
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

    /** Numéros des stories écartées d'un import de Sprint Planning. */
    private array $storiesNonPretes = [];

    /** Une story DoR = Non n'entre pas dans le sprint, y compris par le rattachement d'une tâche. */
    private function storyNonPrete(Sprint $sprint, int $storyId)
    {
        $us = UserStory::find($storyId);
        if (!$us || $us->dor || $sprint->userStories()->whereKey($storyId)->exists()) return null;
        return back()->withInput()->with('erreur', "{$us->numero} : DoR = Non, cette user story ne peut pas entrer dans le sprint.");
    }

    /** Refus des modifications du Sprint Backlog une fois le sprint clôturé. */
    private function backlogFige(Sprint $sprint)
    {
        $motif = \App\Services\CeremoniesScrum::verrouBacklog($sprint);
        return $motif ? back()->with('erreur', $motif) : null;
    }

    /** Le sprint se termine : le statut de ses stories est remis à jour ; renvoie le nombre de stories retournées au backlog. */
    private function retourBacklog(Sprint $sprint): int
    {
        $ids = $sprint->userStories()->pluck('user_stories.id')->merge($sprint->taches()->pluck('user_story_id'))->filter()->unique();
        foreach ($ids as $id) \App\Services\AvancementScrum::synchroniser($id);
        return UserStory::whereIn('id', $ids)->where('statut', 'a_faire')->count();
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
        if ($sprint->statut !== 'en_cours') return back()->with('erreur', $sprint->statut === 'termine' ? 'Ce sprint est déjà clôturé.' : "Ce sprint n'a pas démarré.");
        // Review et Rétrospective précèdent la clôture ; à défaut, un pilote tranche selon les réalités, motif obligatoire
        $motif = null;
        if ($manque = \App\Services\CeremoniesScrum::manquantsCloture($sprint)) {
            if (!$r->user()->estPilote()) {
                return back()->with('erreur', 'Clôture impossible : validez d\'abord ' . implode(' et ', $manque) . ', ou demandez à un pilote de clôturer avec un motif.');
            }
            $motif = trim((string) $r->validate(['motif_cloture' => 'required|string|min:5|max:1000'], [
                'motif_cloture.required' => 'Le motif est obligatoire pour clôturer sans ' . implode(' ni ', $manque) . ' validée(s).',
                'motif_cloture.min' => 'Précisez le motif (5 caractères au moins).',
            ])['motif_cloture']);
        }

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

        $sprint->update(['statut' => 'termine', 'justification_arret' => $motif]);
        if ($motif) \App\Models\AuditLog::log('cloture_motivee', 'sprints', "Sprint #{$sprint->numero}", 'Sans ' . implode(' ni ', $manque) . ' validée(s) - Motif : ' . $motif);
        $retournees = $this->retourBacklog($sprint);

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

        return back()->with('succes', "Sprint clôturé. {$pointsLivres}/{$engagement} pts livrés. {$stats['a_temps']} à temps, {$stats['en_retard']} en retard, {$stats['non_termine']} non terminée(s)." . ($retournees ? " {$retournees} user story(s) retournée(s) au Product Backlog." : ''));
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
        $retournees = $this->retourBacklog($sprint);

        // Notifier le responsable projet
        if ($sprint->projet?->responsable_projet_id) {
            NotificationApp::create([
                'user_id' => $sprint->projet->responsable_projet_id,
                'type' => 'sprint_arret',
                'contenu' => "Sprint {$sprint->numero} arrêté par " . $r->user()->nomComplet() . ". Motif : {$r->justification}",
            ]);
        }

        return back()->with('succes', 'Sprint arrêté. Motif enregistré.' . ($retournees ? " {$retournees} user story(s) retournée(s) au Product Backlog." : ''));
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
        if ($fige = $this->backlogFige($sprint)) return $fige;
        $r->validate([
            'intitule' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'priorite' => 'required|in:haute,moyenne,basse',
            'date_debut' => 'nullable|date',
            'echeance' => 'nullable|date|after_or_equal:date_debut',
            'points' => 'nullable|integer|min:1',
            'user_story_id' => ['nullable', \Illuminate\Validation\Rule::exists('user_stories', 'id')->where('projet_id', $sprint->projet_id)],
        ]);
        if ($r->user_story_id && ($refus = $this->storyNonPrete($sprint, (int) $r->user_story_id))) return $refus;

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

    /**
     * Rattache en une fois des tâches du sprint à une user story (ou les en détache).
     * Une story absente du sprint y entre, à condition d'être disponible (DoR = Oui, non achevée, aucun autre sprint ouvert).
     */
    public function rattacherTaches(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id) || $sprint->projet->peutAlimenterBacklog($r->user()), 403);
        if ($fige = $this->backlogFige($sprint)) return $fige;
        $v = $r->validate([
            'taches' => 'required|array|min:1',
            'taches.*' => [\Illuminate\Validation\Rule::exists('taches', 'id')->where('sprint_id', $sprint->id)->whereNull('deleted_at')],
            'user_story_id' => ['nullable', \Illuminate\Validation\Rule::exists('user_stories', 'id')->where('projet_id', $sprint->projet_id)],
        ], ['taches.required' => 'Cochez au moins une tâche.']);

        $us = !empty($v['user_story_id']) ? UserStory::find($v['user_story_id']) : null;
        if ($us && !$sprint->userStories()->whereKey($us->id)->exists()) {
            if (!UserStory::whereKey($us->id)->disponibles()->exists()) {
                return back()->with('erreur', "{$us->numero} ne peut pas entrer dans ce sprint : DoR = Non, déjà achevée ou engagée dans un autre sprint ouvert.");
            }
            $sprint->userStories()->attach($us->id, ['statut_sprint' => 'a_faire']);
        }
        // Tâche par tâche : le statut des stories concernées se recalcule à chaque changement
        $taches = Tache::whereIn('id', $v['taches'])->where('sprint_id', $sprint->id)->get();
        \Illuminate\Support\Facades\DB::transaction(fn() => $taches->each(fn($t) => $t->update(['user_story_id' => $us?->id])));

        $cible = $us ? $us->numero : 'aucune user story';
        \App\Models\AuditLog::log('rattachement_taches', 'sprints', "Sprint {$sprint->numero}", "{$taches->count()} tâche(s) → {$cible}");
        return back()->with('succes', $us ? "{$taches->count()} tâche(s) rattachée(s) à {$us->numero}." : "{$taches->count()} tâche(s) détachée(s) de leur user story.");
    }

    /** Modifier une tâche existante du sprint. */
    public function modifierTache(Request $r, Sprint $sprint, Tache $tache)
    {
        abort_unless($r->user()->estPilote(), 403);
        abort_unless($tache->sprint_id === $sprint->id, 404);
        if ($fige = $this->backlogFige($sprint)) return $fige;

        $r->validate([
            'intitule' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'priorite' => 'required|in:haute,moyenne,basse',
            'date_debut' => 'nullable|date',
            'echeance' => 'nullable|date|after_or_equal:date_debut',
            'points' => 'nullable|integer|min:1',
            'user_story_id' => ['nullable', \Illuminate\Validation\Rule::exists('user_stories', 'id')->where('projet_id', $sprint->projet_id)],
        ]);
        if ($r->user_story_id && ($refus = $this->storyNonPrete($sprint, (int) $r->user_story_id))) return $refus;

        $tache->update($r->only(['intitule', 'user_id', 'priorite', 'date_debut', 'echeance', 'points', 'user_story_id']));
        if ($r->user_story_id) $sprint->userStories()->syncWithoutDetaching([$r->user_story_id]);

        return back()->with('succes', 'Tâche mise à jour.');
    }

    /* ═══════ SPRINT PLANNING : user stories du sprint ═══════ */

    /** Ajouter des user stories du Product Backlog au Sprint Backlog. */
    public function attacherUserStories(Request $r, Sprint $sprint)
    {
        if ($fige = $this->backlogFige($sprint)) return $fige;
        abort_unless(\App\Services\CeremoniesScrum::peutPuiser($r->user(), $sprint), 403);
        $r->validate([
            'user_stories' => 'required|array|min:1',
            'user_stories.*' => [\Illuminate\Validation\Rule::exists('user_stories', 'id')->where('projet_id', $sprint->projet_id)],
        ], ['user_stories.required' => 'Sélectionnez au moins une user story.']);
        // Une story n'est engagée que dans un sprint ouvert à la fois, et une story terminée ne repart pas
        $ids = UserStory::whereIn('id', $r->user_stories)->disponibles()->pluck('id');
        if ($ids->isEmpty()) return back()->with('erreur', 'Aucune de ces user stories ne peut entrer dans le sprint : DoR = Non, déjà terminée ou engagée dans un sprint ouvert.');
        $sprint->userStories()->syncWithoutDetaching($ids);
        foreach ($ids as $id) \App\Services\AvancementScrum::synchroniser($id);
        \App\Models\AuditLog::log('puiser_backlog', 'sprints', "Sprint {$sprint->numero}", UserStory::whereIn('id', $ids)->pluck('numero')->join(', '));
        $ecartees = count(array_unique($r->user_stories)) - $ids->count();
        return back()->with('succes', "{$ids->count()} user story(s) ajoutée(s) au Sprint {$sprint->numero}." . ($ecartees ? " {$ecartees} écartée(s) : DoR = Non, déjà terminée(s) ou engagée(s) ailleurs." : ''));
    }

    /** Sprint Planning : story points et tâches techniques d'une user story du sprint. */
    public function planifierUserStory(Request $r, Sprint $sprint, UserStory $userStory)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        if ($fige = $this->backlogFige($sprint)) return $fige;
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
        abort_unless($r->user()->peutGererProjet($sprint->projet_id) || \App\Services\CeremoniesScrum::peutPuiser($r->user(), $sprint), 403);
        if ($fige = $this->backlogFige($sprint)) return $fige;
        $sprint->userStories()->detach($userStory->id);
        \App\Services\AvancementScrum::synchroniser($userStory->id);
        return back()->with('succes', "{$userStory->numero} retirée du sprint (retour au backlog).");
    }

    /** Terminé → Terminé testé (TT), et inversement. */
    public function basculerTeste(Request $r, Sprint $sprint, Tache $tache)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        abort_unless($tache->sprint_id === $sprint->id, 404);
        if ($fige = $this->backlogFige($sprint)) return $fige;
        if ($tache->statut !== 'termine') return back()->with('erreur', 'Seule une tâche terminée peut être marquée comme testée.');
        $tache->update(['teste' => !$tache->teste]);
        return back()->with('succes', "« {$tache->intitule} » : " . ($tache->teste ? 'terminée et testée (TT).' : 'test annulé (T).'));
    }

    /** Supprimer une tâche du sprint. */
    public function supprimerTache(Request $r, Sprint $sprint, Tache $tache)
    {
        abort_unless($r->user()->estPilote(), 403);
        abort_unless($tache->sprint_id === $sprint->id, 404);
        if ($fige = $this->backlogFige($sprint)) return $fige;
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

            // Hiérarchie Module > Epic > User story : colonnes dédiées, ou lignes de contexte du format exporté
            // (« MODULE n : … », « Epic : … », ligne « US x.y » suivie de ses tâches « x.y.n »)
            $sprintImport = $r->sprint_id ? Sprint::with('projet')->find($r->sprint_id) : null;
            if ($sprintImport && ($fige = $this->backlogFige($sprintImport))) return $fige;
            $moduleCourant = null; $epicCourant = null; $storyCourante = null;
            $storiesCreees = 0; $storiesRattachees = [];
            // Réimport : tâches déjà présentes dans le sprint, par intitulé et assigné. Chacune ne répond qu'à une seule ligne :
            // deux lignes identiques (même intitulé, même personne) correspondent à deux tâches distinctes.
            $cleTache = fn($intitule, $userId) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $intitule))) . '|' . $userId;
            $existantes = $sprintImport ? $sprintImport->taches()->orderBy('id')->get()->groupBy(fn($t) => $cleTache($t->intitule, $t->user_id)) : collect();
            $majTaches = 0;

            foreach ($data as $idx => $row) {
                $numLigne = $idx + $ligneEntete + 1;

                try {
                    $premiere = trim((string) (collect($row)->first(fn($v) => $v !== null && trim((string) $v) !== '') ?? ''));
                    if (preg_match('/^module\s*\d*\s*[:\-–—]\s*(.+)$/iu', $premiere, $m)) { $moduleCourant = trim($m[1]); $epicCourant = null; $storyCourante = null; continue; }
                    if (preg_match('/^epic\s*[:\-–—]\s*(.+)$/iu', $premiere, $m)) { $epicCourant = trim($m[1]); $storyCourante = null; continue; }
                    if (preg_match('/^total\b/iu', $premiere)) continue;

                    $numero = trim($this->chercher($row, ['n°', 'n', 'no', 'num', 'numero', 'numéro']) ?? '');
                    $colStory = trim($this->chercher($row, ['user_story', 'user story', 'story', 'us']) ?? '');
                    $colModule = trim($this->chercher($row, ['module', 'module_/_fonctionnalité', 'fonctionnalité', 'fonctionnalite']) ?? '');
                    $colEpic = trim($this->chercher($row, ['epic']) ?? '');
                    $intitule = trim($this->chercher($row, ['intitulé', 'intitule', 'tâche', 'tache', 'tâches', 'taches', 'libellé', 'libelle']) ?? '');
                    $points = trim($this->chercher($row, ['points', 'story_points', 'pts']) ?? '');

                    // Ligne de user story (format exporté) : « US 9.1 » dans la colonne N°
                    if ($sprintImport && preg_match('/^US[\s\-_]*[A-Z]?\d+(?:[.\-]\d+)*$/iu', $numero)) {
                        $storyCourante = $this->storyImport($sprintImport, $numero, $colStory ?: $intitule, $moduleCourant ?: ($colModule ?: ($intitule !== $colStory ? $intitule : null)), $epicCourant ?: ($colEpic ?: null), is_numeric($points) ? (int) $points : null, $storiesCreees);
                        if ($storyCourante) $storiesRattachees[$storyCourante->id] = true;
                        continue;
                    }

                    if (empty($intitule)) { $ignores++; continue; }

                    $projetStr = trim($this->chercher($row, ['projet']) ?? '');
                    $assigneStr = trim($this->chercher($row, ['assigné', 'assigne', 'assigné_à', 'assigne_a', 'assigné à', 'collaborateur', 'responsable', 'attribution']) ?? '');
                    $dateDebutStr = trim($this->chercher($row, ['date_début', 'date_debut', 'date début', 'date_de_début', 'date_de_debut', 'début', 'debut', 'date', 'début_prévu', 'debut_prevu']) ?? '');
                    $echeanceStr = trim($this->chercher($row, ['échéance', 'echeance', 'date_fin', 'date fin', 'date_de_fin', 'fin', 'deadline', 'date_échéance', 'date_echeance']) ?? '');

                    // Dans un sprint, une tâche sans échéance prend la date de fin du sprint
                    if (empty($echeanceStr) && $sprintImport) $echeanceStr = $sprintImport->date_fin->format('Y-m-d');
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
                    $statutStr = mb_strtoupper(trim($this->chercher($row, ['statut', 'état', 'etat']) ?? ''));

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
                    if ($sprintId && !$projetId) $projetId = $sprintImport?->projet_id;

                    // User story de la tâche : colonne « User story », sinon la story de la ligne « US » qui précède (tâche « x.y.n »)
                    $story = null;
                    if ($sprintImport && $colStory !== '') {
                        $story = $this->storyImport($sprintImport, $colStory, $colStory, $colModule ?: $moduleCourant, $colEpic ?: $epicCourant, null, $storiesCreees);
                    } elseif ($storyCourante && preg_match('/^\d+(?:\.\d+)+$/', $numero)) {
                        $story = $storyCourante;
                    }
                    if ($story) $storiesRattachees[$story->id] = true;

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
                    // Statut (codes du Sprint Planning : T / TT terminé, D / E en cours)
                    $statut = match (true) {
                        in_array($statutStr, ['T', 'TT', 'TERMINÉ', 'TERMINE', 'RÉALISÉ', 'REALISE'], true) => 'termine',
                        in_array($statutStr, ['D', 'E', 'EN COURS', 'DÉMARRÉ', 'DEMARRE'], true) => 'en_cours',
                        default => 'a_faire',
                    };

                    // Parser les dates (gère les dates texte ET les nombres sériels Excel)
                    $dateDebut = $this->parserDate($dateDebutStr);
                    $echeance = $this->parserDate($echeanceStr);

                    // Déjà dans le sprint : pas de doublon. La story du fichier est rattachée et seuls les champs vides sont complétés ;
                    // le statut et ce qui a été saisi depuis le premier import ne bougent pas.
                    if ($existantes->get($cleTache($intitule, $userId))?->isNotEmpty()) {
                        $tache = $existantes[$cleTache($intitule, $userId)]->shift();
                        $tache->update(array_filter([
                            'user_story_id' => $story?->id,
                            'points' => $tache->points === null && is_numeric($points) ? (int) $points : null,
                            'date_debut' => $tache->date_debut ? null : $dateDebut,
                            'echeance' => $tache->echeance ? null : $echeance,
                        ], fn($v) => $v !== null));
                        $majTaches++;
                        continue;
                    }

                    Tache::create([
                        'user_id' => $userId,
                        'projet_id' => $projetId,
                        'sprint_id' => $sprintId,
                        'user_story_id' => $story?->id,
                        'intitule' => $intitule,
                        'type' => $sprintId ? 'T1' : 'T2',
                        'statut' => $statut,
                        'date_fin_reelle' => $statut === 'termine' ? ($echeance ?? today()->format('Y-m-d')) : null,
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
            if ($majTaches) $msg .= ", {$majTaches} déjà présente(s) mise(s) à jour";
            if ($storiesRattachees) $msg .= ', ' . count($storiesRattachees) . ' user story(s) rattachée(s) au sprint' . ($storiesCreees ? " (dont {$storiesCreees} ajoutée(s) au Product Backlog)" : '');
            if ($ignores > 0) $msg .= ", {$ignores} ignorée(s)";
            if ($this->storiesNonPretes) $msg .= '. Non rattachées au sprint (DoR = Non, achevée ou engagée ailleurs) : ' . implode(', ', array_keys($this->storiesNonPretes)) . ' ; leurs tâches restent hors user story';
            if (!empty($erreurs)) {
                $msg .= '. Erreurs : ' . implode(' | ', array_slice($erreurs, 0, 3));
                return back()->with('erreur', $msg);
            }
            return back()->with('succes', $msg . '.');
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }

    /**
     * User story de l'import : retrouvée dans le backlog du projet par son numéro (« US 9.1 ») ou son intitulé,
     * sinon créée (module, epic, story points) ; dans tous les cas rattachée au sprint.
     */
    private function storyImport(Sprint $sprint, string $ref, ?string $intitule, ?string $module, ?string $epic, ?int $points, int &$crees): ?UserStory
    {
        $projet = $sprint->projet;
        $numero = null;
        $texte = trim((string) $intitule);
        if (preg_match('/^\s*(US[\s\-_]*[A-Z]?\d+(?:[.\-]\d+)*)\s*[—–\-:]*\s*(.*)$/sui', $ref, $m)) {
            $numero = strtoupper(trim($m[1]));
            if ($texte === '' || $texte === $ref) $texte = trim($m[2]);
        }
        // « US-001 », « US 001 » et « us_001 » désignent la même story : le numéro du backlog fait foi
        $cle = fn($n) => preg_replace('/[\s\-_]+/', '', mb_strtolower($n));
        $us = $numero ? $projet->userStories()->get()->first(fn($s) => $cle($s->numero) === $cle($numero)) : null;
        if (!$us && $texte !== '') $us = $projet->userStories()->whereRaw('LOWER(intitule) = ?', [mb_strtolower($texte)])->first();
        $module = trim((string) $module) ?: null;
        $epic = trim((string) $epic) ?: null;
        if (!$us) {
            if ($texte === '' && !$numero) return null;
            $us = $projet->userStories()->create([
                'numero' => $numero ?: UserStory::prochainNumero($projet, $module),
                'module' => $module, 'epic' => $epic,
                'intitule' => $texte !== '' ? $texte : $numero,
                'effort_estime' => UserStory::normaliserPoints($points),
                'dor' => true, // inscrite au planning importé : engagée, donc prête
                'priorite' => 'moyenne', 'statut' => 'a_faire', 'origine' => 'import',
            ]);
            $crees++;
        } else {
            // Compléments sans écraser ce qui est déjà renseigné dans le backlog
            $maj = array_filter([
                'module' => $us->module ? null : $module,
                'epic' => $us->epic ? null : $epic,
                'effort_estime' => $us->effort_estime ? null : UserStory::normaliserPoints($points),
            ]);
            if ($maj) $us->update($maj);
        }
        // Rattachement sans toucher à l'avancement d'une story déjà présente dans le sprint
        if (!$sprint->userStories()->whereKey($us->id)->exists()) {
            // Mêmes règles que pour puiser dans le backlog : DoR = Oui, non achevée, aucun autre sprint ouvert
            if (!UserStory::whereKey($us->id)->disponibles()->exists()) { $this->storiesNonPretes[$us->numero] = true; return null; }
            $sprint->userStories()->attach($us->id, ['statut_sprint' => 'a_faire']);
        }
        return $us;
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
