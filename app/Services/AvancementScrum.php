<?php
namespace App\Services;

use App\Models\{Tache, UserStory};
use Illuminate\Support\Collection;

/**
 * Hiérarchie de développement Module > Epic > User story > Tâche.
 * Le statut d'une user story découle de ses tâches (dans chaque sprint et dans le backlog) ;
 * l'avancement se consolide ensuite par epic et par module.
 */
final class AvancementScrum
{
    private const DEMARREES = ['en_cours', 'bloque', 'a_valider', 'termine'];

    /** « termine », « en_cours », « a_faire » ; null sans tâche (statut laissé à la main). */
    public static function statutDepuisTaches(Collection $taches): ?string
    {
        $t = $taches->where('statut', '!=', 'reporte');
        if ($t->isEmpty()) return null;
        if ($t->every(fn($x) => $x->statut === 'termine')) return 'termine';
        return $t->contains(fn($x) => in_array($x->statut, self::DEMARREES, true)) ? 'en_cours' : 'a_faire';
    }

    /**
     * Recalcule le statut d'une user story à partir de ses tâches.
     * $retrait : une tâche vient de la quitter ; sans tâche restante, la story revient « planifiée ».
     */
    public static function synchroniser(?int $userStoryId, bool $retrait = false): void
    {
        if (!$userStoryId || !($us = UserStory::with('sprints')->find($userStoryId))) return;
        $taches = Tache::where('user_story_id', $us->id)->get(['id', 'statut', 'sprint_id']);
        // Non achevée et sans sprint ouvert : elle revient au Product Backlog, prête pour un prochain sprint
        $ouvert = $us->sprints->contains(fn($s) => $s->statut !== 'termine');
        $actives = $taches->where('statut', '!=', 'reporte');
        if ($retrait && $actives->isEmpty()) {
            foreach ($us->sprints as $sprint) if ($sprint->pivot->statut_sprint !== 'a_faire') $us->sprints()->updateExistingPivot($sprint->id, ['statut_sprint' => 'a_faire']);
            if ($us->statut !== 'a_faire') $us->updateQuietly(['statut' => 'a_faire']);
            return;
        }

        foreach ($us->sprints as $sprint) {
            $s = self::statutDepuisTaches($taches->where('sprint_id', $sprint->id));
            if ($s && $sprint->pivot->statut_sprint !== $s) $us->sprints()->updateExistingPivot($sprint->id, ['statut_sprint' => $s]);
        }
        // Sans tâche, le statut suit le niveau d'exécution déclaré par les développeurs
        if ($actives->isEmpty() && $us->avancement !== null) {
            $statut = match (true) {
                $us->avancement >= 100 => $us->estFinie() ? $us->statut : 'terminee',
                !$ouvert => 'a_faire',
                in_array($us->statut, ['en_revue', 'bloquee'], true) => $us->statut,
                default => $us->avancement > 0 ? 'en_cours' : 'a_faire',
            };
            if ($us->statut !== $statut) $us->updateQuietly(['statut' => $statut]);
            foreach ($us->sprints->where('statut', '!=', 'termine') as $sprint) {
                $p = $us->avancement >= 100 ? 'termine' : ($us->avancement > 0 ? 'en_cours' : 'a_faire');
                if ($sprint->pivot->statut_sprint !== $p) $us->sprints()->updateExistingPivot($sprint->id, ['statut_sprint' => $p]);
            }
            return;
        }
        $calcule = self::statutStory($taches);
        $statut = match (true) {
            $calcule === 'terminee' => $us->statut === 'acceptee' ? 'acceptee' : 'terminee',
            // Mise en revue par le gestionnaire : elle tient jusqu'à la fin des tâches, sauf blocage
            $us->statut === 'en_revue' && $ouvert && $calcule !== 'bloquee' && $calcule !== null => 'en_revue',
            $calcule !== null => $ouvert ? $calcule : 'a_faire',
            // Ni tâche ni déclaration : le statut à la main tient tant qu'un sprint est ouvert
            default => $ouvert || $us->estFinie() ? $us->statut : 'a_faire',
        };
        if ($us->statut !== $statut) $us->updateQuietly(['statut' => $statut]);
    }

    /** Statut d'une story d'après ses tâches ; null sans tâche. « En revue » et « Acceptée » restent des décisions du gestionnaire. */
    public static function statutStory(Collection $taches): ?string
    {
        $t = $taches->where('statut', '!=', 'reporte');
        if ($t->isEmpty()) return null;
        if ($t->every(fn($x) => $x->statut === 'termine')) return 'terminee';
        if ($t->contains('statut', 'bloque')) return 'bloquee';
        return $t->contains(fn($x) => in_array($x->statut, self::DEMARREES, true)) ? 'en_cours' : 'a_faire';
    }

    /** La story a-t-elle des tâches dans ce sprint (son statut y est alors calculé) ? */
    public static function estCalcule(UserStory $us, int $sprintId): bool
    {
        return Tache::where('user_story_id', $us->id)->where('sprint_id', $sprintId)->where('statut', '!=', 'reporte')->exists();
    }

    /**
     * Bilan d'un ensemble de stories (chargées avec taches_count et taches_terminees_count).
     * Avancement : moyenne des niveaux d'exécution des stories, pondérée par leurs story points (1 si non estimée).
     */
    public static function bilan(Collection $stories): array
    {
        $nb = $stories->count();
        $faites = $stories->whereIn('statut', UserStory::FINIES)->count();
        $pts = (int) $stories->sum('effort_estime');
        $ptsFaits = (int) $stories->whereIn('statut', UserStory::FINIES)->sum('effort_estime');
        $poids = $stories->sum(fn($s) => $s->effort_estime ?: 1);
        $pct = $poids ? $stories->sum(fn($s) => ($s->effort_estime ?: 1) * self::pctStory($s)) / $poids : 0;
        return [
            'stories' => $nb, 'stories_terminees' => $faites,
            'points' => $pts, 'points_faits' => $ptsFaits,
            'taches' => (int) $stories->sum('taches_count'), 'taches_terminees' => (int) $stories->sum('taches_terminees_count'),
            'pct' => (int) round($pct),
            'base' => $pts ? 'points' : 'stories',
        ];
    }

    /**
     * Niveau d'exécution d'une story : valeur déclarée par les développeurs, sinon tâches terminées / tâches.
     * $taches : tâches déjà chargées (à défaut, compteurs taches_count / taches_terminees_count).
     */
    public static function pctStory(UserStory $us, ?Collection $taches = null): int
    {
        if ($us->estFinie()) return 100;
        if ($us->avancement !== null) return (int) $us->avancement;
        if ($taches) {
            $t = $taches->where('statut', '!=', 'reporte');
            return $t->count() ? (int) round($t->where('statut', 'termine')->count() / $t->count() * 100) : 0;
        }
        $n = (int) ($us->taches_count ?? 0);
        return $n ? (int) round(($us->taches_terminees_count ?? 0) / $n * 100) : 0;
    }

    /** Origine du niveau d'exécution affiché : « déclaré » ou « calculé ». */
    public static function origine(UserStory $us): string
    {
        return !$us->estFinie() && $us->avancement !== null ? 'declare' : 'calcule';
    }

    /** Peut déclarer le niveau d'exécution : membre de l'équipe du projet ou gestionnaire. */
    public static function peutDeclarer(\App\Models\User $u, UserStory $us): bool
    {
        if ($u->aRole('direction') || $u->aRole('invite')) return false;
        return $u->peutGererProjet($us->projet_id) || $us->projet?->estVisiblePour($u);
    }

    /**
     * User stories d'un sprint pour les comptes rendus, dans l'ordre Module > Epic > numéro,
     * avec leur niveau d'exécution (déclaré ou calculé) et leurs tâches du sprint.
     * @return array<int, array{id:int, module:string, epic:?string, numero:string, intitule:string, niveau:int, origine:string, points:?int, taches:int, taches_terminees:int, statut:string}>
     */
    public static function lignesSprint(\App\Models\Sprint $sprint): array
    {
        $sprint->loadMissing(['userStories', 'taches.userStory']);
        $stories = UserStory::trierParNumero($sprint->userStories->keyBy('id')->union($sprint->taches->pluck('userStory')->filter()->keyBy('id')))->values();
        $lignes = $stories->map(function ($us) use ($sprint) {
            $t = $sprint->taches->where('user_story_id', $us->id)->where('statut', '!=', 'reporte');
            return [
                'id' => $us->id, 'module' => trim((string) $us->module) ?: 'Sans module', 'epic' => trim((string) $us->epic) ?: null,
                'numero' => $us->numero, 'intitule' => $us->intitule,
                'niveau' => self::pctStory($us, $t), 'origine' => self::origine($us),
                'points' => $us->effort_estime, 'taches' => $t->count(), 'taches_terminees' => $t->where('statut', 'termine')->count(),
                'statut' => \App\Models\UserStory::STATUTS_SPRINT[$us->pivot?->statut_sprint ?? ''] ?? $us->statutLibelle(),
            ];
        });
        // Regroupement : modules dans l'ordre du backlog, stories sans epic puis par epic
        $ordreModule = $lignes->pluck('module')->unique()->values()->flip();
        return $lignes->sortBy([fn($a, $b) => $ordreModule[$a['module']] <=> $ordreModule[$b['module']], fn($a, $b) => ($a['epic'] ?? '') <=> ($b['epic'] ?? '')])->values()->all();
    }

    /**
     * Tableau de bord du Product Backlog (stories chargées avec leurs sprints et compteurs de tâches) :
     * répartition par statut et progression du produit, sprint après sprint (le plus récent d'abord).
     */
    public static function tableauBord(Collection $stories, Collection $sprints): array
    {
        $parStatut = $stories->countBy('statut');
        $total = $stories->count();
        $terminees = collect();
        $lignes = [];
        foreach ($sprints->sortBy('numero') as $sp) {
            $engagees = $stories->filter(fn($us) => $us->sprints->contains('id', $sp->id));
            if ($engagees->isEmpty()) continue;
            $faites = $engagees->filter(fn($us) => $us->sprints->firstWhere('id', $sp->id)->pivot->statut_sprint === 'termine');
            $terminees = $terminees->merge($faites->pluck('id'))->unique();
            $lignes[] = [
                'id' => $sp->id, 'numero' => $sp->numero, 'statut' => $sp->statut,
                'engagees' => $engagees->count(), 'terminees' => $faites->count(),
                'cumul' => $total ? (int) round($terminees->count() / $total * 100) : 0,
            ];
        }
        return [
            'repartition' => collect(array_keys(UserStory::STATUTS))->mapWithKeys(fn($s) => [$s => (int) ($parStatut[$s] ?? 0)])->all(),
            'a_reprendre' => $stories->filter(fn($us) => self::aReprendre($us))->count(),
            'issues_review' => $stories->where('origine', 'review')->count(),
            'sprints' => array_reverse($lignes),
        ];
    }

    /** Revenue au backlog alors qu'elle était entamée : son niveau d'exécution est conservé. */
    public static function aReprendre(UserStory $us): bool
    {
        return $us->statut === 'a_faire' && self::pctStory($us) > 0;
    }

    /**
     * Arborescence Module > Epic > stories, avec le bilan de chaque niveau.
     * @return Collection<int, array{module: string, bilan: array, epics: Collection}>
     */
    public static function arborescence(Collection $stories): Collection
    {
        return $stories->groupBy(fn($s) => trim((string) $s->module) ?: 'Sans module')
            ->map(fn($duModule, $module) => [
                'module' => $module,
                'bilan' => self::bilan($duModule),
                'epics' => $duModule->groupBy(fn($s) => trim((string) $s->epic))
                    ->map(fn($duEpic, $epic) => ['epic' => $epic ?: null, 'bilan' => self::bilan($duEpic), 'stories' => $duEpic->values()])
                    // Les stories sans epic d'abord, puis les epics par ordre alphabétique
                    ->sortBy(fn($e) => $e['epic'] === null ? '' : '1' . mb_strtolower($e['epic']))->values(),
            ])
            // Ordre du backlog conservé (ordre d'import), « Sans module » en dernier
            ->sortBy(fn($m) => $m['module'] === 'Sans module' ? 1 : 0)->values();
    }
}
