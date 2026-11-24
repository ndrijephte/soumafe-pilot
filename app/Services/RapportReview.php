<?php
namespace App\Services;

use App\Models\{AuditLog, CompteRendu, NotificationApp, Sprint, Tache, User, UserStory};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Rapport de Review Sprint (compte rendu « demo_sprint ») : fond et circuit.
 *
 * Circuit : clôture du sprint → séance de review (démonstration au client) → rapport rédigé par le pilote
 * (seul rédacteur ; il peut passer la main au responsable du projet et la reprendre) → validation par le pilote.
 * Le pilote qui rédige valide lui-même, sans autre relecture ; le responsable qui rédige transmet au pilote, qui valide ou renvoie.
 * → Review des développeurs (archive Excel) → Rétrospective.
 *
 * Les développeurs n'écrivent pas dans le rapport : leurs commentaires et les blocages déclarés sur leurs tâches
 * pré-remplissent l'observation de chaque tâche. Le pilote garde la main sur toutes les rubriques.
 *
 * À la validation, le rapport alimente le suivi : nouvelles user stories au Product Backlog, décisions en tâches.
 */
class RapportReview
{
    /** Verdict du client sur une fonctionnalité démontrée : [libellé, couleur du texte, fond]. */
    public const VERDICTS = [
        'accepte'      => ['Acceptée', '15803D', 'DCFCE7'],
        'a_reprendre'  => ['À reprendre', 'B45309', 'FEF3C7'],
        'refuse'       => ['Refusée', 'B91C1C', 'FEE2E2'],
        'non_presente' => ['Non présentée', '64748B', 'F1F5F9'],
    ];

    /** Étapes du circuit, dans l'ordre : clé de statut => libellé. */
    public const ETAPES = ['brouillon' => 'Rédaction', 'en_revision' => 'Transmis au pilote', 'confirme' => 'Validé'];

    /* ───────────── Droits ───────────── */

    /** Valideurs : les pilotes. Un rapport transmis par le responsable du projet leur revient. */
    public static function relecteurs(CompteRendu $cr): Collection
    {
        return User::where('actif', true)->orderBy('name')->get()->filter(fn(User $u) => $u->estPilote())->values();
    }

    public static function estRelecteur(User $u, CompteRendu $cr): bool
    {
        return self::relecteurs($cr)->contains('id', $u->id);
    }

    /** Responsable du projet du sprint, à qui le pilote peut passer la main pour la rédaction (null s'il n'y en a pas). */
    public static function responsable(CompteRendu $cr): ?User
    {
        $u = $cr->sprint?->projet?->responsable_projet_id ? User::where('actif', true)->find($cr->sprint->projet->responsable_projet_id) : null;
        return $u && !$u->estPilote() ? $u : null;
    }

    /** La rédaction a-t-elle été confiée au responsable du projet ? */
    public static function mainAuResponsable(CompteRendu $cr): bool
    {
        $resp = self::responsable($cr);
        return $resp && (int) $cr->redacteur_id === (int) $resp->id;
    }

    /* ───────────── Observations des tâches ───────────── */

    /** Longueur maximale d'une observation de tâche dans le rapport. */
    public const OBSERVATION_MAX = 1000;

    /**
     * Observation pré-remplie de chaque tâche du sprint, à partir du journal de la tâche (Mes activités) :
     * qualification (à temps, en retard…), blocages déclarés (motif, levée), journal d'activité daté
     * (commentaires du développeur, de l'équipe ou du pilote, rejets, arbitrages ; les validations automatiques n'y figurent pas),
     * et l'observation de la Review des développeurs si elle existe déjà.
     * @return array<int, string> id de tâche => texte (OBSERVATION_MAX caractères au plus)
     */
    public static function observationsTaches(Sprint $sprint): array
    {
        $taches = Tache::where('sprint_id', $sprint->id)->with(['blocages', 'commentaires.user'])->get();
        $review = CompteRendu::where('sprint_id', $sprint->id)->where('type', 'review_sprint')->duModuleScrum()->first();
        $obsReview = $review ? ($review->getDonnees()['observations'] ?? []) : [];
        $texte = fn($v) => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</li>'], ' ', (string) $v)), ENT_QUOTES, 'UTF-8')));

        $obs = [];
        foreach ($taches as $t) {
            $parts = [];
            if ($t->qualification) $parts[] = Tache::libelleQualification($t->qualification);
            foreach ($t->blocages->sortBy('date_blocage') as $b) {
                $parts[] = 'Blocage du ' . $b->date_blocage?->format('d/m') . ' : ' . $texte($b->motif)
                    . ($b->date_levee ? ' (levé le ' . $b->date_levee->format('d/m') . ')' : ' (non levé)');
            }
            // Journal d'activité de la tâche, dans l'ordre : l'auteur est nommé quand ce n'est pas le développeur de la tâche
            foreach ($t->commentaires->where('contexte', '!==', 'validation')->sortBy('created_at') as $c) {
                $contenu = $texte($c->contenu);
                if ($contenu === '') continue;
                $auteur = (int) $c->user_id !== (int) $t->user_id && $c->user && !str_contains($contenu, $c->user->nomComplet()) ? $c->user->nomComplet() . ' : ' : '';
                $parts[] = $c->created_at->format('d/m') . ' ' . $auteur . $contenu;
            }
            if (!empty($obsReview[$t->id])) $parts[] = $texte($obsReview[$t->id]);
            $parts = array_values(array_unique(array_filter($parts, fn($p) => $p !== '')));
            $obs[$t->id] = mb_substr(implode(' — ', $parts), 0, self::OBSERVATION_MAX);
        }
        return $obs;
    }

    /* ───────────── Contenu ───────────── */

    /** Le bloc produit-il quelque chose à l'impression ? (mêmes règles que le PDF et le Word) */
    public static function blocVisible(array $b, array $d): bool
    {
        $vide = fn($html) => trim(html_entity_decode(strip_tags(str_replace('&nbsp;', ' ', (string) $html)), ENT_QUOTES, 'UTF-8')) === '';
        return match ($b['type'] ?? '') {
            'sous_titre' => trim($b['texte'] ?? '') !== '',
            'commentaire' => trim($b['contenu'] ?? '') !== '',
            'texte', 'reference' => !$vide($b['contenu'] ?? ''),
            'verdict' => !empty($b['verdict']) || !$vide($b['contenu'] ?? ''),
            'tableau_stories' => (bool) \App\Http\Controllers\CompteRenduController::lignesModule($d['stories'] ?? [], $b['module'] ?? null, false),
            'tableau_taches' => (bool) \App\Http\Controllers\CompteRenduController::lignesModule($d['taches'] ?? [], $b['module'] ?? null, true),
            'tableau_livraison' => (bool) self::lignesLivraison($d, $b['module'] ?? null),
            'actions', 'tableau_backlog', 'validation' => !empty($b['items']),
            'score', 'indicateurs' => true,
            default => false,
        };
    }

    /**
     * Un seul tableau par fonctionnalité : dans une même section, le tableau des user stories et celui des tâches de la
     * même fonctionnalité deviennent un tableau « Stories et tâches livrées » (les tâches sous leur story).
     * S'applique aussi aux rapports rédigés avant ce format.
     */
    public static function fusionnerTableaux(array $d): array
    {
        foreach ($d['modules'] ?? [] as $mi => $mod) {
            foreach ($mod['sections'] ?? [] as $si => $sec) {
                $blocs = $sec['blocs'] ?? [];
                foreach ($blocs as $i => $b) {
                    if (($b['type'] ?? '') !== 'tableau_stories') continue;
                    foreach ($blocs as $j => $t) {
                        if (($t['type'] ?? '') === 'tableau_taches' && ($t['module'] ?? null) === ($b['module'] ?? null)) {
                            $blocs[$i]['type'] = 'tableau_livraison';
                            unset($blocs[$j]);
                            break;
                        }
                    }
                }
                $d['modules'][$mi]['sections'][$si]['blocs'] = array_values($blocs);
            }
        }
        return $d;
    }

    /**
     * Lignes du tableau « Stories et tâches livrées » d'une fonctionnalité : chaque story suivie de ses tâches
     * (rattachées par le numéro de story), puis les tâches de la fonctionnalité sans story reconnue.
     * @return array<int, array{story: ?array, taches: array}>
     */
    public static function lignesLivraison(array $d, ?string $module): array
    {
        $C = \App\Http\Controllers\CompteRenduController::class;
        $stories = array_values($C::lignesModule($d['stories'] ?? [], $module, false));
        $taches = array_values($C::lignesModule($d['taches'] ?? [], $module, true));
        $parStory = [];
        $autres = [];
        $numeros = array_flip(array_filter(array_map(fn($s) => $s['numero'] ?? '', $stories)));
        foreach ($taches as $t) {
            $n = $t['us'] ?? '';
            if ($n !== '' && isset($numeros[$n])) $parStory[$n][] = $t; else $autres[] = $t;
        }
        $res = [];
        foreach ($stories as $st) $res[] = ['story' => $st, 'taches' => $parStory[$st['numero'] ?? ''] ?? []];
        if ($autres) $res[] = ['story' => null, 'taches' => $autres];
        return $res;
    }

    /** Modules et sections tels qu'imprimés : sans les sections vides ni les modules restés vides. */
    public static function modulesVisibles(array $d): array
    {
        $d = self::fusionnerTableaux($d);
        $modules = [];
        foreach ($d['modules'] ?? [] as $mod) {
            $sections = [];
            foreach ($mod['sections'] ?? [] as $sec) {
                $blocs = array_values(array_filter($sec['blocs'] ?? [], fn($b) => is_array($b) && self::blocVisible($b, $d)));
                if ($blocs) $sections[] = ['blocs' => $blocs] + $sec;
            }
            if ($sections || trim($mod['objectif'] ?? '') !== '') $modules[] = ['sections' => $sections] + $mod;
        }
        return $modules;
    }

    /** Verdicts posés dans le rapport, par fonctionnalité (titre de la section qui porte le verdict). */
    public static function verdicts(array $d): array
    {
        $v = [];
        foreach ($d['modules'] ?? [] as $mod) foreach ($mod['sections'] ?? [] as $sec) foreach ($sec['blocs'] ?? [] as $b) {
            if (($b['type'] ?? '') === 'verdict' && isset(self::VERDICTS[$b['verdict'] ?? ''])) $v[$sec['titre'] ?? ''] = $b['verdict'];
        }
        return $v;
    }

    /**
     * Indicateurs du sprint, calculés à partir du rapport (tâches, user stories, verdicts) et du sprint précédent.
     * Rien n'est figé : le bloc « Indicateurs » se recalcule à chaque affichage.
     */
    public static function indicateurs(array $d, ?Sprint $sprint): array
    {
        $taches = collect($d['taches'] ?? []);
        $stories = collect($d['stories'] ?? []);
        $verdicts = collect(self::verdicts($d));
        $faites = $taches->filter(fn($t) => !empty($t['termine']));
        $enRetard = $taches->filter(fn($t) => ($t['observation'] ?? '') === 'en_retard' || ($t['observation'] ?? '') === Tache::QUALIFICATIONS['en_retard'])->count();
        $finies = $stories->filter(fn($s) => (int) ($s['niveau'] ?? 0) >= 100 || in_array($s['statut'] ?? '', ['Terminée', 'Acceptée'], true));

        // Vélocité : points réalisés du sprint précédent du même projet
        $precedent = null;
        if ($sprint) {
            $sp = Sprint::where('projet_id', $sprint->projet_id)->where('numero', '<', $sprint->numero)->orderByDesc('numero')->first();
            if ($sp) $precedent = ['numero' => $sp->numero, 'points' => (int) Tache::where('sprint_id', $sp->id)->where('statut', 'termine')->get()->sum(fn($t) => $t->points ?? 1)];
        }

        $parDev = $taches->groupBy(fn($t) => $t['attribution'] ?: '—')->map(fn($g, $nom) => [
            'nom' => $nom, 'total' => $g->sum(fn($t) => (int) ($t['points'] ?? 1)),
            'realise' => $g->filter(fn($t) => !empty($t['termine']))->sum(fn($t) => (int) ($t['points'] ?? 1)),
        ])->map(fn($l) => $l + ['pourcent' => $l['total'] ? (int) round($l['realise'] / $l['total'] * 100) : 0])->sortBy('nom')->values()->all();

        $pts = (int) ($d['points_realises'] ?? 0);
        return [
            'fonctionnalites' => $verdicts->count(),
            'demontrees' => $verdicts->reject(fn($v) => $v === 'non_presente')->count(),
            'acceptees' => $verdicts->filter(fn($v) => $v === 'accepte')->count(),
            'a_reprendre' => $verdicts->filter(fn($v) => $v === 'a_reprendre')->count(),
            'refusees' => $verdicts->filter(fn($v) => $v === 'refuse')->count(),
            'stories' => $stories->count(), 'stories_finies' => $finies->count(),
            'taches' => $taches->count(), 'taches_faites' => $faites->count(), 'taches_en_retard' => $enRetard,
            'points' => (int) ($d['total_points'] ?? 0), 'points_realises' => $pts, 'score' => (int) ($d['score_pourcent'] ?? 0),
            'livraison' => $taches->count() ? (int) round($faites->count() / $taches->count() * 100) : 0,
            'precedent' => $precedent,
            'velocite' => $precedent ? $pts - $precedent['points'] : null,
            'par_dev' => $parDev,
        ];
    }

    /** Cartes d'indicateurs à afficher : [valeur, libellé, détail]. */
    public static function cartes(array $i): array
    {
        $c = [
            [$i['points_realises'] . ' / ' . $i['points'], 'Points réalisés', "Score {$i['score']} %"],
            [$i['taches_faites'] . ' / ' . $i['taches'], 'Tâches réalisées', "Livraison {$i['livraison']} %" . ($i['taches_en_retard'] ? " · {$i['taches_en_retard']} en retard" : '')],
        ];
        if ($i['stories']) $c[] = [$i['stories_finies'] . ' / ' . $i['stories'], 'User stories terminées', ''];
        if ($i['fonctionnalites']) {
            $c[] = [$i['acceptees'] . ' / ' . $i['demontrees'], 'Fonctionnalités acceptées',
                trim(($i['a_reprendre'] ? "{$i['a_reprendre']} à reprendre" : '') . ($i['refusees'] ? ($i['a_reprendre'] ? ' · ' : '') . "{$i['refusees']} refusée(s)" : '')) ?: 'sur les fonctionnalités démontrées'];
        }
        if ($i['precedent']) {
            $c[] = [($i['velocite'] > 0 ? '+' : '') . $i['velocite'] . ' pts', 'Vélocité', "vs sprint {$i['precedent']['numero']} ({$i['precedent']['points']} pts)"];
        }
        return $c;
    }

    /* ───────────── Suites de la validation ───────────── */

    /**
     * Nouvelles user stories du tableau « Backlog issu de la review » : créées une seule fois au Product Backlog
     * (origine Review, rattachées au rapport), leur numéro est reporté dans le tableau.
     */
    public static function versBacklog(CompteRendu $cr, array &$d): int
    {
        $projet = $cr->sprint?->projet;
        if (!$projet) return 0;
        $n = 0;
        foreach ($d['modules'] as &$mod) foreach ($mod['sections'] as &$sec) foreach ($sec['blocs'] as &$b) {
            if (($b['type'] ?? '') !== 'tableau_backlog') continue;
            foreach ($b['items'] as &$it) {
                if (!empty($it['user_story_id']) || trim($it['numero'] ?? '') !== '' || trim($it['intitule'] ?? '') === '') continue;
                $module = trim($it['module'] ?? '') ?: null;
                $us = $projet->userStories()->create([
                    'numero' => UserStory::prochainNumero($projet, $module), 'module' => $module, 'intitule' => $it['intitule'],
                    'exigences' => "<i>Issue de la review du sprint {$cr->sprint->numero}.</i>",
                    'statut' => 'a_faire', 'origine' => 'review', 'source_cr_id' => $cr->id,
                    'priorite_moscow' => $it['moscow'] ?: null, 'effort_estime' => UserStory::normaliserPoints($it['points'] ?? null),
                ]);
                $it['numero'] = $us->numero;
                $it['user_story_id'] = $us->id;
                $n++;
            }
            unset($it);
        }
        unset($mod, $sec, $b);
        if ($n) AuditLog::log('review_vers_backlog', 'projet', $projet->code, "{$n} user story(s) issue(s) du rapport de review du sprint {$cr->sprint->numero}");
        return $n;
    }

    /**
     * Décisions et actions : une tâche opérationnelle (T2) par responsable, dans « Mes tâches », avec l'échéance fixée.
     * Une action déjà transformée n'est pas recréée (empreinte conservée dans le rapport).
     */
    public static function versSuivi(CompteRendu $cr, array &$d): int
    {
        if (!$cr->sprint) return 0;
        $membres = ScrumService::membres($cr->sprint);
        $parNom = User::where('actif', true)->get()->keyBy(fn(User $u) => $u->nomComplet());
        $deja = $d['suivi'] ?? [];
        $n = 0;
        foreach ($d['modules'] ?? [] as $mod) foreach ($mod['sections'] ?? [] as $sec) foreach ($sec['blocs'] ?? [] as $b) {
            if (($b['type'] ?? '') !== 'actions') continue;
            foreach ($b['items'] ?? [] as $a) {
                $intitule = trim($a['action'] ?? '');
                if ($intitule === '') continue;
                $noms = $a['responsables'] ?? [];
                $users = in_array('Tous', $noms, true) ? $membres : collect($noms)->map(fn($nom) => $parNom[$nom] ?? null)->filter();
                foreach ($users as $u) {
                    $cle = md5(mb_strtolower($intitule) . '|' . $u->id);
                    if (isset($deja[$cle]) && Tache::withTrashed()->whereKey($deja[$cle])->exists()) continue;
                    $echeance = !empty($a['echeance']) && strtotime($a['echeance']) ? $a['echeance'] : null;
                    $t = Tache::create([
                        'user_id' => $u->id, 'projet_id' => $cr->sprint->projet_id, 'type' => 'T2',
                        'intitule' => mb_substr($intitule, 0, 255), 'activite' => "Décision de la review du sprint {$cr->sprint->numero}",
                        'priorite' => 'moyenne', 'statut' => 'a_faire', 'date_debut' => today()->toDateString(),
                        'echeance' => $echeance ?? today()->addDays(7)->toDateString(),
                    ]);
                    $deja[$cle] = $t->id;
                    self::notifier($u->id, "Décision de la review du sprint {$cr->sprint->numero} : « {$intitule} » vous est confiée.", route('taches.index'));
                    $n++;
                }
            }
        }
        $d['suivi'] = $deja;
        return $n;
    }

    public static function notifier(int $userId, string $message, string $lien): void
    {
        if (!Schema::hasTable('notifications_app')) return;
        NotificationApp::create(['user_id' => $userId, 'type' => 'designation_cr', 'lu' => false, 'canal' => 'in_app', 'contenu' => $message, 'lien' => $lien]);
    }
}
