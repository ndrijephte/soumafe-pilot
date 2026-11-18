<?php
namespace App\Services;

use App\Models\{CompteRendu, Sprint, User};

/**
 * Déroulement d'un sprint : Sprint Backlog → Sprint Planning → Daily Scrum → clôture → séance de review (démonstration au client)
 * → rapport de Review Sprint (rédaction, validation par le pilote) → Review des développeurs (archive Excel) → Rétrospective.
 * Source unique des règles : les contrôleurs les appliquent, les écrans les affichent.
 */
class CeremoniesScrum
{
    /** Règles affichées dans « Règles du sprint ». */
    public const REGLES = [
        ['Product Backlog', 'En continu, tout au long du projet', 'Alimenté par le responsable du projet, le Product Owner ou un pilote : directement, par import ou depuis la Review', "Toutes les user stories du projet et leur niveau d'exécution."],
        ['Sprint Backlog', "Avant le démarrage ; ajustable pendant le sprint, figé à la clôture", "Les développeurs y puisent avant le démarrage ; le gestionnaire l'ajuste ensuite", "User stories prêtes (DoR = Oui) engagées pour le sprint."],
        ['Sprint Planning', "Avant le démarrage ; ajustable pendant le sprint, figé à la clôture", 'Gestionnaire du projet', "Tâches attribuées et datées, rattachées aux user stories. Au moins une tâche pour démarrer."],
        ['Daily Scrum', 'Chaque jour travaillé, tant que le sprint est en cours', 'Créé par un pilote ou automatiquement ; rédigé et publié par le scribe du jour', 'Rapport visible de tous une fois publié.'],
        ['Clôture', 'Fin du sprint ; un arrêt motivé reste possible à tout moment', 'Pilote ou gestionnaire du projet', "Exécution figée ; les user stories non terminées retournent au Product Backlog. Les rapports suivent."],
        ['Séance de review', 'Après la clôture', "L'équipe démontre au client les fonctionnalités du sprint ; le pilote prépare le rapport (date, lieu, rédacteur)", "Démonstration, appréciation du client par fonctionnalité, nouvelles demandes."],
        ['Rapport de review', 'Après la séance de review', "Rédigé par le pilote, seul rédacteur (il peut passer la main au responsable du projet) ; observations des tâches reprises des commentaires et blocages des développeurs ; validé par le pilote (directement s’il rédige, après transmission si le responsable rédige)", "Le rapport de Review Sprint : verdict du client par fonctionnalité, indicateurs, nouvelles user stories et décisions. À la validation, les user stories rejoignent le Product Backlog et les décisions deviennent des tâches suivies."],
        ['Review des développeurs', 'Après la validation du rapport de review', "Chaque développeur commente ses tâches et ses user stories ; le gestionnaire du projet complète la synthèse et valide", "Réalisation par tâche, observations, ajustements du Product Backlog (reprend les user stories du rapport). Consignée dans l'archive Excel."],
        ['Rétrospective', 'Après la validation de la Review des développeurs', "Rédigée par les développeurs de l'équipe ; validée par le gestionnaire", "Ce qui a marché ou non, causes, actions d'amélioration, reprises au sprint suivant. N'alimente pas le Product Backlog."],
    ];

    /** Atteinte de l'objectif du sprint, déclarée à la validation de la Review. */
    public const OBJECTIF = ['oui' => 'Atteint', 'partiel' => 'Partiellement atteint', 'non' => 'Non atteint'];

    private static function compteRendu(Sprint $sprint, string $type): ?CompteRendu
    {
        return CompteRendu::where('sprint_id', $sprint->id)->where('type', $type)->duModuleScrum()->first();
    }

    public static function reviewValidee(Sprint $sprint): bool
    {
        return (bool) self::compteRendu($sprint, 'review_sprint')?->estConfirme();
    }

    public static function retroValidee(Sprint $sprint): bool
    {
        return (bool) self::compteRendu($sprint, 'retrospective')?->estConfirme();
    }

    /** Rapport de Review Sprint (compte rendu « demo_sprint »), le plus récent. */
    public static function crDemo(Sprint $sprint): ?CompteRendu
    {
        return CompteRendu::where('sprint_id', $sprint->id)->where('type', 'demo_sprint')->latest('date_reunion')->latest('id')->first();
    }

    public static function demoConfirmee(Sprint $sprint): bool
    {
        return (bool) self::crDemo($sprint)?->estConfirme();
    }

    /** Chaque verrou renvoie le motif du refus, ou null si l'action est permise. */
    public static function verrouBacklog(Sprint $sprint): ?string
    {
        return $sprint->statut === 'termine' ? 'Sprint clôturé : le Sprint Backlog est figé.' : null;
    }

    /** Puiser dans le Product Backlog : le gestionnaire tant que le sprint n'est pas clos, l'équipe du projet avant son démarrage. */
    public static function peutPuiser(User $u, Sprint $sprint): bool
    {
        if ($sprint->statut === 'termine') return false;
        if ($u->peutGererProjet($sprint->projet_id) || $sprint->projet->peutAlimenterBacklog($u)) return true;
        return $sprint->statut === 'planifie' && !$u->aRole('direction') && !$u->aRole('invite') && $sprint->projet->estVisiblePour($u);
    }

    public static function verrouDaily(Sprint $sprint): ?string
    {
        return match ($sprint->statut) {
            'en_cours' => null,
            'planifie' => 'Le Daily Scrum commence au démarrage du sprint.',
            default => 'Sprint clôturé : plus de nouveau Daily Scrum.',
        };
    }

    /** La Review des développeurs suit le rapport de review : elle s'ouvre une fois le rapport validé. */
    public static function verrouReview(Sprint $sprint): ?string
    {
        if ($sprint->statut !== 'termine') return "La Review des développeurs s'ouvre après la clôture du sprint et la validation du rapport de review.";
        // Sprints antérieurs au circuit actuel : une Review déjà entamée reste accessible
        if (!self::demoConfirmee($sprint) && !self::compteRendu($sprint, 'review_sprint')) return "La Review des développeurs s'ouvre une fois le rapport de review validé.";
        return null;
    }

    /** La Rétrospective clôt le cycle : elle s'ouvre une fois la Review validée. */
    public static function verrouRetro(Sprint $sprint): ?string
    {
        if ($sprint->statut !== 'termine') return "La Rétrospective s'ouvre après la clôture du sprint et la validation de la Review.";
        return self::reviewValidee($sprint) ? null : "La Rétrospective s'ouvre une fois la Review validée.";
    }

    /**
     * Actions de la rétrospective du sprint précédent du projet, reprises dans ce sprint.
     * @return array{sprint_id: int, numero: int, valide: bool, lignes: array}|null
     */
    public static function actionsPrecedentes(Sprint $sprint): ?array
    {
        $precedent = Sprint::where('projet_id', $sprint->projet_id)->where('numero', '<', $sprint->numero)->orderByDesc('numero')->first();
        $cr = $precedent ? self::compteRendu($precedent, 'retrospective') : null;
        if (!$cr) return null;
        $lignes = collect($cr->getDonnees()['lignes'] ?? [])->filter(fn($l) => trim(TexteRiche::texte($l['actions'] ?? '')) !== '')->values()->all();
        return $lignes ? ['sprint_id' => $precedent->id, 'numero' => $precedent->numero, 'valide' => $cr->estConfirme(), 'lignes' => $lignes] : null;
    }

    /** @return string[] rapports d'un sprint clôturé qui restent à confirmer ou valider */
    public static function rapportsManquants(Sprint $sprint): array
    {
        if ($sprint->statut !== 'termine') return [];
        return array_values(array_filter([
            self::demoConfirmee($sprint) ? null : 'Rapport de review',
            self::reviewValidee($sprint) ? null : 'Review',
            self::retroValidee($sprint) ? null : 'Rétrospective',
        ]));
    }

    /** État de chaque onglet : ok (faite), brouillon (entamée), cours (à mener maintenant), '' (pas encore ouverte). */
    public static function etats(Sprint $sprint): array
    {
        $cr = CompteRendu::where('sprint_id', $sprint->id)->whereIn('type', ['review_sprint', 'retrospective'])->duModuleScrum()->pluck('statut', 'type');
        $rapport = function (string $type, ?string $verrou) use ($cr) {
            if (isset($cr[$type])) return $cr[$type] === 'confirme' ? ['ok', 'Validée'] : ($verrou ? ['', $verrou] : ['brouillon', 'Brouillon']);
            return $verrou ? ['', $verrou] : ['cours', 'À mener'];
        };
        $demo = self::crDemo($sprint);
        return [
            'backlog' => $sprint->userStories()->exists() ? ['ok', 'User stories engagées'] : ($sprint->statut === 'planifie' ? ['cours', 'À préparer'] : ['', 'Aucune user story']),
            'planning' => $sprint->taches()->exists() ? ['ok', 'Tâches planifiées'] : ($sprint->statut === 'planifie' ? ['cours', 'À préparer'] : ['', 'Aucune tâche']),
            'daily' => match ($sprint->statut) { 'en_cours' => ['cours', 'En cours'], 'planifie' => ['', self::verrouDaily($sprint)], default => ['ok', 'Terminé'] },
            'demo' => $demo ? ($demo->estConfirme() ? ['ok', 'Rapport validé'] : ($demo->estEnRevision() ? ['brouillon', 'Rapport transmis au pilote'] : ['brouillon', 'Rapport en rédaction']))
                : ($sprint->statut === 'termine' ? ['cours', 'À mener'] : ['', 'Le rapport de review suit la clôture du sprint.']),
            'review' => $rapport('review_sprint', self::verrouReview($sprint)),
            'retro' => $rapport('retrospective', self::verrouRetro($sprint)),
        ];
    }

    /** Prochaine étape du sprint : [texte, lien] ; lien null quand il n'y a plus rien à faire. */
    public static function prochaineEtape(Sprint $sprint): array
    {
        if ($sprint->statut === 'planifie') {
            return $sprint->taches()->exists()
                ? ['démarrer le sprint', route('sprints.show', $sprint)]
                : ['ajouter les tâches au Sprint Planning, puis démarrer le sprint', route('sprints.planning', $sprint)];
        }
        if ($sprint->statut === 'en_cours') {
            return $sprint->date_fin->gte(today())
                ? ['tenir les Daily Scrum jusqu\'au ' . $sprint->date_fin->format('d/m') . ', puis clôturer le sprint', route('scrum.dailies', $sprint)]
                : ['clôturer le sprint', route('sprints.show', $sprint)];
        }
        $demo = self::crDemo($sprint);
        if (!$demo) return ['tenir la séance de review avec le client, puis préparer le rapport de review', route('scrum.demo', $sprint)];
        if ($demo->estBrouillon()) return ['rédiger puis valider le rapport de review (le responsable du projet, s\'il a la main, le transmet au pilote)', route('scrum.demo', $sprint)];
        if ($demo->estEnRevision()) return ['valider le rapport de review transmis par le responsable du projet', route('scrum.demo', $sprint)];
        if (!self::reviewValidee($sprint)) return ['compléter et valider la Review des développeurs', route('scrum.review', $sprint)];
        if (!self::retroValidee($sprint)) return ['rédiger et valider la Rétrospective', route('scrum.retro', $sprint)];
        return [($sprint->arrete_le ? 'sprint arrêté le ' . $sprint->arrete_le->format('d/m/Y') : 'sprint clôturé') . ', rapports complets.', null];
    }
}
