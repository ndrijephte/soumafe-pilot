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
        'taches_crees' => 0, 'taches_maj' => 0, 'lignes_ignorees' => 0,
        'dailys_crees' => 0, 'entrees_daily' => 0,
        'reviews_crees' => 0, 'retros_crees' => 0,
        'erreurs' => [],
    ];

    public function __construct(private Projet $projet)
    {
        // Tous les comptes, y compris ceux des collaborateurs partis : l'historique du projet leur reste attribué
        foreach (User::orderByDesc('actif')->get() as $u) {
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

    /**
     * Classeur Scrum (consolidé ou archive d'un sprint) plutôt que simple liste de tâches :
     * un onglet de planning ou de backlog de sprint, ou au moins un onglet de cérémonie (daily, review, rétrospective).
     */
    public static function estClasseurScrum(array $feuilles): bool
    {
        $ceremonies = 0;
        foreach ($feuilles as $nom) {
            $n = self::norm($nom);
            if (preg_match('/sprint\s*\d+\s*(planning|backlog)|(planning|backlog)\s*sprint\s*\d+|sprint planning\s*\d+/', $n)) return true;
            if (preg_match('/^(dail|review|retro)/', $n)) $ceremonies++;
        }
        return $ceremonies >= 1;
    }

    /** Résumé lisible d'un rapport d'import. */
    public static function resume(array $r): string
    {
        return collect([
            $r['sprints_crees'] ? "{$r['sprints_crees']} sprint(s) créé(s)" : null,
            $r['sprints_maj'] ? "{$r['sprints_maj']} sprint(s) complété(s)" : null,
            $r['stories_crees'] ? "{$r['stories_crees']} user story(s) créée(s)" : null,
            $r['stories_maj'] ? "{$r['stories_maj']} user story(s) mise(s) à jour" : null,
            $r['stories_engagees'] ? "{$r['stories_engagees']} engagement(s) en Sprint Backlog" : null,
            $r['taches_crees'] ? "{$r['taches_crees']} tâche(s) créée(s)" : null,
            $r['taches_maj'] ? "{$r['taches_maj']} tâche(s) mise(s) à jour" : null,
            $r['dailys_crees'] ? "{$r['dailys_crees']} daily(s) créé(s)" : null,
            $r['reviews_crees'] ? "{$r['reviews_crees']} review(s)" : null,
            $r['retros_crees'] ? "{$r['retros_crees']} rétrospective(s)" : null,
        ])->filter()->implode(', ') ?: 'aucun élément nouveau (déjà importé)';
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
            if (preg_match('/backlog\s*sprint\s*(\d+)|sprint\s*(\d+)\s*backlog/', $n, $m)) { $plan['sprints'][$courant = (int) ($m[1] !== '' ? $m[1] : $m[2])]['backlog'] = $nom; continue; }
            if (preg_match('/sprint\s*(\d+)\s*planning|planning\s*(?:sprint\s*)?(\d+)/', $n, $m)) { $plan['sprints'][$courant = (int) ($m[1] !== '' ? $m[1] : $m[2])]['planning'] = $nom; continue; }
            if (preg_match('/review\s*(?:sprint\s*)?(\d+)/', $n, $m)) { $plan['sprints'][$courant = (int) $m[1]]['review'] = $nom; continue; }
            if (preg_match('/retro(?:spective)?\s*(?:sprint\s*)?(\d+)/', $n, $m)) { $plan['sprints'][$courant = (int) $m[1]]['retro'] = $nom; continue; }
            // « REVIEW SPRINT », « RETROSPECTIVE » sans numéro (archive d'un sprint) : celles du sprint en cours de lecture
            if (preg_match('/^(review|retro)/', $n, $m)) {
                if ($courant === null) { $this->erreur("Onglet « {$nom} » : aucun sprint ne le précède, ignoré."); continue; }
                $plan['sprints'][$courant][$m[1] === 'review' ? 'review' : 'retro'] = $nom;
                continue;
            }
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
        try {
            $r = $import->importer($this->chemin, 'maj');
        } catch (\RuntimeException $e) {
            // Backlog vide ou sans colonne « User Story » (archive d'un sprint) : le reste du classeur est importé
            $this->erreur('Product Backlog non importé : ' . $e->getMessage());
            return;
        }
        $this->rapport['stories_crees'] += $r['crees'];
        $this->rapport['stories_maj'] += $r['maj'];
        foreach ($r['remarques'] as $rq) $this->erreur("Product Backlog : {$rq}");
    }

    private function indexerStories(): void
    {
        $this->stories = [];
        foreach ($this->projet->userStories()->get() as $us) $this->stories[self::cle($us->numero)] = $us;
    }

    /**
     * Story désignée par une référence (« US-009 », « US-ROLE-04 (…) — En tant que… ») et/ou un intitulé.
     * Le numéro seul ne suffit pas quand un intitulé l'accompagne : il doit concorder avec la story du backlog
     * (un planning peut numéroter ses stories à sa façon) ; à défaut, l'intitulé fait foi.
     */
    private function story(?string $texte, ?string $intitule = null): ?UserStory
    {
        $texte = trim((string) $texte);
        $intitule = trim((string) $intitule);
        if ($texte === '' && $intitule === '') return null;
        $ref = null;
        $reste = '';
        if (preg_match('/^\s*([A-Z][A-Z0-9]*(?:[\s.\-][A-Z0-9]+)*?\d[A-Z0-9.\-]*)\s*(?:\([^)]*\))?\s*[—–:\-]*\s*(.*)$/su', $texte, $m)) {
            [$ref, $reste] = [rtrim($m[1], '.-'), trim($m[2])];
        } elseif (preg_match('/\bUS[\s\-]?[A-Z0-9][A-Z0-9.\-]*/u', $texte, $m)) {
            [$ref, $reste] = [rtrim($m[0], '.-'), trim(str_replace($m[0], '', $texte))];
        }
        $stories = collect($this->stories);
        $cle = fn($n) => self::cle((string) $n);
        $us = UserStory::retrouverPourImport($stories, $ref, $intitule ?: $reste, $cle);
        // Référence inconnue : le texte complet est peut-être un intitulé (« API 2 : … »)
        if (!$us && $ref && $intitule === '' && $reste !== '') $us = UserStory::retrouverPourImport($stories, null, $texte, $cle);
        return $us;
    }

    /* ─── Sprint ─── */

    private function sprint(int $num, array $onglets, bool $dernier): void
    {
        $meta = $this->metaSprint($onglets);
        $sprint = $this->projet->sprints()->where('numero', $num)->first();
        $cree = false;
        if ((!$meta['debut'] || !$meta['fin']) && isset($onglets['planning']) && ($d = $this->periodeDesTaches($onglets['planning']))) {
            $meta['debut'] ??= $d[0];
            $meta['fin'] ??= $d[1];
            $this->erreur("Sprint {$num} : période non indiquée, déduite des dates des tâches ({$meta['debut']->format('d/m/Y')} → {$meta['fin']->format('d/m/Y')}).");
        }
        // Clôturé : review ou rétrospective présente, ou période révolue alors qu'un sprint suivant existe ; sinon selon les dates
        $termine = isset($onglets['review']) || isset($onglets['retro'])
            || (!$dernier && $meta['fin'] && $meta['fin']->lt(today()));
        if (!$sprint) {
            if (!$meta['debut'] || !$meta['fin']) { $this->erreur("Sprint {$num} : période « à définir » ou introuvable dans le classeur, sprint non créé. Créez-le avec ses dates (Nouveau sprint), puis réimportez pour y verser son planning."); return; }
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
                'objectif' => (!$sprint->objectif || self::estLibelleMeta($sprint->objectif)) ? $meta['objectif'] : null,
                'date_debut' => $meta['debut'] && $meta['fin'] && $sprint->date_debut->eq($sprint->date_fin) && !$meta['debut']->eq($meta['fin']) ? $meta['debut'] : null,
                'date_fin' => $meta['debut'] && $meta['fin'] && $sprint->date_debut->eq($sprint->date_fin) && !$meta['debut']->eq($meta['fin']) ? $meta['fin'] : null,
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
        // Review et rétrospective : seulement pour un sprint clôturé (dans l'application, ou par cet import)
        $clos = $sprint->statut === 'termine' || ($cree && $termine);
        if (!$clos && (isset($onglets['review']) || isset($onglets['retro']))) {
            $this->erreur("Sprint {$num} : en cours dans l'application, review et rétrospective non importées.");
        } else {
            if (isset($onglets['review'])) $this->review($sprint, $onglets['review']);
            if (isset($onglets['retro'])) $this->retro($sprint, $onglets['retro']);
        }

        if ($cree && $termine) $this->cloturer($sprint);
    }

    /**
     * Objectif et période du sprint, cherchés dans le Planning, puis le Backlog du sprint, la Review et la Rétrospective.
     * Mises en page reconnues :
     *  - texte « Objectif : … », « Date de début : jj/mm/aaaa Date de fin : jj/mm/aaaa », « Sprint 1 — … (jj/mm/aaaa → jj/mm/aaaa) » ;
     *  - libellé puis valeur dans la case voisine (« Objectif du sprint » | texte) ;
     *  - ligne de libellés (« Objectif du sprint », « Date de début », « Date de fin », « Dates et durée ») et valeurs à la ligne suivante.
     * Une valeur qui est elle-même un libellé n'est jamais retenue ; les lignes « Tenue le », « Bilan » sont ignorées.
     */
    private function metaSprint(array $onglets): array
    {
        $meta = ['objectif' => null, 'debut' => null, 'fin' => null];
        foreach (['planning', 'backlog', 'review', 'retro'] as $type) {
            if (!isset($onglets[$type])) continue;
            $lignes = array_values(array_slice($this->lecteur->lignesFeuille($onglets[$type]), 0, 16, true));
            foreach ($lignes as $k => $c) {
                ksort($c);
                $cases = array_filter(array_map(fn($v) => trim((string) $v), $c), fn($v) => $v !== '');
                if (!$cases) continue;
                $premiere = self::norm(reset($cases));
                if (preg_match('/^(tenue|bilan|source|legende|statuts?\s*:|n°?$|n$)/', $premiere)) continue;
                $suivante = $lignes[$k + 1] ?? [];
                $vues = [];
                foreach ($cases as $i => $v) {
                    if (isset($vues[$v])) continue; // cellule fusionnée : une seule lecture
                    $vues[$v] = true;
                    if (str_contains($v, ':') || str_contains($v, '→') || preg_match('/\bdu\s+\d/i', $v)) $this->metaTexte($v, $meta);
                    if (!self::estLibelleMeta($v)) continue;
                    // Valeur : case suivante de la ligne, sinon même colonne à la ligne suivante
                    $val = null;
                    foreach ($cases as $j => $w) if ($j > $i && $w !== $v) { $val = self::estLibelleMeta($w) ? null : $w; break; }
                    if ($val === null) { $w = trim((string) ($suivante[$i] ?? '')); if ($w !== '' && !self::estLibelleMeta($w)) $val = $w; }
                    if ($val !== null) $this->metaLibelle(self::norm(rtrim($v, ' :')), $val, $meta);
                }
            }
            if ($meta['objectif'] && $meta['debut'] && $meta['fin']) break;
        }
        if ($meta['debut'] && $meta['fin'] && $meta['fin']->lt($meta['debut'])) $meta['fin'] = null;
        return $meta;
    }

    /** Première date de début et dernière date de fin des tâches d'un onglet de planning. */
    private function periodeDesTaches(string $onglet): ?array
    {
        $lignes = $this->lecteur->lignesFeuille($onglet);
        [$entete, $cols] = $this->entete($lignes, ['tache' => ['tache'], 'debut' => ['debut'], 'fin' => ['date de fin', 'fin', 'echeance']], 'tache');
        if ($entete === null || !isset($cols['debut'])) return null;
        $debuts = []; $fins = [];
        foreach ($lignes as $num => $c) {
            if ($num <= $entete) continue;
            if ($d = self::date($c[$cols['debut']] ?? null)) $debuts[] = $d;
            if (isset($cols['fin']) && ($f = self::date($c[$cols['fin']] ?? null))) $fins[] = $f;
        }
        if (!$debuts) return null;
        $debut = min($debuts);
        $fin = $fins ? max($fins) : max($debuts);
        return [$debut->copy(), max($fin, $debut)->copy()];
    }

    /** Case-libellé d'une fiche de sprint (objectif, dates, durée, points, statut). */
    private static function estLibelleMeta(string $v): bool
    {
        $n = self::norm(rtrim(trim($v), ' :'));
        return mb_strlen($n) <= 45 && !preg_match('/\d{1,2}\/\d{1,2}\/\d{4}/', $n)
            && (bool) preg_match('/^(objectifs? du sprint|objectif|sprint goal|dates? et duree|dates?|periode( du sprint)?|date de debut|debut|date de fin|fin|duree|story points?( engages)?|points|statut( du sprint)?|realisation)$/', $n);
    }

    /** Libellé et valeur lus dans des cases distinctes. */
    private function metaLibelle(string $lib, string $val, array &$meta): void
    {
        if (str_starts_with($lib, 'objectif') || $lib === 'sprint goal') {
            $meta['objectif'] ??= trim($val, " \t«»\"“”") ?: null;
        } elseif (in_array($lib, ['date de debut', 'debut'], true)) {
            $meta['debut'] ??= self::date($val);
        } elseif (in_array($lib, ['date de fin', 'fin'], true)) {
            $meta['fin'] ??= self::date($val);
        } elseif (in_array($lib, ['dates et duree', 'dates', 'date', 'periode', 'periode du sprint'], true)) {
            $this->metaTexte('Du ' . $val, $meta);
        }
    }

    /** Valeurs écrites dans une même case : « Objectif : … », « Date de début : … Date de fin : … », « (… → …) », « Du … au … ». */
    private function metaTexte(string $v, array &$meta): void
    {
        $date = '(\d{1,2}\/\d{1,2}\/\d{4})';
        if (!$meta['objectif'] && preg_match('/^\s*(objectifs?|sprint goal)\b[^:]{0,60}:\s*(.+)$/isu', $v, $m)) {
            $o = trim($m[2], " \t«»\"“”");
            if ($o !== '' && !self::estLibelleMeta($o)) $meta['objectif'] = $o;
        }
        if (preg_match("/d[ée]but\s*:?\s*{$date}/iu", $v, $m)) $meta['debut'] ??= self::date($m[1]);
        if (preg_match("/\bfin\s*:?\s*{$date}/iu", $v, $m)) $meta['fin'] ??= self::date($m[1]);
        if (preg_match("/{$date}\s*(?:→|->|–|—|-|\bau\b)\s*{$date}/iu", $v, $m)) {
            $meta['debut'] ??= self::date($m[1]);
            $meta['fin'] ??= self::date($m[2]);
        }
        // Dates en toutes lettres : « Ven. 20 nov. → Ven. 27 nov. 2026 », « du 3 au 14 novembre 2026 »
        if ((!$meta['debut'] || !$meta['fin']) && ($d = self::periodeEnLettres($v))) {
            $meta['debut'] ??= $d[0];
            $meta['fin'] ??= $d[1];
        }
    }

    /** Période écrite en français (mois en toutes lettres ou abrégés) ; l'année manquante est reprise de l'autre date. */
    private static function periodeEnLettres(string $v): ?array
    {
        $mois = ['janv' => 1, 'janvier' => 1, 'fevr' => 2, 'fev' => 2, 'fevrier' => 2, 'mars' => 3, 'avr' => 4, 'avril' => 4, 'mai' => 5, 'juin' => 6,
            'juil' => 7, 'juillet' => 7, 'aout' => 8, 'sept' => 9, 'sep' => 9, 'septembre' => 9, 'oct' => 10, 'octobre' => 10, 'nov' => 11, 'novembre' => 11, 'dec' => 12, 'decembre' => 12];
        $n = self::norm($v);
        if (!preg_match_all('/\b(\d{1,2})(?:er)?\s+(' . implode('|', array_keys($mois)) . ')\.?(?:\s+(\d{4}))?/', $n, $mm, PREG_SET_ORDER) || count($mm) < 2) {
            // « du 3 au 14 novembre 2026 » : le mois et l'année ne figurent qu'une fois
            if (preg_match('/\b(\d{1,2})(?:er)?\s+au\s+(\d{1,2})\s+(' . implode('|', array_keys($mois)) . ')\.?\s+(\d{4})/', $n, $m)) {
                return [Carbon::create((int) $m[4], $mois[$m[3]], (int) $m[1])->startOfDay(), Carbon::create((int) $m[4], $mois[$m[3]], (int) $m[2])->startOfDay()];
            }
            return null;
        }
        [$a, $b] = [$mm[0], $mm[1]];
        $anneeB = (int) ($b[3] ?? 0) ?: (int) ($a[3] ?? 0);
        $anneeA = (int) ($a[3] ?? 0) ?: $anneeB;
        if (!$anneeA || !$anneeB) return null;
        $debut = Carbon::create($anneeA, $mois[$a[2]], (int) $a[1])->startOfDay();
        $fin = Carbon::create($anneeB, $mois[$b[2]], (int) $b[1])->startOfDay();
        if ($fin->lt($debut) && empty($a[3])) $debut->subYear(); // « 28 déc. → 9 janv. 2027 »
        return [$debut, $fin];
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
            'num' => ['n', 'no', 'num', 'numero'], 'tache' => ['tache'], 'story' => ['user story', 'story'], 'assigne' => ['attribution', 'assigne', 'responsable'],
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
        $ignorees = 0;
        // Équipe du sprint (collaborateurs nommés dans le planning) : une tâche « Toute l'équipe » est confiée à chacun
        $equipe = collect($lignes)->filter(fn($c, $num) => $num > $entete)->map(fn($c) => $this->utilisateur($c[$cols['assigne'] ?? -1] ?? null, false))->filter()->unique('id')->values();

        foreach ($lignes as $num => $c) {
            if ($num <= $entete) continue;
            $get = fn($k) => isset($cols[$k]) ? trim((string) ($c[$cols[$k]] ?? '')) : '';
            if (self::fusionnee($c)) { $storyGroupe = $this->story(self::valeurs($c)[0]); continue; }
            $intitule = $get('tache');
            // Ligne de user story (archive de l'application) : « US 1.1 » dans la colonne N°, ses tâches suivent (« 1.1.1 »…)
            if (preg_match('/^US[\s\-_]*[A-Z]?\d/iu', $get('num'))) {
                $storyGroupe = $this->story($get('num'), $get('story') ?: $intitule);
                continue;
            }
            if ($intitule === '' || preg_match('/^(total|l[ée]gende|statuts?\s*:)/iu', $intitule)) continue;
            // Ligne de regroupement non fusionnée (processus, story sans tâche) : ni attribution, ni date, ni statut
            if ($get('assigne') === '' && $get('debut') === '' && $get('statut') === '') {
                $storyGroupe = $this->story($get('story')) ?? $this->story($intitule) ?? $this->storyParIntitule($get('story')) ?? $storyGroupe;
                continue;
            }
            // Une tâche planifiée a toujours une attribution ; sans elle, c'est un total, une observation ou une note de recette
            if ($get('assigne') === '' || self::pasUneTache($intitule)) { $ignorees++; continue; }

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
                    // Déjà rattachée : l'application fait foi (un ré-import ne déplace pas une tâche d'une story à l'autre)
                    if ($us && !$t->user_story_id) { $t->update(['user_story_id' => $us->id]); $this->rapport['taches_maj']++; }
                    continue;
                }
                // Même code, même collaborateur, intitulé reformulé : la tâche est mise à jour (son statut ne change pas)
                if (($c = $code($intitule)) && ($lot = $parCode->get($c . '|' . $userId)) && $lot->isNotEmpty()) {
                    $t = $lot->shift();
                    $t->update(array_filter(['intitule' => $intitule, 'user_story_id' => $t->user_story_id ? null : $us?->id], fn($v) => $v !== null));
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
        if ($ignorees) {
            $this->rapport['lignes_ignorees'] += $ignorees;
            $this->erreur("{$onglet} : {$ignorees} ligne(s) sans attribution ignorée(s) (totaux, observations, notes).");
        }
    }

    private function storyParIntitule(string $texte): ?UserStory
    {
        return UserStory::retrouverPourImport(collect($this->stories), null, $texte, fn($n) => self::cle((string) $n));
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
                if (str_starts_with($lib, 'scribe')) $lib = 'scribe';
                if (!in_array($lib, ['objet', 'facilitateur', 'scribe', 'gardien du temps', 'observateur', 'date', 'heure debut', 'heure fin', 'horaire'], true)) continue;
                $suivante = $c[$i + 1] ?? null;
                if ($suivante !== null && !str_ends_with(trim((string) $suivante), ':')) $meta[$lib] = trim((string) $suivante);
            }
        }
        if (!empty($meta['horaire']) && preg_match('/(\d{1,2}\s*[h:]\s*\d{2})\s*(?:→|->|-|–|à)\s*(\d{1,2}\s*[h:]\s*\d{2})/u', $meta['horaire'], $hm)) {
            $meta['heure debut'] ??= $hm[1];
            $meta['heure fin'] ??= $hm[2];
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
        $parIntitule = [];
        foreach ($sprint->taches()->get() as $t) {
            if (preg_match('/^\s*(T-\d+)/i', $t->intitule, $m)) $parCode[strtoupper($m[1])] = $t->id;
            $parIntitule[self::norm($t->intitule)][] = $t->id;
        }
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
                if (!empty($parIntitule[self::norm($intitule)])) {
                    $id = array_shift($parIntitule[self::norm($intitule)]);
                    if ($get('obs') !== '') $observations[(string) $id] = self::riche($get('obs'));
                    continue;
                }
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
            // Déjà membre (actif ou retiré) : seule l'entrée dans le projet peut remonter ; un retrait n'est pas annulé par l'import
            if ($c->exists) {
                if ($c->date_ajout && Carbon::parse($c->date_ajout)->gt(Carbon::parse($depuis))) $c->update(['date_ajout' => $depuis]);
                continue;
            }
            // Collaborateur parti de l'entreprise : membre de l'historique du projet, retiré à sa date de départ
            $u = User::find($userId);
            $parti = $u && !$u->actif;
            $c->fill(['role_projet' => 'developpeur', 'actif' => !$parti, 'date_ajout' => $depuis,
                'date_retrait' => $parti ? ($u->date_depart ?? today()) : null])->save();
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
        $score = 0; $candidats = [];
        foreach ($this->index as $i) {
            if (array_diff($i['nom'], $mots)) continue;
            $sc = count(array_intersect($i['tous'], $mots));
            if ($sc > $score) { $score = $sc; $candidats = [$i['user']]; }
            elseif ($sc === $score && $sc > 0) $candidats[] = $i['user'];
        }
        // Homonymes : le compte actif l'emporte sur celui d'un collaborateur parti ; sinon, ambigu
        if (count($candidats) > 1) $candidats = array_values(array_filter($candidats, fn($u) => $u->actif));
        if (count($candidats) !== 1) { $this->nomsInconnus[trim((string) $nom)] = true; return null; }
        return $candidats[0];
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

    /** Intitulé qui ne peut pas être une tâche : nombre seul, ponctuation, libellé de tableau (total, observations…). */
    public static function pasUneTache(string $intitule): bool
    {
        $n = self::norm($intitule);
        return $n === '' || (bool) preg_match('/^[\d\s.,%\/?!:;\-–—]+$/u', $n)
            || (bool) preg_match('/^(total|sous[- ]?total|observations?|remarques?|commentaires?|legende|statuts?|nb|nombre)\b\s*:?\s*$/u', $n);
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
