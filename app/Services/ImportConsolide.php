<?php
namespace App\Services;

use App\Models\{CompteRendu, DailyScrum, DailyScrumEntree, Projet, ProjetCollaborateur, Sprint, Tache, User, UserStory};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import d'un classeur Scrum consolidé (.xlsx) couvrant plusieurs sprints, dans l'ordre des onglets :
 * Product Backlog, puis pour chaque sprint « Backlog Sprint N », « Sprint N Planning », « DAILY n jj-mm-aaaa »,
 * « REVIEW SPRINT N », « RETROSPECTIVE N ».
 *
 * Les données produites sont celles de la saisie dans l'application : Sprint Backlog (stories du sprint),
 * tâches du Sprint Planning, Dailys publiés avec leurs lignes, Review et Rétrospective validées.
 * Réimporter le même fichier ne crée pas de doublon ; un sprint déjà clôturé dans l'application n'est pas modifié.
 */
class ImportConsolide
{
    private LecteurXlsx $lecteur;
    private string $chemin;
    /** @var array<string, UserStory> numéro normalisé → story */
    private array $stories = [];
    /** @var array<int, array{user: User, nom: string[], tous: string[]}> */
    private array $index = [];
    private array $nomsInconnus = [];
    private array $membres = [];
    /** Sprints créés par cet import (historique) : leurs stories y entrent sans les règles du puisage. */
    private array $sprintsCrees = [];

    public array $rapport = [
        'sprints_crees' => 0, 'sprints_maj' => 0,
        'stories_crees' => 0, 'stories_maj' => 0, 'stories_engagees' => 0,
        'taches_crees' => 0, 'taches_maj' => 0,
        'dailys_crees' => 0, 'entrees_daily' => 0,
        'reviews_crees' => 0, 'retros_crees' => 0,
        'erreurs' => [],
    ];

    public function __construct(private Projet $projet)
    {
        foreach (User::where('actif', true)->get() as $u) {
            $this->index[] = ['user' => $u, 'nom' => self::mots($u->name), 'tous' => self::mots($u->nomComplet())];
        }
    }

    public function importer(string $chemin): array
    {
        $this->chemin = $chemin;
        $this->lecteur = new LecteurXlsx($chemin);
        $plan = $this->plan($this->lecteur->feuilles());
        if (!$plan['backlog'] && !$plan['sprints']) {
            throw new \RuntimeException('Aucun onglet Scrum reconnu (Product Backlog, Sprint N Planning, DAILY…, REVIEW SPRINT N, RETROSPECTIVE N).');
        }

        UserStory::$signaler = false;
        try {
            return DB::transaction(function () use ($plan) {
                if ($plan['backlog']) $this->productBacklog();
                $this->indexerStories();
                $dernier = $plan['sprints'] ? max(array_keys($plan['sprints'])) : null;
                foreach ($plan['sprints'] as $num => $onglets) $this->sprint($num, $onglets, $num === $dernier);
                $this->ajouterMembres();
                if ($this->nomsInconnus) {
                    $this->erreur('Collaborateurs non reconnus (tâches attribuées à vous, lignes de daily ignorées) : ' . implode(', ', array_keys($this->nomsInconnus)) . '.');
                }
                return $this->rapport;
            });
        } finally {
            UserStory::$signaler = true;
        }
    }

    /* ─── Plan du classeur ─── */

    /** Onglets classés par sprint ; un daily appartient au sprint dont les onglets le précèdent. */
    private function plan(array $feuilles): array
    {
        $plan = ['backlog' => null, 'sprints' => []];
        $courant = null;
        foreach ($feuilles as $nom) {
            $n = self::norm($nom);
            if (str_contains($n, 'product backlog')) { $plan['backlog'] = $nom; continue; }
            if (preg_match('/backlog\s*sprint\s*(\d+)/', $n, $m)) { $plan['sprints'][$courant = (int) $m[1]]['backlog'] = $nom; continue; }
            if (preg_match('/sprint\s*(\d+)\s*planning|planning\s*(?:sprint\s*)?(\d+)/', $n, $m)) { $plan['sprints'][$courant = (int) ($m[1] !== '' ? $m[1] : $m[2])]['planning'] = $nom; continue; }
            if (preg_match('/review\s*(?:sprint\s*)?(\d+)/', $n, $m)) { $plan['sprints'][$courant = (int) $m[1]]['review'] = $nom; continue; }
            if (preg_match('/retro(?:spective)?\s*(?:sprint\s*)?(\d+)/', $n, $m)) { $plan['sprints'][$courant = (int) $m[1]]['retro'] = $nom; continue; }
            if (preg_match('/^dail/', $n)) {
                if ($courant === null) { $this->erreur("Onglet « {$nom} » : daily placé avant tout sprint, ignoré."); continue; }
                $plan['sprints'][$courant]['dailys'][] = $nom;
            }
        }
        ksort($plan['sprints']);
        return $plan;
    }

    /* ─── Product Backlog ─── */

    private function productBacklog(): void
    {
        $import = new ImportBacklog($this->projet);
        $r = $import->importer($this->chemin, 'maj');
        $this->rapport['stories_crees'] += $r['crees'];
        $this->rapport['stories_maj'] += $r['maj'];
        foreach ($r['remarques'] as $rq) $this->erreur("Product Backlog : {$rq}");
    }

    private function indexerStories(): void
    {
        $this->stories = [];
        foreach ($this->projet->userStories()->get() as $us) $this->stories[self::cle($us->numero)] = $us;
    }

    /** Story désignée par une référence (« US-009 », « US-ROLE-04 (…) — En tant que… »). */
    private function story(?string $texte): ?UserStory
    {
        $texte = trim((string) $texte);
        if ($texte === '') return null;
        if (preg_match('/^\s*([A-Z][A-Z0-9]*(?:[\s.\-][A-Z0-9]+)*?\d[A-Z0-9.\-]*)/u', $texte, $m) && isset($this->stories[self::cle($m[1])])) {
            return $this->stories[self::cle($m[1])];
        }
        if (preg_match_all('/\bUS[\s\-]?[A-Z0-9][A-Z0-9.\-]*/u', $texte, $mm)) {
            foreach ($mm[0] as $ref) if (isset($this->stories[self::cle(rtrim($ref, '.-'))])) return $this->stories[self::cle(rtrim($ref, '.-'))];
        }
        return null;
    }

    /* ─── Sprint ─── */

    private function sprint(int $num, array $onglets, bool $dernier): void
    {
        $meta = $this->metaSprint($onglets);
        $sprint = $this->projet->sprints()->where('numero', $num)->first();
        $cree = false;
        $termine = !$dernier || isset($onglets['review']) || isset($onglets['retro']);

        if (!$sprint) {
            if (!$meta['debut'] || !$meta['fin']) { $this->erreur("Sprint {$num} : dates de début et de fin introuvables, sprint ignoré."); return; }
            $enCours = $this->projet->sprints()->where('statut', 'en_cours')->exists();
            $statut = $termine ? 'termine' : ($meta['debut']->isFuture() || $enCours ? 'planifie' : 'en_cours');
            // Ouvert le temps de l'import (Sprint Backlog, statuts des stories), clôturé ensuite
            $sprint = Sprint::create([
                'projet_id' => $this->projet->id, 'numero' => $num, 'objectif' => $meta['objectif'],
                'date_debut' => $meta['debut'], 'date_fin' => $meta['fin'],
                'statut' => $statut === 'termine' ? 'en_cours' : $statut,
            ]);
            $cree = true;
            $this->rapport['sprints_crees']++;
            $this->sprintsCrees[$sprint->id] = true;
        } else {
            $maj = array_filter([
                'objectif' => !$sprint->objectif ? $meta['objectif'] : null,
            ]);
            if ($maj) { $sprint->update($maj); $this->rapport['sprints_maj']++; }
        }

        if ($sprint->statut === 'termine') {
            $this->erreur("Sprint {$num} : déjà clôturé dans l'application, Sprint Backlog et planning conservés tels quels.");
        } else {
            if (isset($onglets['backlog'])) $this->sprintBacklog($sprint, $onglets['backlog']);
            if (isset($onglets['planning'])) $this->planning($sprint, $onglets['planning']);
        }
        $rang = 0;
        foreach ($onglets['dailys'] ?? [] as $nom) $this->daily($sprint, $nom, ++$rang);
        if (isset($onglets['review'])) $this->review($sprint, $onglets['review']);
        if (isset($onglets['retro'])) $this->retro($sprint, $onglets['retro']);

        if ($cree && $termine) $this->cloturer($sprint);
    }

    /** Objectif et dates : onglet Planning, sinon Backlog du sprint, sinon Review. */
    private function metaSprint(array $onglets): array
    {
        $meta = ['objectif' => null, 'debut' => null, 'fin' => null];
        foreach (['planning', 'backlog', 'review'] as $type) {
            if (!isset($onglets[$type])) continue;
            foreach (array_slice($this->lecteur->lignesFeuille($onglets[$type]), 0, 14, true) as $c) {
                $vals = self::valeurs($c);
                $txt = $vals[0] ?? '';
                $n = self::norm($txt);
                if (!$meta['objectif'] && str_starts_with($n, 'objectif')) {
                    $obj = count($vals) > 1 && !str_contains($txt, ':') ? $vals[1] : preg_replace('/^[^:]*:\s*/u', '', $txt);
                    $meta['objectif'] = trim($obj, " \t«»\"“”") ?: null;
                }
                if (!$meta['debut'] && preg_match_all('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $txt, $d, PREG_SET_ORDER) && count($d) >= 2
                    && (str_contains($n, 'debut') || str_contains($txt, '→') || str_contains($n, 'sprint'))) {
                    $meta['debut'] = Carbon::create((int) $d[0][3], (int) $d[0][2], (int) $d[0][1])->startOfDay();
                    $meta['fin'] = Carbon::create((int) $d[1][3], (int) $d[1][2], (int) $d[1][1])->startOfDay();
                }
            }
            if ($meta['objectif'] && $meta['debut']) break;
        }
        return $meta;
    }

    /** Onglet « Backlog Sprint N » : stories engagées dans le sprint (créées si absentes du Product Backlog). */
    private function sprintBacklog(Sprint $sprint, string $onglet): void
    {
        $lignes = $this->lecteur->lignesFeuille($onglet);
        [$entete, $cols] = $this->entete($lignes, ['story' => ['user story'], 'module' => ['module', 'fonctionnalite'], 'exigences' => ['exigence']], 'story');
        if ($entete === null) { $this->erreur("{$onglet} : colonne « User Story » introuvable."); return; }
        $epic = null;
        foreach ($lignes as $num => $c) {
            if ($num <= $entete) continue;
            if (self::fusionnee($c)) { $epic = $this->libelleGroupe(self::valeurs($c)[0]); continue; }
            $texte = trim((string) ($c[$cols['story']] ?? ''));
            if ($texte === '') continue;
            $us = $this->story($texte);
            if (!$us) {
                // Élément du périmètre absent du Product Backlog : « US 3.2 — … », « TECH 1 — … », ou intitulé seul
                $numero = null;
                $intitule = $texte;
                if (preg_match('/^\s*((?:US|TECH|[A-Z]{2,6})[\s\-]?[A-Z0-9]*\d[A-Z0-9.\-]*)\s*(?:\([^)]*\))?\s*[—–:\-]+\s*(.+)$/su', $texte, $m)) {
                    [$numero, $intitule] = [Str::limit(trim($m[1]), 20, ''), trim($m[2])];
                }
                $us = $this->storyParIntitule($intitule);
                if (!$us) {
                    $module = Str::limit(trim((string) ($c[$cols['module'] ?? -1] ?? '')), 147) ?: null;
                    if (!$numero || isset($this->stories[self::cle($numero)])) $numero = UserStory::prochainNumero($this->projet, $module);
                    $us = $this->projet->userStories()->create([
                        'numero' => $numero, 'intitule' => Str::limit($intitule, 2000, ''), 'module' => $module, 'epic' => $epic,
                        'exigences' => isset($cols['exigences']) && trim((string) ($c[$cols['exigences']] ?? '')) !== '' ? TexteRiche::depuisTexte($c[$cols['exigences']]) : null,
                        'priorite' => 'moyenne', 'statut' => 'a_faire', 'origine' => 'import', 'dor' => true,
                    ]);
                    $this->stories[self::cle($us->numero)] = $us;
                    $this->rapport['stories_crees']++;
                }
            }
            $this->engager($sprint, $us);
        }
    }

    /**
     * Engage la story dans le sprint. Un sprint qui existait déjà dans l'application suit les règles du puisage
     * (DoR = Oui, story non achevée, engagée dans aucun autre sprint ouvert) ; sinon la story n'est pas engagée.
     */
    private function engager(Sprint $sprint, UserStory $us): bool
    {
        if ($sprint->userStories()->whereKey($us->id)->exists()) return true;
        if (!isset($this->sprintsCrees[$sprint->id]) && !UserStory::whereKey($us->id)->disponibles()->exists()) {
            $this->erreur("Sprint {$sprint->numero} : {$us->numero} non engagée (DoR = Non, déjà achevée ou engagée dans un autre sprint ouvert) ; ses tâches restent hors user story.");
            return false;
        }
        $sprint->userStories()->attach($us->id, ['statut_sprint' => 'a_faire']);
        $this->rapport['stories_engagees']++;
        return true;
    }

    /** Onglet « Sprint N Planning » : tâches, rattachées à leur story (colonne ou ligne de regroupement). */
    private function planning(Sprint $sprint, string $onglet): void
    {
        $lignes = $this->lecteur->lignesFeuille($onglet);
        [$entete, $cols] = $this->entete($lignes, [
            'tache' => ['tache'], 'story' => ['user story', 'story'], 'assigne' => ['attribution', 'assigne', 'responsable'],
            'debut' => ['debut'], 'fin' => ['date de fin', 'fin', 'echeance'], 'statut' => ['statut', 'etat'], 'points' => ['point'],
        ], 'tache');
        if ($entete === null) { $this->erreur("{$onglet} : colonne « Tâches » introuvable."); return; }

        $cle = fn($intitule, $userId) => self::norm($intitule) . '|' . $userId;
        $toutes = $sprint->taches()->get();
        $existantes = $toutes->groupBy(fn($t) => $cle($t->intitule, $t->user_id));
        // Code de tâche (« T-007 — … ») : une tâche dont l'intitulé a été retouché dans le classeur est reconnue par son code
        $code = fn($intitule) => preg_match('/^\s*(T-\d+)\b/i', (string) $intitule, $m) ? strtoupper($m[1]) : null;
        $parCode = $toutes->filter(fn($t) => $code($t->intitule))->groupBy(fn($t) => $code($t->intitule) . '|' . $t->user_id);
        $storyGroupe = null;
        // Équipe du sprint (collaborateurs nommés dans le planning) : une tâche « Toute l'équipe » est confiée à chacun
        $equipe = collect($lignes)->filter(fn($c, $num) => $num > $entete)->map(fn($c) => $this->utilisateur($c[$cols['assigne'] ?? -1] ?? null, false))->filter()->unique('id')->values();

        foreach ($lignes as $num => $c) {
            if ($num <= $entete) continue;
            $get = fn($k) => isset($cols[$k]) ? trim((string) ($c[$cols[$k]] ?? '')) : '';
            if (self::fusionnee($c)) { $storyGroupe = $this->story(self::valeurs($c)[0]); continue; }
            $intitule = $get('tache');
            if ($intitule === '' || preg_match('/^(total|l[ée]gende|statuts?\s*:)/iu', $intitule)) continue;
            // Ligne de regroupement non fusionnée (processus, story sans tâche) : ni attribution, ni date, ni statut
            if ($get('assigne') === '' && $get('debut') === '' && $get('statut') === '') {
                $storyGroupe = $this->story($get('story')) ?? $this->story($intitule) ?? $this->storyParIntitule($get('story')) ?? $storyGroupe;
                continue;
            }

            if (in_array(self::norm($get('assigne')), ['attribution', 'assigne'], true)) continue; // en-tête répété

            $us = $this->story($get('story')) ?? $storyGroupe;
            if ($us && !$this->engager($sprint, $us)) $us = null;
            $nAssigne = self::norm($get('assigne'));
            $pourTous = $equipe->isNotEmpty() && preg_match('/^(toute?s?\s+l.?\s*equipe|l.?\s*equipe|equipe|tous|toutes)$/', $nAssigne);
            $users = $pourTous ? $equipe->all() : [$this->utilisateur($get('assigne'))];

            [$statut, $teste] = self::statutTache($get('statut'));
            $debut = self::date($get('debut')) ?? $sprint->date_debut->copy();
            $fin = self::date($get('fin')) ?? $debut->copy();
            if ($fin->lt($debut)) $fin = $debut->copy();
            $intitule = Str::limit(preg_replace('/\s+/u', ' ', $intitule), 250, '…');

            foreach ($users as $user) {
                $userId = $user?->id ?? auth()->id();
                if ($user) $this->membres[$user->id] = min($this->membres[$user->id] ?? $sprint->date_debut->toDateString(), $sprint->date_debut->toDateString());
                if (($lot = $existantes->get($cle($intitule, $userId))) && $lot->isNotEmpty()) {
                    $t = $lot->shift();
                    if ($us && $t->user_story_id !== $us->id) { $t->update(['user_story_id' => $us->id]); $this->rapport['taches_maj']++; }
                    continue;
                }
                // Même code, même collaborateur, intitulé reformulé : la tâche est mise à jour (son statut ne change pas)
                if (($c = $code($intitule)) && ($lot = $parCode->get($c . '|' . $userId)) && $lot->isNotEmpty()) {
                    $t = $lot->shift();
                    $t->update(array_filter(['intitule' => $intitule, 'user_story_id' => $us?->id], fn($v) => $v !== null));
                    $this->rapport['taches_maj']++;
                    continue;
                }
                Tache::create([
                    'user_id' => $userId, 'projet_id' => $this->projet->id, 'sprint_id' => $sprint->id, 'user_story_id' => $us?->id,
                    'type' => 'T1', 'intitule' => $intitule, 'priorite' => 'moyenne', 'statut' => $statut, 'teste' => $teste,
                    'date_debut' => $debut->toDateString(), 'echeance' => $fin->toDateString(),
                    'date_fin_reelle' => $statut === 'termine' ? $fin->toDateString() : null,
                    'points' => is_numeric($get('points')) ? max(0, min(255, (int) $get('points'))) : null,
                ]);
                $this->rapport['taches_crees']++;
            }
        }
    }

    private function storyParIntitule(string $texte): ?UserStory
    {
        $n = mb_substr(self::norm($texte), 0, 60);
        if (mb_strlen($n) < 20) return null;
        foreach ($this->stories as $us) if (str_starts_with(self::norm($us->intitule), $n)) return $us;
        return null;
    }

    /** Clôture d'un sprint importé : tâches qualifiées, stories figées (sans justification demandée : c'est l'historique). */
    private function cloturer(Sprint $sprint): void
    {
        $cloture = new ClotureService();
        foreach ($sprint->taches()->get() as $t) {
            if ($t->statut === 'termine') { if (!$t->qualification) $cloture->cloturer($t, $t->date_fin_reelle ? Carbon::parse($t->date_fin_reelle) : $t->echeance); }
            elseif ($t->statut !== 'reporte') $t->update(['qualification' => 'non_termine_sprint', 'justification_requise' => false]);
        }
        $sprint->update(['statut' => 'termine']);
        foreach ($sprint->userStories()->pluck('user_stories.id') as $id) AvancementScrum::synchroniser($id);
        $sprint->figerBacklog();
    }

    /* ─── Daily ─── */

    private function daily(Sprint $sprint, string $onglet, int $rang): void
    {
        $lignes = $this->lecteur->lignesFeuille($onglet);
        $meta = [];
        foreach (array_slice($lignes, 0, 12, true) as $c) {
            $c = array_values(array_filter($c, fn($v) => trim((string) $v) !== ''));
            foreach ($c as $i => $v) {
                $lib = self::norm(rtrim(trim((string) $v), ' :'));
                if (!in_array($lib, ['objet', 'facilitateur', 'scribe', 'gardien du temps', 'observateur', 'date', 'heure debut', 'heure fin'], true)) continue;
                $suivante = $c[$i + 1] ?? null;
                if ($suivante !== null && !str_ends_with(trim((string) $suivante), ':')) $meta[$lib] = trim((string) $suivante);
            }
        }
        $date = self::date($meta['date'] ?? null);
        if (!$date && preg_match('/(\d{1,2})-(\d{1,2})-(\d{4})/', $onglet, $m)) $date = Carbon::create((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
        if (!$date) { $this->erreur("{$onglet} : date du daily introuvable, onglet ignoré."); return; }
        if ($sprint->dailyScrums()->whereDate('date_reunion', $date)->exists()) return;

        $roles = [];
        foreach (['facilitateur' => 'facilitateur', 'scribe' => 'scribe', 'gardien du temps' => 'gardien_temps', 'observateur' => 'observateur'] as $lib => $code) {
            if (!empty($meta[$lib]) && ($u = $this->utilisateur($meta[$lib]))) $roles[$code] = $u->id;
        }

        // Tableau des participants
        [$entete, $cols] = $this->entete($lignes, [
            'nom' => ['nom', 'participant'], 'hier' => ['bilan', 'hier', 'precedent', 'realise'],
            'reste' => ['reste'], 'jour' => ['detail', 'du jour', 'aujourd', 'a faire'], 'defis' => ['defi', 'blocage', 'observation'],
        ], 'nom');
        $commentaire = $observation = null;
        $entrees = [];
        if ($entete !== null) {
            foreach ($lignes as $num => $c) {
                if ($num <= $entete) continue;
                $vals = self::valeurs($c);
                $premier = $vals[0] ?? '';
                $np = self::norm($premier);
                if (str_starts_with($np, 'commentaire') || str_starts_with($np, 'observation')) {
                    $texte = str_contains($premier, ':') && trim(Str::after($premier, ':')) !== '' ? trim(Str::after($premier, ':')) : ($vals[1] ?? '');
                    if (str_starts_with($np, 'commentaire')) $commentaire = self::riche($texte); else $observation = self::riche($texte);
                    continue;
                }
                $nom = trim((string) ($c[$cols['nom']] ?? ''));
                if ($nom === '' || $commentaire !== null || $observation !== null) continue;
                $u = $this->utilisateur($nom);
                if (!$u || isset($entrees[$u->id])) continue;
                $get = fn($k) => isset($cols[$k]) ? trim((string) ($c[$cols[$k]] ?? '')) : '';
                $entrees[$u->id] = [
                    'user_id' => $u->id, 'taches_hier' => self::riche($get('hier')), 'taches_jour' => self::riche($get('jour')),
                    'duree_restante' => $get('reste') !== '' ? Str::limit($get('reste'), 20, '') : null, 'defis' => self::riche($get('defis')),
                ];
            }
        }

        $publication = $date->copy()->setTimeFromTimeString(self::heure($meta['heure fin'] ?? null) ?? '09:15');
        // Rang du jour ouvré dans le sprint ; un daily tenu après la fin prévue prend le numéro suivant
        $numero = $sprint->date_debut->lte($date) ? ScrumService::numeroJour($sprint, $date) : $rang;
        if ($sprint->dailyScrums()->where('numero_jour', $numero)->exists()) $numero = (int) $sprint->dailyScrums()->max('numero_jour') + 1;
        $daily = DailyScrum::create([
            'sprint_id' => $sprint->id, 'projet_id' => $this->projet->id,
            'objet' => !empty($meta['objet']) ? Str::limit($meta['objet'], 250, '') : null,
            'numero_jour' => $numero,
            'date_reunion' => $date, 'heure_debut' => self::heure($meta['heure debut'] ?? null), 'heure_fin' => self::heure($meta['heure fin'] ?? null),
            'roles' => $roles, 'commentaire_general' => $commentaire, 'observation' => $observation,
            'cree_par' => auth()->id(), 'publie_le' => $publication, 'publie_par' => $roles['scribe'] ?? auth()->id(),
        ]);
        foreach ($entrees as $e) DailyScrumEntree::create($e + ['daily_scrum_id' => $daily->id]);
        $this->rapport['dailys_crees']++;
        $this->rapport['entrees_daily'] += count($entrees);
    }

    /* ─── Review ─── */

    private function review(Sprint $sprint, string $onglet): void
    {
        if (CompteRendu::where('sprint_id', $sprint->id)->where('type', 'review_sprint')->duModuleScrum()->exists()) return;
        $lignes = $this->lecteur->lignesFeuille($onglet);
        [$entete, $cols] = $this->entete($lignes, ['code' => ['n°', 'n', 'no'], 'tache' => ['tache'], 'story' => ['user story'], 'statut' => ['statut'], 'obs' => ['observation']], 'tache');

        $tenue = null;
        $sections = [];
        $decisions = null;
        foreach ($lignes as $num => $c) {
            $vals = self::valeurs($c);
            $lib = self::norm($vals[0] ?? '');
            if ($lib === 'tenue le') $tenue = $vals[1] ?? null;
            if ($entete !== null && $num > $entete && !self::fusionnee($c) && count($vals) === 2 && trim((string) ($c[0] ?? '')) !== '' && !preg_match('/^(t-\d+|\+|\d)/', $lib)) {
                if (str_contains($lib, 'decision')) $decisions = $vals[1]; else $sections[$vals[0]] = $vals[1];
            }
        }

        // Observations par tâche (code « T-007 » en tête de l'intitulé) ; lignes « + » : travaux hors planning
        $parCode = [];
        foreach ($sprint->taches()->get() as $t) if (preg_match('/^\s*(T-\d+)/i', $t->intitule, $m)) $parCode[strtoupper($m[1])] = $t->id;
        $observations = [];
        $horsPlanning = [];
        if ($entete !== null) {
            foreach ($lignes as $num => $c) {
                if ($num <= $entete || self::fusionnee($c)) continue;
                $get = fn($k) => isset($cols[$k]) ? trim((string) ($c[$cols[$k]] ?? '')) : '';
                $code = strtoupper($get('code'));
                $intitule = $get('tache');
                if ($intitule === '') continue;
                if (isset($parCode[$code])) { if ($get('obs') !== '') $observations[(string) $parCode[$code]] = self::riche($get('obs')); continue; }
                if ($code === '+' || $code === '' || !preg_match('/^T-\d+/', $code)) {
                    $horsPlanning[] = $intitule . ($get('obs') !== '' ? ' — ' . $get('obs') : '');
                }
            }
        }

        $synthese = '';
        if ($tenue) $synthese .= '<p><strong>Tenue le</strong> : ' . e($tenue) . '</p>';
        foreach ($sections as $lib => $txt) if (!in_array(self::norm($lib), ['resultat', 'objectif du sprint'], true)) $synthese .= '<p><strong>' . e($lib) . '</strong></p>' . self::riche($txt);
        if ($horsPlanning) $synthese .= '<p><strong>Travaux réalisés hors planning</strong></p>' . TexteRiche::liste($horsPlanning);

        $cr = CompteRendu::create([
            'type' => 'review_sprint', 'sprint_id' => $sprint->id,
            'titre' => "Review - Sprint {$sprint->numero} - {$this->projet->libelle}",
            'redacteur_id' => auth()->id(), 'designe_par' => auth()->id(),
            'date_reunion' => self::dateDansTexte($tenue) ?? $sprint->date_fin, 'statut' => 'confirme', 'donnees_json' => '{}',
        ]);
        $cr->setDonnees(array_filter([
            'observations' => $observations, 'synthese' => TexteRiche::nettoyer($synthese), 'decisions' => $decisions ? self::riche($decisions) : null,
            'backlog' => [], 'source' => 'import_consolide',
        ], fn($v) => $v !== null));
        $cr->save();
        $this->rapport['reviews_crees']++;
    }

    /* ─── Rétrospective ─── */

    private function retro(Sprint $sprint, string $onglet): void
    {
        if (CompteRendu::where('sprint_id', $sprint->id)->where('type', 'retrospective')->duModuleScrum()->exists()) return;
        $lignes = $this->lecteur->lignesFeuille($onglet);
        [$entete, $cols] = $this->entete($lignes, ['theme' => ['theme'], 'bien' => ['bien march'], 'pas_bien' => ['pas march'], 'causes' => ['cause'], 'actions' => ['action']], 'theme');
        $tenue = $bilan = null;
        foreach ($lignes as $num => $c) {
            $vals = self::valeurs($c);
            $lib = self::norm($vals[0] ?? '');
            if ($lib === 'tenue le') $tenue = $vals[1] ?? null;
            if (str_starts_with($lib, 'bilan')) $bilan = $vals[1] ?? null;
        }
        $lignesRetro = [];
        if ($entete !== null) {
            foreach ($lignes as $num => $c) {
                if ($num <= $entete || self::fusionnee($c)) continue;
                $get = fn($k) => isset($cols[$k]) ? trim((string) ($c[$cols[$k]] ?? '')) : '';
                $l = ['theme' => Str::limit($get('theme'), 250, ''), 'bien' => self::riche($get('bien')), 'pas_bien' => self::riche($get('pas_bien')), 'causes' => self::riche($get('causes')), 'actions' => self::riche($get('actions'))];
                if ($l['bien'] . $l['pas_bien'] . $l['causes'] . $l['actions'] !== '') $lignesRetro[] = $l;
            }
        }
        if (!$lignesRetro) { $this->erreur("{$onglet} : matrice de rétrospective vide ou introuvable."); return; }
        $cr = CompteRendu::create([
            'type' => 'retrospective', 'sprint_id' => $sprint->id,
            'titre' => "Rétrospective - Sprint {$sprint->numero} - {$this->projet->libelle}",
            'redacteur_id' => auth()->id(), 'designe_par' => auth()->id(),
            'date_reunion' => self::dateDansTexte($tenue) ?? $sprint->date_fin, 'statut' => 'confirme', 'donnees_json' => '{}',
        ]);
        $cr->setDonnees(array_filter(['lignes' => $lignesRetro, 'bilan' => $bilan ? self::riche($bilan) : null, 'source' => 'import_consolide']));
        $cr->save();
        $this->rapport['retros_crees']++;
    }

    /* ─── Équipe ─── */

    /** Les collaborateurs du planning rejoignent l'équipe du projet (à partir de leur premier sprint). */
    private function ajouterMembres(): void
    {
        foreach ($this->membres as $userId => $depuis) {
            $c = ProjetCollaborateur::firstOrNew(['projet_id' => $this->projet->id, 'user_id' => $userId]);
            if ($c->exists && $c->actif) {
                if ($c->date_ajout && Carbon::parse($c->date_ajout)->gt(Carbon::parse($depuis))) $c->update(['date_ajout' => $depuis]);
                continue;
            }
            $c->fill(['role_projet' => $c->role_projet ?: 'developpeur', 'actif' => true, 'date_ajout' => $depuis, 'date_retrait' => null])->save();
        }
    }

    /**
     * Collaborateur désigné par son nom tel qu'écrit dans le classeur (« ZANFACK TSOPKENG Durel Manson »,
     * « KOUASSI ANGE-ELISEE ») : nom de famille présent, puis le plus de mots en commun ; ambigu = non reconnu.
     */
    private function utilisateur(?string $nom, bool $signaler = true): ?User
    {
        $mots = self::mots($nom);
        if (!$mots) return null;
        if (!$signaler) {
            $avant = $this->nomsInconnus;
            $u = $this->utilisateur($nom);
            $this->nomsInconnus = $avant;
            return $u;
        }
        $meilleur = null; $score = 0; $egalite = false;
        foreach ($this->index as $i) {
            if (array_diff($i['nom'], $mots)) continue;
            $s = count(array_intersect($i['tous'], $mots));
            if ($s > $score) { $meilleur = $i['user']; $score = $s; $egalite = false; }
            elseif ($s === $score) $egalite = true;
        }
        if (!$meilleur || $egalite) { $this->nomsInconnus[trim((string) $nom)] = true; return null; }
        return $meilleur;
    }

    /* ─── Lecture ─── */

    /**
     * Ligne d'en-tête : la première dont une cellule correspond à la colonne obligatoire.
     * @param array<string, string[]> $colonnes champ → mots-clés (premier champ trouvé prioritaire pour une cellule)
     */
    private function entete(array $lignes, array $colonnes, string $obligatoire): array
    {
        foreach (array_slice($lignes, 0, 40, true) as $num => $c) {
            if (self::fusionnee($c)) continue;
            $cols = [];
            foreach ($c as $i => $v) {
                $h = self::norm($v);
                if ($h === '' || mb_strlen($h) > 50) continue;
                foreach ($colonnes as $champ => $mots) {
                    if (isset($cols[$champ])) continue;
                    foreach ($mots as $mot) {
                        if (mb_strlen($mot) <= 3 ? $h === $mot : str_contains($h, $mot)) { $cols[$champ] = $i; continue 3; }
                    }
                }
            }
            if (isset($cols[$obligatoire]) && count($cols) >= 2) return [$num, $cols];
        }
        return [null, []];
    }

    private function libelleGroupe(string $texte): ?string
    {
        $t = preg_replace(['/^(?:section\s*\d+|ep[\s\-]*[A-Z0-9\-]+)\s*[—–:\-]\s*/iu', '/\s*[—–-]\s*\d+\s*user stor.*$/iu', '/\s*\([^()]*\)\s*$/u'], '', trim($texte));
        return $t !== '' ? Str::limit($t, 147) : null;
    }

    /** Valeurs non vides d'une ligne, sans les répétitions des cellules fusionnées. */
    private static function valeurs(array $c): array
    {
        return array_values(array_unique(array_filter(array_map(fn($v) => trim((string) $v), $c), fn($v) => $v !== '')));
    }

    /** Ligne de titre ou de regroupement : une seule valeur répétée sur plusieurs cellules fusionnées. */
    private static function fusionnee(array $c): bool
    {
        $pleines = array_filter(array_map(fn($v) => trim((string) $v), $c), fn($v) => $v !== '');
        return count($pleines) > 1 && count(array_unique($pleines)) === 1;
    }

    private static function statutTache(string $v): array
    {
        $n = self::norm($v);
        return match (true) {
            $n === 'tt' || str_contains($n, 'teste') => ['termine', true],
            $n === 't' || str_contains($n, 'termin') || str_contains($n, 'realis') => ['termine', false],
            in_array($n, ['e', 'd', 'er', 'ec'], true) || str_contains($n, 'cours') || str_contains($n, 'demarr') || str_contains($n, 'retard') => ['en_cours', false],
            $n === 'b' || str_contains($n, 'bloqu') => ['bloque', false],
            str_contains($n, 'report') => ['reporte', false],
            default => ['a_faire', false],
        };
    }

    /** Date Excel (numéro de série) ou texte jj/mm/aaaa. */
    private static function date($v): ?Carbon
    {
        $v = trim((string) $v);
        if ($v === '') return null;
        if (is_numeric($v) && (float) $v > 30000 && (float) $v < 80000) return Carbon::create(1899, 12, 30)->addDays((int) floor((float) $v));
        return self::dateDansTexte($v);
    }

    private static function dateDansTexte(?string $v): ?Carbon
    {
        if ($v && preg_match('/(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/', $v, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
        }
        return null;
    }

    private static function heure(?string $v): ?string
    {
        if ($v === null) return null;
        if (is_numeric($v) && (float) $v < 1) return gmdate('H:i', (int) round((float) $v * 86400));
        return preg_match('/(\d{1,2})\s*[h:]\s*(\d{2})/i', $v, $m) ? sprintf('%02d:%s', $m[1], $m[2]) : null;
    }

    /** Texte du classeur → HTML du cadre de rédaction (puces « ● », « → », « • » une par ligne) ; « Aucun » = vide. */
    private static function riche(?string $v): ?string
    {
        $v = trim(str_replace("\r", '', (string) $v));
        if ($v === '' || preg_match('/^(aucun|aucune|néant|neant|ras|-|—)\.?$/iu', $v)) return null;
        $v = preg_replace('/\s*[●→▪►]\s*/u', "\n• ", $v);
        return TexteRiche::nettoyer(TexteRiche::depuisTexte(trim($v)));
    }

    private static function norm($v): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower(Str::ascii((string) $v))));
    }

    private static function cle(string $numero): string
    {
        return preg_replace('/[\s\-_.]+/', '', mb_strtolower(Str::ascii($numero)));
    }

    /** Mots d'un nom (sans accents, ni tirets, ni particules d'une lettre). */
    private static function mots(?string $nom): array
    {
        return array_values(array_unique(array_filter(preg_split('/[^a-z]+/', mb_strtolower(Str::ascii((string) $nom))), fn($m) => strlen($m) >= 2)));
    }

    private function erreur(string $msg): void
    {
        if (count($this->rapport['erreurs']) < 25) $this->rapport['erreurs'][] = $msg;
    }
}
