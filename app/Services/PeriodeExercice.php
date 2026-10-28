<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Période d'exercice mensuelle des tâches et des sprints : du 05 du mois au 04 du mois suivant.
 * Même règle que l'exercice du parc (ExerciceParc : jour paramétrable, historique conservé),
 * pour qu'un seul exercice s'applique dans tout le système.
 * La qualité des tâches est appréciée par sprint ou par période d'exercice ; un sprint à cheval est arbitré.
 */
class PeriodeExercice
{
    /** Début de la période contenant la date. */
    public static function debut(Carbon|string $date): Carbon
    {
        return ExerciceParc::bornes(ExerciceParc::cle(Carbon::parse($date)))[0]->copy()->startOfDay();
    }

    /** [début, fin] de la période contenant la date. */
    public static function bornes(Carbon|string $date): array
    {
        return ExerciceParc::bornes(ExerciceParc::cle(Carbon::parse($date)));
    }

    /** « 05/10/2026 → 04/11/2026 ». */
    public static function libelle(Carbon|string $date): string
    {
        [$d, $f] = self::bornes($date);
        return $d->format('d/m/Y') . ' → ' . $f->format('d/m/Y');
    }

    /** « Exercice octobre 2026 » (nommé d'après le mois où il commence). */
    public static function nom(Carbon|string $date): string
    {
        return 'Exercice ' . mb_strtolower(ExerciceParc::nom(ExerciceParc::cle(Carbon::parse($date))));
    }

    /** Débuts des périodes couvertes par un intervalle de dates. */
    public static function couvertes(Carbon $du, Carbon $au): Collection
    {
        $res = collect();
        for ($p = self::debut($du); $p->lte($au); $p = self::bornes($p)[1]->copy()->addDay()->startOfDay()) $res->push($p->copy());
        return $res;
    }
}
