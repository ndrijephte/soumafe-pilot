<?php
namespace App\Services;

use App\Models\{Sprint, Tache, UserStory};
use Illuminate\Support\Facades\DB;

/**
 * Story points répartis sur les tâches : la somme des points des tâches d'une user story, dans un sprint, est égale aux
 * points de la story. Un point par tâche d'abord (si la story en compte assez), le reste au prorata de la durée des tâches
 * (jours ouvrés entre début et échéance), au plus fort reste pour des points entiers. Les tâches reportées n'en reçoivent pas ; les tâches hors user
 * story et celles d'une story non estimée gardent leurs propres points.
 *
 * La story fait foi : dans un sprint clôturé, ce sont ses points figés à la clôture (instantané) qui sont répartis.
 */
final class RepartitionPoints
{
    /** Garde-fou contre les appels en cascade (événements des tâches pendant une répartition). */
    private static bool $enCours = false;

    /**
     * Répartit les points d'une story (ou de toutes les stories du sprint) sur ses tâches du sprint.
     * @return array<int, int> id de tâche => nouveaux points (seulement les tâches modifiées)
     */
    public static function sprint(Sprint $sprint, ?int $storyId = null, bool $enregistrer = true): array
    {
        if (self::$enCours) return [];
        self::$enCours = true;
        try {
            $stories = $sprint->userStories()->get()->keyBy('id');
            $taches = $sprint->taches()->whereNotNull('user_story_id')->where('statut', '!=', 'reporte')
                ->when($storyId, fn($q) => $q->where('user_story_id', $storyId))->orderBy('id')->get()->groupBy('user_story_id');
            $changes = [];
            foreach ($taches as $usId => $lot) {
                // Points de la story lus à travers le sprint (figés à la clôture le cas échéant). Une story hors du Sprint
                // Backlog (tâche rattachée pour la structure du planning) n'engage pas de points dans ce sprint : on n'y touche pas
                $us = $stories->get($usId);
                $pts = (int) ($us?->effort_estime ?? 0);
                if ($pts <= 0) continue;
                foreach (self::parts($lot, $pts) as $id => $p) {
                    if ((int) $lot->firstWhere('id', $id)->points !== $p || $lot->firstWhere('id', $id)->points === null) $changes[$id] = $p;
                }
            }
            if ($enregistrer && $changes) {
                // Écriture directe : les points ne changent ni le statut ni la story, pas d'événement à déclencher
                foreach ($changes as $id => $p) DB::table('taches')->where('id', $id)->update(['points' => $p]);
            }
            return $changes;
        } finally {
            self::$enCours = false;
        }
    }

    /** Toutes les sprints ouverts (ou en rectification) où la story est engagée. */
    public static function story(?int $storyId): void
    {
        if (!$storyId || self::$enCours) return;
        $sprints = Sprint::whereHas('userStories', fn($q) => $q->whereKey($storyId))
            ->orWhereHas('taches', fn($q) => $q->where('user_story_id', $storyId))->get();
        foreach ($sprints as $sp) if ($sp->modifiable()) self::sprint($sp, $storyId);
    }

    /**
     * Parts entières d'un total, au prorata de la durée des tâches (parts égales sans durée), au plus fort reste.
     * @return array<int, int> id => points
     */
    public static function parts($taches, int $total): array
    {
        $poids = $taches->mapWithKeys(fn(Tache $t) => [$t->id => max(1, $t->dureeJours())]);
        // Socle : un point par tâche quand la story en compte assez (une tâche réalisée n'est jamais « sans valeur »)
        $socle = $total >= $poids->count() ? 1 : 0;
        $aRepartir = $total - $socle * $poids->count();
        $somme = $poids->sum();
        $brut = $poids->map(fn($w) => $aRepartir * $w / $somme);
        $parts = $brut->map(fn($x) => $socle + (int) floor($x))->all();
        $reste = $total - array_sum($parts);
        // Le reste va aux plus fortes décimales (à égalité, la tâche la plus longue puis la première)
        $ordre = $brut->map(fn($x, $id) => [$x - floor($x), $poids[$id], -$id])->sort(fn($a, $b) => $b <=> $a)->keys();
        foreach ($ordre->take($reste) as $id) $parts[$id]++;
        return $parts;
    }
}
