<?php
namespace App\Http\Controllers;

use App\Models\{AuditLog, CompteRendu, DailyScrum, DailyScrumEntree, NotificationApp, ProjetCollaborateur, Sprint, SprintEquipe, TypeReunion, User, UserStory};
use App\Services\{ArchiveScrum, RedacteurDuJour, ScrumService, TexteRiche};
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Cérémonies Scrum d'un sprint : Daily Scrum, Review, Rétrospective, archive Excel. */
class SprintScrumController extends Controller
{
    private function voir(Request $r, Sprint $sprint): void
    {
        abort_unless($sprint->projet->estVisiblePour($r->user()), 403, "Vous n'êtes pas membre de ce projet.");
    }

    private function gerer(Request $r, Sprint $sprint): bool
    {
        return $r->user()->peutGererProjet($sprint->projet_id);
    }

    /** Rédacteur d'un rapport : le scribe du jour au planning des réunions (ou le rédacteur enregistré). */
    private function redacteurId(Sprint $sprint, ?CompteRendu $cr): ?int
    {
        return $cr?->redacteur_id ?? RedacteurDuJour::id($sprint->date_fin->lt(today()) ? $sprint->date_fin : today(), $sprint->projet_id);
    }

    /** Review / Rétrospective : membres du projet et rédacteur du jour. */
    private function voirRapport(Request $r, Sprint $sprint, ?CompteRendu $cr): void
    {
        if ($this->redacteurId($sprint, $cr) === $r->user()->id) return;
        $this->voir($r, $sprint);
    }

    private function redigerRapport(Request $r, Sprint $sprint, ?CompteRendu $cr): bool
    {
        return $this->gerer($r, $sprint) || $this->redacteurId($sprint, $cr) === $r->user()->id;
    }

    /** Si la date du rapport change (non validé), le rédacteur devient le scribe de cette date. */
    private function reattribuer(CompteRendu $cr, Sprint $sprint, ?string $date): void
    {
        if (!$date || $cr->estConfirme() || $cr->date_reunion?->toDateString() === $date) return;
        if ($id = RedacteurDuJour::id($date, $sprint->projet_id)) $cr->redacteur_id = $id;
    }

    /* ═══════ DAILY SCRUM ═══════ */

    /** Les rapports de Daily Scrum sont consultables par tous les collaborateurs. */
    public function dailies(Request $r, Sprint $sprint)
    {
        $sprint->load('projet');
        // Création automatique du Daily du jour si elle est activée (complète la tâche planifiée)
        if ($auto = ScrumService::creationAutomatique($sprint)) AuditLog::log('creation_daily', 'sprint', $sprint->projet->code . ' S' . $sprint->numero, $auto->libelle() . ' (automatique)');
        $sprint->load(['dailyScrums' => fn($q) => $q->withCount(['entrees', 'entrees as defis_count' => fn($e) => $e->whereNotNull('defis')->where('defis', '!=', '')])]);
        $membres = ScrumService::membres($sprint);
        $config = $sprint->projet->configDaily();
        // Hors équipe du projet, seuls les rapports publiés sont visibles
        $voitTout = $sprint->projet->estVisiblePour($r->user());

        // Jours programmés du sprint et Daily correspondant (calendrier)
        $parDate = $sprint->dailyScrums->filter(fn($d) => $voitTout || $d->visiblePour($r->user()))->keyBy(fn($d) => $d->date_reunion->toDateString());
        $jours = ScrumService::joursProgrammes($sprint)->values()->map(fn($j, $i) => ['date' => $j, 'numero' => $i + 1, 'daily' => $parDate[$j->toDateString()] ?? null])->all();
        // Daily tenus un jour qui n'est plus programmé (programmation modifiée en cours de sprint)
        $horsProgramme = $parDate->reject(fn($d) => collect($jours)->contains(fn($j) => $j['daily']?->id === $d->id))->values();
        $dateProposee = collect($jours)->first(fn($j) => !$j['daily'] && $j['date']->gte(today()))['date']
            ?? collect($jours)->first(fn($j) => !$j['daily'])['date'] ?? null;
        $users = User::whereIn('id', $sprint->dailyScrums->pluck('roles')->flatten()->filter()->unique())->get()->keyBy('id');

        return view('scrum.dailies', [
            'sprint' => $sprint, 'jours' => $jours, 'horsProgramme' => $horsProgramme, 'membres' => $membres, 'users' => $users,
            'dateProposee' => $dateProposee, 'config' => $config, 'heureDefaut' => $config['heure_debut'],
            'peutCreer' => $this->peutCreerDaily($r, $sprint), 'canEdit' => $this->gerer($r, $sprint),
        ]);
    }

    /** Programmation du Daily Scrum du projet (jours, horaire, durée, lieu, création automatique). */
    public function programmationDaily(Request $r, Sprint $sprint)
    {
        abort_unless($this->gerer($r, $sprint), 403);
        $v = $r->validate([
            'jours' => 'required|array|min:1', 'jours.*' => 'integer|between:1,6',
            'heure_debut' => 'required|date_format:H:i',
            'duree' => 'required|integer|between:5,120',
            'lieu' => 'nullable|string|max:150',
            'creation_auto' => 'nullable|boolean',
        ], ['jours.required' => 'Choisissez au moins un jour pour le Daily Scrum.']);
        $v['jours'] = array_values(array_unique(array_map('intval', $v['jours'])));
        $v['creation_auto'] = $r->boolean('creation_auto');
        $sprint->projet->update(['daily_config' => $v]);
        $libJours = collect($v['jours'])->sort()->map(fn($j) => \App\Models\Projet::JOURS_DAILY[$j])->join(', ');
        AuditLog::log('programmation_daily', 'projet', $sprint->projet->code, "{$libJours} à {$v['heure_debut']} ({$v['duree']} min)" . ($v['creation_auto'] ? ', création automatique' : ''));
        return back()->with('succes', "Programmation enregistrée : {$libJours} à {$v['heure_debut']} ({$v['duree']} min)" . ($v['creation_auto'] ? ', création automatique.' : '.'));
    }

    /** Tous les rapports de Daily Scrum, tous projets confondus : consultables par tous les collaborateurs. */
    public function rapportsDaily(Request $r)
    {
        $q = DailyScrum::with(['sprint.projet'])->withCount(['entrees', 'entrees as defis_count' => fn($e) => $e->whereNotNull('defis')->where('defis', '!=', '')])
            ->when($r->projet_id, fn($q) => $q->whereHas('sprint', fn($s) => $s->where('projet_id', $r->projet_id)))
            ->when($r->du, fn($q) => $q->whereDate('date_reunion', '>=', $r->du))
            ->when($r->au, fn($q) => $q->whereDate('date_reunion', '<=', $r->au))
            ->when(!$r->user()->estPilote(), fn($q) => $q->where(fn($v) => $v->whereNotNull('publie_le')
                ->orWhereHas('entrees', fn($e) => $e->where('user_id', $r->user()->id))
                ->orWhereHas('sprint.projet', fn($p) => $p->where('responsable_projet_id', $r->user()->id)
                    ->orWhereHas('collaborateurs', fn($c) => $c->where('user_id', $r->user()->id)->where('actif', true)))))
            ->orderByDesc('date_reunion')->orderByDesc('id');
        $dailies = $q->paginate(25)->withQueryString();
        $ids = $dailies->getCollection()->map(fn($d) => $d->roleUserId('scribe'))->filter()->unique();
        return view('scrum.rapports', [
            'dailies' => $dailies,
            'scribes' => User::whereIn('id', $ids)->get()->keyBy('id'),
            'projets' => \App\Models\Projet::whereHas('sprints.dailyScrums')->orderBy('libelle')->get(['id', 'libelle', 'code']),
        ]);
    }

    private function peutCreerDaily(Request $r, Sprint $sprint): bool
    {
        return $this->gerer($r, $sprint) || $sprint->projet->equipe()->whereKey($r->user()->id)->exists()
            || ScrumService::membres($sprint)->contains('id', $r->user()->id);
    }

    public function storeDaily(Request $r, Sprint $sprint)
    {
        abort_unless($this->peutCreerDaily($r, $sprint), 403);
        $v = $r->validate([
            'date_reunion' => ['required', 'date', 'after_or_equal:' . $sprint->date_debut->toDateString(), 'before_or_equal:' . $sprint->date_fin->toDateString()],
            'heure_debut' => 'nullable|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i|after:heure_debut',
        ], ['date_reunion.after_or_equal' => 'La date doit être comprise dans le sprint.', 'date_reunion.before_or_equal' => 'La date doit être comprise dans le sprint.']);
        $date = Carbon::parse($v['date_reunion'])->startOfDay();
        $sprint->loadMissing('projet');
        if (!ScrumService::estProgramme($sprint, $date)) {
            $jours = collect($sprint->projet->configDaily()['jours'])->map(fn($j) => mb_strtolower(\App\Models\Projet::JOURS_DAILY[$j]))->join(', ');
            return back()->withInput()->with('erreur', "Jour non programmé pour le Daily (prévus : {$jours}).");
        }
        if ($sprint->dailyScrums()->whereDate('date_reunion', $date)->exists()) return back()->withInput()->with('erreur', 'Un Daily existe déjà à cette date.');

        $daily = DB::transaction(fn() => ScrumService::creerDaily($sprint, $date, $r->user()->id, $v['heure_debut'] ?? null, $v['heure_fin'] ?? null));
        $numero = $daily->numero_jour;
        AuditLog::log('creation_daily', 'sprint', $sprint->projet->code . ' S' . $sprint->numero, $daily->libelle());
        return redirect()->route('scrum.daily', [$sprint, $daily])->with('succes', "Daily {$numero} créé et pré-rempli.");
    }

    public function daily(Request $r, Sprint $sprint, DailyScrum $daily)
    {
        abort_unless($daily->sprint_id === $sprint->id, 404);
        $daily->load(['entrees.user', 'createur', 'publiePar', 'soumisPar']);
        $sprint->load('projet');
        // Sauf aval d'un pilote (publication), le rapport n'est pas diffusé hors de l'équipe
        abort_unless($daily->visiblePour($r->user()), 403, "Ce rapport de Daily Scrum n'est pas encore publié.");
        $membres = ScrumService::membres($sprint);
        $voisins = $sprint->dailyScrums()->get(['id', 'numero_jour', 'date_reunion']);
        $manquants = $membres->whereNotIn('id', $daily->entrees->pluck('user_id'));

        return view('scrum.daily', [
            'sprint' => $sprint, 'daily' => $daily, 'membres' => $membres, 'manquants' => $manquants,
            'precedent' => $voisins->where('numero_jour', '<', $daily->numero_jour)->last(),
            'suivant' => $voisins->where('numero_jour', '>', $daily->numero_jour)->first(),
            'tousUsers' => User::where('actif', true)->orderBy('name')->get(),
            'edition' => $this->editionDaily($r, $sprint, $daily),
            'peutVoirProjet' => $sprint->projet->estVisiblePour($r->user()),
            // Liste de présence : statut du Pointage du jour pour chaque participant
            'pointages' => \App\Models\Pointage::whereDate('date', $daily->date_reunion)->whereIn('user_id', $daily->entrees->pluck('user_id'))->get()->keyBy('user_id'),
            'config' => $sprint->projet->configDaily(),
        ]);
    }

    /** « tout » : gestionnaire du projet, scribe ou facilitateur du jour ; « ligne » : un membre pour sa propre ligne. */
    private function editionDaily(Request $r, Sprint $sprint, DailyScrum $daily): ?string
    {
        $u = $r->user();
        // Rapport soumis ou publié : modifiable uniquement par un pilote (administrateur ou chargé des opérations)
        if ($daily->estPublie() || $daily->estSoumis()) return $u->estAdmin() ? 'tout' : null;
        if ($this->gerer($r, $sprint) || in_array($u->id, [$daily->roleUserId('scribe'), $daily->roleUserId('facilitateur'), $daily->cree_par], true)) return 'tout';
        return $daily->entrees->contains('user_id', $u->id) ? 'ligne' : null;
    }

    public function updateDaily(Request $r, Sprint $sprint, DailyScrum $daily)
    {
        abort_unless($daily->sprint_id === $sprint->id, 404);
        $daily->load('entrees');
        $mode = $this->editionDaily($r, $sprint, $daily);
        abort_unless($mode, 403);

        $v = $r->validate([
            'heure_debut' => 'nullable|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i',
            'roles' => 'nullable|array',
            'roles.*' => 'nullable|exists:users,id',
            'commentaire_general' => 'nullable|string|max:20000',
            'observation' => 'nullable|string|max:20000',
            'entrees' => 'nullable|array',
            'entrees.*.taches_hier' => 'nullable|string|max:20000',
            'entrees.*.taches_jour' => 'nullable|string|max:20000',
            'entrees.*.duree_restante' => 'nullable|string|max:20',
            'entrees.*.defis' => 'nullable|string|max:20000',
        ]);
        foreach (['commentaire_general', 'observation'] as $k) if (array_key_exists($k, $v)) $v[$k] = TexteRiche::nettoyer($v[$k]);
        foreach ($v['entrees'] ?? [] as $id => $champs) {
            foreach (['taches_hier', 'taches_jour', 'defis'] as $k) if (array_key_exists($k, $champs)) $v['entrees'][$id][$k] = TexteRiche::nettoyer($champs[$k]);
        }

        DB::transaction(function () use ($v, $mode, $daily, $sprint, $r) {
            if ($mode === 'tout') {
                // Seuls les champs soumis sont modifiés
                $maj = [];
                foreach (['heure_debut', 'heure_fin', 'commentaire_general', 'observation'] as $k) {
                    if ($r->exists($k)) $maj[$k] = $v[$k] ?? null;
                }
                if ($r->exists('roles')) $maj['roles'] = array_map('intval', array_filter(array_intersect_key($v['roles'] ?? [], DailyScrum::ROLES)));
                if ($maj) $daily->update($maj);
            }
            $suivant = $sprint->dailyScrums()->where('numero_jour', '>', $daily->numero_jour)->first();
            foreach ($v['entrees'] ?? [] as $id => $champs) {
                $e = $daily->entrees->firstWhere('id', (int) $id);
                if (!$e || ($mode === 'ligne' && $e->user_id !== $r->user()->id)) continue;
                $ancienJour = $e->taches_jour;
                $e->update(array_intersect_key($champs, array_flip(['taches_hier', 'taches_jour', 'duree_restante', 'defis'])));
                // Le « hier » du Daily suivant suit le « à faire ce jour » tant qu'il n'a pas été retouché
                if ($suivant && $ancienJour !== $e->taches_jour) {
                    DailyScrumEntree::where('daily_scrum_id', $suivant->id)->where('user_id', $e->user_id)
                        ->where(fn($q) => $q->where('taches_hier', $ancienJour)->orWhereNull('taches_hier'))
                        ->update(['taches_hier' => $e->taches_jour]);
                }
            }
        });
        return back()->with('succes', 'Daily enregistré.');
    }

    /** Recalcule les lignes à partir de l'état actuel des tâches (les défis saisis sont conservés). */
    public function recalculerDaily(Request $r, Sprint $sprint, DailyScrum $daily)
    {
        abort_unless($daily->sprint_id === $sprint->id, 404);
        $daily->load('entrees');
        abort_unless($this->editionDaily($r, $sprint, $daily) === 'tout', 403);
        $n = ScrumService::remplir($daily, true);
        if ($r->boolean('roles')) $daily->update(['roles' => ScrumService::roles($sprint, $daily->date_reunion, $daily->numero_jour)]);
        return back()->with('succes', "{$n} ligne(s) recalculée(s).");
    }

    /**
     * Publication du rapport : le scribe (ou l'équipe de rédaction) le soumet ; il n'est diffusé
     * qu'après l'aval d'un pilote (administrateur ou chargé des opérations), qui peut publier directement.
     */
    public function publierDaily(Request $r, Sprint $sprint, DailyScrum $daily)
    {
        abort_unless($daily->sprint_id === $sprint->id, 404);
        $daily->load('entrees');
        abort_if($daily->estPublie(), 403);
        $cible = $sprint->projet->code . ' S' . $sprint->numero;
        if ($r->user()->estAdmin()) {
            $daily->update(['publie_le' => now(), 'publie_par' => $r->user()->id, 'soumis_le' => $daily->soumis_le ?? now(), 'soumis_par' => $daily->soumis_par ?? $r->user()->id]);
            AuditLog::log('publication_daily', 'sprint', $cible, $daily->libelle());
            if ($daily->soumis_par && $daily->soumis_par !== $r->user()->id) $this->notifierMembre($daily->soumis_par, "Le rapport {$daily->libelle()} ({$sprint->projet->libelle}) a été publié.", route('scrum.daily', [$sprint, $daily]));
            return back()->with('succes', 'Rapport publié.');
        }
        abort_unless(!$daily->estSoumis() && $this->editionDaily($r, $sprint, $daily) === 'tout', 403);
        $daily->update(['soumis_le' => now(), 'soumis_par' => $r->user()->id]);
        AuditLog::log('soumission_daily', 'sprint', $cible, $daily->libelle());
        foreach (User::where('actif', true)->whereIn('role', ['administrateur', 'charge_operations'])->pluck('id') as $pid) {
            $this->notifierMembre($pid, "{$r->user()->nomComplet()} soumet le rapport {$daily->libelle()} ({$sprint->projet->libelle}) pour publication.", route('scrum.daily', [$sprint, $daily]));
        }
        return back()->with('succes', 'Rapport soumis : publication après validation d\'un pilote.');
    }

    /** Remet un Daily publié en brouillon (pilote uniquement). */
    public function depublierDaily(Request $r, Sprint $sprint, DailyScrum $daily)
    {
        abort_unless($daily->sprint_id === $sprint->id, 404);
        abort_unless($r->user()->estAdmin() && ($daily->estPublie() || $daily->estSoumis()), 403);
        $daily->update(['publie_le' => null, 'publie_par' => null, 'soumis_le' => null, 'soumis_par' => null]);
        AuditLog::log('depublication_daily', 'sprint', $sprint->projet->code . ' S' . $sprint->numero, $daily->libelle());
        return back()->with('succes', 'Daily remis en brouillon.');
    }

    /** Ajoute une ligne au Daily pour n'importe quel collaborateur (invité, renfort, membre d'un autre projet…). */
    public function ajouterEntree(Request $r, Sprint $sprint, DailyScrum $daily)
    {
        abort_unless($daily->sprint_id === $sprint->id, 404);
        $daily->load('entrees');
        abort_unless($this->editionDaily($r, $sprint, $daily) === 'tout', 403);
        $v = $r->validate(['user_id' => ['required', Rule::exists('users', 'id')->where('actif', true)]]);
        if ($daily->entrees->contains('user_id', (int) $v['user_id'])) return back()->with('erreur', 'Ce collaborateur a déjà une ligne dans ce Daily.');
        $daily->loadMissing('sprint');
        DailyScrumEntree::create(['daily_scrum_id' => $daily->id, 'user_id' => $v['user_id']] + ScrumService::calculerEntree($daily, (int) $v['user_id']));
        return back()->with('succes', User::find($v['user_id'])->nomComplet() . ' ajouté(e) au Daily.');
    }

    public function retirerEntree(Request $r, Sprint $sprint, DailyScrum $daily, DailyScrumEntree $entree)
    {
        abort_unless($daily->sprint_id === $sprint->id && $entree->daily_scrum_id === $daily->id, 404);
        $daily->load('entrees');
        abort_unless($this->editionDaily($r, $sprint, $daily) === 'tout', 403);
        $entree->delete();
        return back()->with('succes', 'Ligne retirée du Daily.');
    }

    public function destroyDaily(Request $r, Sprint $sprint, DailyScrum $daily)
    {
        abort_unless($daily->sprint_id === $sprint->id, 404);
        abort_unless($this->gerer($r, $sprint) && (!($daily->estPublie() || $daily->estSoumis()) || $r->user()->estAdmin()), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression_daily', 'sprint', $sprint->projet->code . ' S' . $sprint->numero, $daily->libelle() . ' - Motif : ' . $r->motif_suppression);
        $daily->entrees()->delete();
        $daily->delete();
        return redirect()->route('scrum.dailies', $sprint)->with('succes', 'Daily supprimé.');
    }

    /* ═══════ REVIEW ═══════ */

    private function compteRendu(Sprint $sprint, string $type): ?CompteRendu
    {
        return CompteRendu::where('sprint_id', $sprint->id)->where('type', $type)->first();
    }

    private function nouveauCompteRendu(Request $r, Sprint $sprint, string $type): CompteRendu
    {
        return CompteRendu::create([
            'type' => $type, 'sprint_id' => $sprint->id,
            'titre' => ($type === 'review_sprint' ? 'Review' : 'Rétrospective') . " - Sprint {$sprint->numero} - {$sprint->projet->libelle}",
            'redacteur_id' => RedacteurDuJour::id($date = $sprint->date_fin->lt(today()) ? $sprint->date_fin : today(), $sprint->projet_id) ?? $r->user()->id,
            'designe_par' => $r->user()->id,
            'date_reunion' => $date, 'statut' => 'brouillon', 'donnees_json' => '{}',
        ]);
    }

    public function review(Request $r, Sprint $sprint)
    {
        $sprint->load(['projet', 'taches.user', 'taches.userStory', 'userStories']);
        $cr = $this->compteRendu($sprint, 'review_sprint');
        $this->voirRapport($r, $sprint, $cr);
        return view('scrum.review', [
            'sprint' => $sprint, 'cr' => $cr, 'donnees' => $cr?->getDonnees() ?? [],
            'planning' => ScrumService::planning($sprint), 'ind' => ScrumService::indicateurs($sprint),
            'canEdit' => $cr?->estConfirme() ? $r->user()->estAdmin() : $this->redigerRapport($r, $sprint, $cr),
            'canGerer' => $this->gerer($r, $sprint),
            'redacteur' => User::find($this->redacteurId($sprint, $cr)),
            'storiesCreees' => UserStory::whereIn('id', collect($cr?->getDonnees()['backlog'] ?? [])->pluck('user_story_id')->filter())->get()->keyBy('id'),
            'modules' => $sprint->projet->userStories()->whereNotNull('module')->distinct()->orderBy('module')->pluck('module'),
            'crDemo' => CompteRendu::where('sprint_id', $sprint->id)->where('type', 'demo_sprint')->latest('date_reunion')->first(),
        ]);
    }

    public function updateReview(Request $r, Sprint $sprint)
    {
        $cr = $this->compteRendu($sprint, 'review_sprint');
        abort_unless($cr?->estConfirme() ? $r->user()->estAdmin() : $this->redigerRapport($r, $sprint, $cr), 403);
        $v = $r->validate([
            'date_reunion' => 'nullable|date',
            'heure_debut' => 'nullable|date_format:H:i', 'heure_fin' => 'nullable|date_format:H:i',
            'lieu' => 'nullable|string|max:255',
            'synthese' => 'nullable|string|max:30000', 'decisions' => 'nullable|string|max:30000',
            'observations' => 'nullable|array', 'observations.*' => 'nullable|string|max:10000',
            'backlog' => 'nullable|array|max:50',
            'backlog.*.module' => 'nullable|string|max:150',
            'backlog.*.intitule' => 'nullable|string|max:2000',
            'backlog.*.exigences' => 'nullable|string|max:20000',
            'backlog.*.user_story_id' => 'nullable|integer',
            'apres' => 'nullable|in:enregistrer,backlog',
        ]);
        $cr ??= $this->nouveauCompteRendu($r, $sprint, 'review_sprint');
        $d = $cr->getDonnees();
        $d['synthese'] = TexteRiche::nettoyer($v['synthese'] ?? null);
        $d['decisions'] = TexteRiche::nettoyer($v['decisions'] ?? null);
        $d['observations'] = array_filter(array_map(fn($o) => TexteRiche::nettoyer($o), $v['observations'] ?? []));
        $d['backlog'] = collect($v['backlog'] ?? [])->map(fn($l) => [
            'module' => trim($l['module'] ?? '') ?: null,
            'intitule' => trim($l['intitule'] ?? ''),
            'exigences' => TexteRiche::nettoyer($l['exigences'] ?? null),
            'user_story_id' => !empty($l['user_story_id']) ? (int) $l['user_story_id'] : null,
        ])->filter(fn($l) => $l['intitule'] !== '' || $l['user_story_id'])->values()->all();

        $message = 'Review enregistrée.';
        if (($v['apres'] ?? null) === 'backlog') {
            $n = $this->reviewVersBacklog($sprint, $d['backlog']);
            $message .= $n ? " {$n} user story(s) ajoutée(s) au Product Backlog." : ' Aucune nouvelle user story à ajouter.';
        }
        $cr->setDonnees($d);
        $this->reattribuer($cr, $sprint, $v['date_reunion'] ?? null);
        $cr->fill(array_filter(['date_reunion' => $v['date_reunion'] ?? null, 'lieu' => $v['lieu'] ?? null]) + ['heure_debut' => $v['heure_debut'] ?? null, 'heure_fin' => $v['heure_fin'] ?? null])->save();
        return back()->with('succes', $message);
    }

    /**
     * La Sprint Review alimente le Product Backlog : chaque nouvelle user story identifiée
     * (retours du Product Owner / client) est créée une seule fois, numérotée dans son module.
     */
    private function reviewVersBacklog(Sprint $sprint, array &$lignes): int
    {
        $projet = $sprint->projet;
        $n = 0;
        foreach ($lignes as &$l) {
            if ($l['user_story_id'] || $l['intitule'] === '') continue;
            $us = $projet->userStories()->create([
                'numero' => UserStory::prochainNumero($projet, $l['module']),
                'module' => $l['module'],
                'intitule' => $l['intitule'],
                'exigences' => TexteRiche::nettoyer(($l['exigences'] ? $l['exigences'] . '<br>' : '') . "<i>Issue de la review du sprint {$sprint->numero}.</i>"),
                'statut' => 'a_faire', 'origine' => 'review',
            ]);
            $l['user_story_id'] = $us->id;
            $n++;
        }
        unset($l);
        if ($n) AuditLog::log('review_vers_backlog', 'projet', $projet->code, "{$n} user story(s) issue(s) de la review du sprint {$sprint->numero}");
        return $n;
    }

    /** Valide la Review : fige le compte rendu ; les stories non terminées peuvent retourner au backlog. */
    public function validerReview(Request $r, Sprint $sprint)
    {
        abort_unless($this->gerer($r, $sprint), 403);
        $cr = $this->compteRendu($sprint, 'review_sprint') ?? $this->nouveauCompteRendu($r, $sprint, 'review_sprint');
        abort_if($cr->estConfirme(), 403, 'Review déjà publiée.');
        $ajoutees = 0;
        if ($r->boolean('ajouter_backlog')) {
            $d = $cr->getDonnees();
            $lignes = $d['backlog'] ?? [];
            $ajoutees = $this->reviewVersBacklog($sprint, $lignes);
            $d['backlog'] = $lignes;
            $cr->setDonnees($d);
            $cr->save();
        }
        $n = 0;
        if ($r->boolean('retour_backlog')) {
            foreach ($sprint->userStories as $us) {
                if ($us->pivot->statut_sprint !== 'termine' && $us->statut !== 'terminee') { $us->update(['statut' => 'a_faire']); $n++; }
            }
        }
        $cr->update(['statut' => 'confirme']);
        AuditLog::log('validation_review', 'sprint', $sprint->projet->code . ' S' . $sprint->numero, $n ? "{$n} story(s) remise(s) au backlog" : 'Review validée');
        return back()->with('succes', 'Review validée.' . ($n ? " {$n} story(s) remise(s) au backlog." : '')
            . ($ajoutees ? " {$ajoutees} nouvelle(s) user story(s) ajoutée(s) au Product Backlog." : ''));
    }

    public function rouvrir(Request $r, Sprint $sprint, string $type)
    {
        abort_unless($r->user()->estAdmin() && in_array($type, ['review_sprint', 'retrospective'], true), 403);
        $this->compteRendu($sprint, $type)?->update(['statut' => 'brouillon']);
        return back()->with('succes', 'Compte rendu rouvert en modification.');
    }

    /* ═══════ RÉTROSPECTIVE ═══════ */

    public const THEMES = ['Collaboration', 'Communication', 'Aspect technique', 'Organisation et délais'];

    public function retro(Request $r, Sprint $sprint)
    {
        $sprint->load(['projet', 'taches', 'userStories']);
        $cr = $this->compteRendu($sprint, 'retrospective');
        $this->voirRapport($r, $sprint, $cr);
        $d = $cr?->getDonnees() ?? [];
        $lignes = $d['lignes'] ?? array_map(fn($t) => ['theme' => $t, 'bien' => '', 'pas_bien' => '', 'causes' => '', 'actions' => ''], self::THEMES);
        return view('scrum.retro', [
            'sprint' => $sprint, 'cr' => $cr, 'lignes' => $lignes, 'ind' => ScrumService::indicateurs($sprint),
            'canEdit' => $cr?->estConfirme() ? $r->user()->estAdmin() : $this->redigerRapport($r, $sprint, $cr),
            'canGerer' => $this->gerer($r, $sprint),
            'redacteur' => User::find($this->redacteurId($sprint, $cr)),
        ]);
    }

    public function updateRetro(Request $r, Sprint $sprint)
    {
        $cr = $this->compteRendu($sprint, 'retrospective');
        abort_unless($cr?->estConfirme() ? $r->user()->estAdmin() : $this->redigerRapport($r, $sprint, $cr), 403);
        $v = $r->validate([
            'date_reunion' => 'nullable|date',
            'heure_debut' => 'nullable|date_format:H:i', 'heure_fin' => 'nullable|date_format:H:i',
            'lignes' => 'nullable|array|max:50',
            'lignes.*.theme' => 'nullable|string|max:255',
            'lignes.*.bien' => 'nullable|string|max:20000', 'lignes.*.pas_bien' => 'nullable|string|max:20000',
            'lignes.*.causes' => 'nullable|string|max:20000', 'lignes.*.actions' => 'nullable|string|max:20000',
            'apres' => 'nullable|in:enregistrer,valider',
        ]);
        $cr ??= $this->nouveauCompteRendu($r, $sprint, 'retrospective');
        // La rétrospective porte sur le fonctionnement de l'équipe : elle n'alimente pas le Product Backlog
        $lignes = collect($v['lignes'] ?? [])->map(fn($l) => [
            'theme' => trim($l['theme'] ?? ''),
            'bien' => TexteRiche::nettoyer($l['bien'] ?? null), 'pas_bien' => TexteRiche::nettoyer($l['pas_bien'] ?? null),
            'causes' => TexteRiche::nettoyer($l['causes'] ?? null), 'actions' => TexteRiche::nettoyer($l['actions'] ?? null),
        ])->filter(fn($l) => $l['theme'] . $l['bien'] . $l['pas_bien'] . $l['causes'] . $l['actions'] !== '')->values()->all();

        $message = 'Rétrospective enregistrée.';

        $d = $cr->getDonnees();
        $d['lignes'] = $lignes;
        $cr->setDonnees($d);
        $this->reattribuer($cr, $sprint, $v['date_reunion'] ?? null);
        $cr->fill(array_filter(['date_reunion' => $v['date_reunion'] ?? null]) + ['heure_debut' => $v['heure_debut'] ?? null, 'heure_fin' => $v['heure_fin'] ?? null]);
        // Le rédacteur rédige ; la validation reste au gestionnaire du projet
        if (($v['apres'] ?? null) === 'valider' && $this->gerer($r, $sprint)) { $cr->statut = 'confirme'; $message = 'Rétrospective enregistrée et validée.'; }
        $cr->save();
        return back()->with('succes', $message);
    }

    /* ═══════ ÉQUIPE DU SPRINT ═══════ */

    /**
     * Ajoute un collaborateur au sprint. S'il n'est pas (ou plus) dans l'équipe du projet,
     * il la rejoint à partir d'aujourd'hui : intégration en cours de développement.
     */
    public function ajouterMembre(Request $r, Sprint $sprint)
    {
        abort_unless($this->gerer($r, $sprint), 403);
        $v = $r->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('actif', true)],
            'role_projet' => ['nullable', Rule::in(array_keys(ProjetCollaborateur::ROLES))],
        ]);
        $projet = $sprint->projet;
        $c = ProjetCollaborateur::firstOrNew(['projet_id' => $projet->id, 'user_id' => $v['user_id']]);
        $rejoint = !$c->exists || !$c->actif;
        if ($rejoint) {
            $c->fill(['role_projet' => $v['role_projet'] ?? ($c->role_projet ?: 'developpeur'), 'actif' => true, 'date_ajout' => today(), 'date_retrait' => null])->save();
        }
        SprintEquipe::updateOrCreate(['sprint_id' => $sprint->id, 'user_id' => $v['user_id']], ['statut' => 'ajoute', 'motif' => null, 'par_id' => $r->user()->id]);

        $nom = $c->user->nomComplet();
        $this->notifierMembre((int) $v['user_id'], "Vous rejoignez l'équipe du sprint {$sprint->numero} du projet {$projet->libelle}.", route('sprints.show', $sprint));
        AuditLog::log('ajout_equipe_sprint', 'sprint', $projet->code . ' S' . $sprint->numero, $nom . ($rejoint ? ' - intègre le projet' : ''));
        return back()->with('succes', "{$nom} rejoint l'équipe du sprint {$sprint->numero}" . ($rejoint ? " et l'équipe du projet (" . $c->roleLibelle() . ')' : '') . '.');
    }

    /** Retire un collaborateur de ce sprint uniquement (il reste dans l'équipe du projet). */
    public function retirerMembre(Request $r, Sprint $sprint, User $user)
    {
        abort_unless($this->gerer($r, $sprint), 403);
        $v = $r->validate(['motif' => 'nullable|string|max:255']);
        SprintEquipe::updateOrCreate(['sprint_id' => $sprint->id, 'user_id' => $user->id], ['statut' => 'retire', 'motif' => $v['motif'] ?? null, 'par_id' => $r->user()->id]);
        $ouvertes = $sprint->taches()->where('user_id', $user->id)->whereNotIn('statut', ['termine', 'reporte'])->count();
        AuditLog::log('retrait_equipe_sprint', 'sprint', $sprint->projet->code . ' S' . $sprint->numero, $user->nomComplet() . ($v['motif'] ?? null ? ' - ' . $v['motif'] : ''));
        return back()->with('succes', $user->nomComplet() . " retiré(e) du sprint {$sprint->numero}."
            . ($ouvertes ? " Attention : {$ouvertes} tâche(s) non terminée(s) lui restent attribuées." : ''));
    }

    /** Annule le retrait : le collaborateur revient dans l'équipe du sprint. */
    public function reintegrerMembre(Request $r, Sprint $sprint, User $user)
    {
        abort_unless($this->gerer($r, $sprint), 403);
        SprintEquipe::where('sprint_id', $sprint->id)->where('user_id', $user->id)->where('statut', 'retire')->delete();
        if (!ScrumService::membres($sprint)->contains('id', $user->id)) {
            SprintEquipe::create(['sprint_id' => $sprint->id, 'user_id' => $user->id, 'statut' => 'ajoute', 'par_id' => $r->user()->id]);
        }
        AuditLog::log('ajout_equipe_sprint', 'sprint', $sprint->projet->code . ' S' . $sprint->numero, $user->nomComplet() . ' - réintégré(e)');
        return back()->with('succes', $user->nomComplet() . " réintègre l'équipe du sprint {$sprint->numero}.");
    }

    private function notifierMembre(int $userId, string $message, string $lien): void
    {
        if ($userId === auth()->id() || !Schema::hasTable('notifications_app')) return;
        NotificationApp::create(['user_id' => $userId, 'type' => 'projet', 'contenu' => $message, 'lien' => $lien, 'lu' => false, 'canal' => 'in_app']);
    }

    /* ═══════ ARCHIVE EXCEL ═══════ */

    public function archive(Request $r, Sprint $sprint)
    {
        $this->voir($r, $sprint);
        $nom = 'ARCHIVE_SCRUM_' . Str::upper(Str::slug($sprint->projet->code ?: $sprint->projet->libelle, '_')) . "_SPRINT_{$sprint->numero}_" . now()->format('Ymd') . '.xlsx';
        AuditLog::log('export_archive_scrum', 'sprint', $sprint->projet->code . ' S' . $sprint->numero, $nom);
        return ArchiveScrum::sprint($sprint)->telecharger($nom);
    }
}
