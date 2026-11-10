<?php
namespace App\Services;

use App\Models\{Sprint, Tache, User};
use Carbon\Carbon;

/**
 * Performance des collaborateurs sur une période choisie (par défaut, l'exercice en cours).
 * Date d'une tâche : sa fin réelle, à défaut son échéance, son début, sa création.
 * - Période égale à un exercice : chaque tâche appartient à un seul exercice ; une tâche de sprint compte dans
 *   celui du sprint (arbitré s'il est à cheval), comme au reporting, les autres selon leur date.
 * - Période libre : toutes les tâches comptent selon leur date, un sprint pouvant déborder de l'intervalle.
 * Les tâches reportées ne comptent pas.
 */
class PerformanceEquipe
{
    public const NB_EXERCICES = 12;

    /**
     * @param string|null $du Y-m-d (avec $au) ; sans dates, l'exercice en cours
     * @return array{cle: ?string, du: string, au: string, exercices: array, collaborateurs: \Illuminate\Support\Collection}
     */
    public static function calculer(?string $du = null, ?string $au = null): array
    {
        [$debut, $fin] = $du && $au
            ? [Carbon::parse($du)->startOfDay(), Carbon::parse($au)->endOfDay()]
            : ExerciceParc::courant();

        $cle = ExerciceParc::cle($debut);
        [$debutExercice, $finExercice] = ExerciceParc::bornes($cle);
        if (!$debut->isSameDay($debutExercice) || !$fin->isSameDay($finExercice)) $cle = null;

        $selonDate = fn($q) => $q->whereRaw('DATE(COALESCE(date_fin_reelle, echeance, date_debut, created_at)) BETWEEN ? AND ?', [$debut->toDateString(), $fin->toDateString()]);
        $requete = Tache::with('sprint')->where('statut', '!=', 'reporte');

        if ($cle) {
            $sprints = Sprint::whereDate('date_debut', '<=', $fin)->whereDate('date_fin', '>=', $debut)->get();
            $retenus = $sprints->filter(fn($s) => $s->periodeExercice()?->isSameDay($debut))->pluck('id');
            $requete->where(fn($q) => $q->whereIn('sprint_id', $retenus)->orWhere(fn($h) => $selonDate($h->whereNull('sprint_id'))));
        } else {
            $selonDate($requete);
        }
        $taches = $requete->get()->groupBy('user_id');

        $collaborateurs = User::where('actif', true)->whereNotIn('role', ['invite', 'direction', 'administrateur'])
            ->orderBy('name')->get()->map(function ($c) use ($taches) {
                $siennes = $taches->get($c->id, collect());
                $terminees = $siennes->where('statut', 'termine');
                $c->taches_total = $siennes->count();
                $c->taches_terminees = $terminees->count();
                $c->taches_en_cours = $siennes->where('statut', 'en_cours')->count();
                $c->taches_bloquees = $siennes->where('statut', 'bloque')->count();
                $c->taches_en_retard = $siennes->filter(fn($t) => in_array($t->statut, ['en_cours', 'a_faire'], true) && $t->estEnRetard())->count();
                $c->points_livres = (int) $terminees->whereNotNull('sprint_id')->sum('points');
                $c->taux = $c->taches_total > 0 ? (int) round($c->taches_terminees / $c->taches_total * 100) : null;
                return $c;
            })
            // Les collaborateurs sans tâche sur la période passent après les autres
            ->sortBy(fn($c) => [$c->taches_total > 0 ? 0 : 1, $c->name])->values();

        return [
            'cle' => $cle,
            'du' => $debut->toDateString(),
            'au' => $fin->toDateString(),
            'exercices' => ExerciceParc::liste(self::NB_EXERCICES),
            'collaborateurs' => $collaborateurs,
        ];
    }
}
