<?php
namespace App\Services;

use App\Models\{CompteRendu, Sprint, User};

/**
 * Déroulement d'un sprint : Sprint Backlog → Sprint Planning → Daily Scrum → Review → Rétrospective → clôture → sprint suivant.
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
        ['Review', 'Dès le démarrage, tenue en fin de sprint', 'Rédigée par le scribe ou le gestionnaire ; validée par le gestionnaire', 'Réalisation par tâche et ajustements du Product Backlog. Le CR Review Sprint (DOCX) en est le document formel.'],
        ['Rétrospective', 'Dès le démarrage du sprint', "Rédigée par les développeurs de l'équipe ; validée par le gestionnaire", "Ce qui a marché ou non, causes, actions d'amélioration, reprises au sprint suivant. N'alimente pas le Product Backlog."],
        ['Clôture', 'Review et Rétrospective validées ; à défaut, sur décision motivée', 'Gestionnaire du projet ; sans ces validations, un pilote, motif obligatoire', "Sprint figé ; les user stories non terminées retournent au Product Backlog. Un arrêt motivé reste possible à tout moment."],
    ];

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

    public static function verrouReview(Sprint $sprint): ?string
    {
        return $sprint->statut === 'planifie' ? "La Review s'ouvre au démarrage du sprint." : null;
    }

    public static function verrouRetro(Sprint $sprint): ?string
    {
        // Elle qualifie la collaboration pendant l'exécution : ouverte dès le démarrage, sans attendre la Review
        return $sprint->statut === 'planifie' ? "La Rétrospective s'ouvre au démarrage du sprint." : null;
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

    /** @return string[] étapes qui manquent avant de pouvoir clôturer */
    public static function manquantsCloture(Sprint $sprint): array
    {
        return array_values(array_filter([
            self::reviewValidee($sprint) ? null : 'la Review',
            self::retroValidee($sprint) ? null : 'la Rétrospective',
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
        return [
            'backlog' => $sprint->userStories()->exists() ? ['ok', 'User stories engagées'] : ($sprint->statut === 'planifie' ? ['cours', 'À préparer'] : ['', 'Aucune user story']),
            'planning' => $sprint->taches()->exists() ? ['ok', 'Tâches planifiées'] : ($sprint->statut === 'planifie' ? ['cours', 'À préparer'] : ['', 'Aucune tâche']),
            'daily' => match ($sprint->statut) { 'en_cours' => ['cours', 'En cours'], 'planifie' => ['', self::verrouDaily($sprint)], default => ['ok', 'Terminé'] },
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
        if ($sprint->statut === 'termine') {
            return [$sprint->arrete_le ? 'sprint arrêté le ' . $sprint->arrete_le->format('d/m/Y') . '.' : 'sprint clôturé.', null];
        }
        if (!self::reviewValidee($sprint)) {
            return $sprint->date_fin->gte(today())
                ? ['tenir les Daily Scrum jusqu\'au ' . $sprint->date_fin->format('d/m') . ', puis valider la Review', route('scrum.dailies', $sprint)]
                : ['valider la Review', route('scrum.review', $sprint)];
        }
        if (!self::retroValidee($sprint)) return ['valider la Rétrospective', route('scrum.retro', $sprint)];
        return ['clôturer le sprint', route('sprints.show', $sprint)];
    }
}
