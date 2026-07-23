<?php
namespace App\Services;

use App\Models\JourFerie;
use App\Models\Tache;
use Carbon\Carbon;

class ClotureService
{
    /**
     * Clôture une tâche : date de fin, qualification, effort volontaire,
     * et signale si une justification est requise (retard ou anticipation > seuil).
     */
    public function cloturer(Tache $tache, ?Carbon $dateFin = null): Tache
    {
        $fin = $dateFin ?? today();
        $tache->date_fin_reelle = $fin;
        $tache->statut = 'termine';
        $tache->effort_volontaire = $this->estHorsJourOuvre($fin);

        if ($tache->type === 'T3' || $tache->echeance === null) {
            $tache->qualification = 'non_planifiee';
            $tache->justification_requise = false;
        } else {
            $ecart = $this->ecartJoursOuvres($fin, $tache->echeance); // >0 = avance, <0 = retard
            $tolerance = config('soumafe.tolerance_a_temps_jours');
            $seuil = config('soumafe.seuil_anticipation_jours');

            if ($ecart < -$tolerance) {
                $tache->qualification = 'en_retard';
                $tache->justification_requise = true;
            } elseif ($ecart > $seuil) {
                $tache->qualification = 'anticipee';
                $tache->justification_requise = true; // commentaire court : calibrage des estimations, jamais un reproche
            } else {
                $tache->qualification = 'a_temps';
                $tache->justification_requise = false;
            }
        }
        $tache->save();

        return $tache;
    }

    /** Une tâche encore « en cours » à l'échéance dépassée doit être justifiée. */
    public function estEnRetardOuvert(Tache $tache): bool
    {
        return $tache->statut !== 'termine'
            && $tache->echeance !== null
            && $tache->echeance->isPast();
    }

    public function estHorsJourOuvre(Carbon $date): bool
    {
        return $date->isWeekend()
            || JourFerie::whereDate('date', $date)->exists();
    }

    /** Écart en jours ouvrés (fériés CI exclus) entre l'échéance et la fin réelle. */
    public function ecartJoursOuvres(Carbon $fin, Carbon $echeance): int
    {
        if ($fin->equalTo($echeance)) return 0;
        $feries = JourFerie::pluck('date')->map(fn ($d) => $d->toDateString())->all();
        [$debut, $borne, $signe] = $fin->lessThan($echeance) ? [$fin, $echeance, 1] : [$echeance, $fin, -1];
        $jours = 0;
        for ($d = $debut->copy()->addDay(); $d->lessThanOrEqualTo($borne); $d->addDay()) {
            if (!$d->isWeekend() && !in_array($d->toDateString(), $feries, true)) $jours++;
        }
        return $signe * $jours;
    }
}
