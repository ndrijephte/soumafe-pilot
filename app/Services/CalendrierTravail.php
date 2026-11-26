<?php
namespace App\Services;

use App\Models\{HoraireTravail, JourFerie, JourOuvrableSpecial};
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Calendrier de travail : horaires et jours travaillés paramétrables avec date d'effet (table horaires_travail).
 * Une date est jugée avec la règle en vigueur ce jour-là : les statistiques passées ne bougent pas
 * quand la règle change.
 */
final class CalendrierTravail
{
    /** @var Collection<int, HoraireTravail>|null règles triées par date d'effet croissante */
    private static ?Collection $regles = null;
    private static ?array $feries = null;
    private static ?array $speciaux = null;

    /** @return Collection<int, HoraireTravail> */
    public static function regles(): Collection
    {
        if (self::$regles !== null) return self::$regles;
        $regles = Schema::hasTable('horaires_travail') ? HoraireTravail::orderBy('date_effet')->get() : collect();
        if ($regles->isEmpty()) {
            $regles = collect([new HoraireTravail(['date_effet' => '2000-01-01', 'heure_arrivee' => '08:00:00', 'heure_depart' => '18:00:00', 'jours' => '1,2,3,4,5'])]);
        }
        return self::$regles = $regles;
    }

    /** Vide les caches (après une modification des règles, des fériés ou des jours spéciaux). */
    public static function vider(): void
    {
        self::$regles = self::$feries = self::$speciaux = null;
    }

    /** Règle en vigueur à une date (aujourd'hui par défaut). */
    public static function regle(CarbonInterface|string|null $date = null): HoraireTravail
    {
        $jour = Carbon::parse($date ?? today())->toDateString();
        return self::regles()->last(fn($r) => $r->date_effet->toDateString() <= $jour) ?? self::regles()->first();
    }

    /** Jour de la semaine travaillé selon la règle du jour (fériés et jours spéciaux non pris en compte). */
    public static function estJourTravaille(CarbonInterface|string $date): bool
    {
        $d = Carbon::parse($date);
        return in_array($d->dayOfWeekIso, self::regle($d)->joursTravailles(), true);
    }

    public static function estFerie(CarbonInterface|string $date): bool
    {
        self::$feries ??= JourFerie::pluck('date')->map(fn($d) => Carbon::parse($d)->toDateString())->flip()->all();
        return isset(self::$feries[Carbon::parse($date)->toDateString()]);
    }

    public static function estJourSpecial(CarbonInterface|string $date): bool
    {
        self::$speciaux ??= Schema::hasTable('jours_ouvrables_speciaux')
            ? JourOuvrableSpecial::pluck('date')->map(fn($d) => Carbon::parse($d)->toDateString())->flip()->all() : [];
        return isset(self::$speciaux[Carbon::parse($date)->toDateString()]);
    }

    /** Jour ouvré : jour travaillé non férié, ou jour ouvrable spécial. */
    public static function estJourOuvre(CarbonInterface|string $date): bool
    {
        return self::estJourSpecial($date) || (self::estJourTravaille($date) && !self::estFerie($date));
    }

    /** Heure d'arrivée attendue ce jour-là (« 08:00 ») : horaire propre au jour de la semaine, sinon général. */
    public static function heureArrivee(CarbonInterface|string|null $date = null): string
    {
        $d = Carbon::parse($date ?? today());
        // Jour exceptionnel travaillé (férié, samedi ou dimanche déclarés ouvrables) : horaire des jours exceptionnels
        if (self::estJourExceptionnel($d)) return self::regle($d)->exceptionnel()[0];
        return self::regle($d)->arrivee($d->dayOfWeekIso);
    }

    /** Jour ouvrable spécial qui n'est pas un jour de travail ordinaire (férié, ou jour non travaillé de la semaine). */
    public static function estJourExceptionnel(CarbonInterface|string $date): bool
    {
        return self::estJourSpecial($date) && (self::estFerie($date) || !self::estJourTravaille($date));
    }

    /** Heure de départ attendue ce jour-là (« 18:00 »). */
    public static function heureDepart(CarbonInterface|string|null $date = null): string
    {
        $d = Carbon::parse($date ?? today());
        if (self::estJourExceptionnel($d)) return self::regle($d)->exceptionnel()[1];
        return self::regle($d)->depart($d->dayOfWeekIso);
    }

    /** Heure au-delà de laquelle une arrivée est un retard : heure de début + tolérance. */
    public static function limiteRetard(CarbonInterface|string|null $date = null): Carbon
    {
        $d = Carbon::parse($date ?? today());
        return Carbon::parse(self::heureArrivee($d))->addMinutes((int) self::regle($d)->tolerance_arrivee);
    }

    /** Heure avant laquelle un départ est anticipé : heure de fin - tolérance. */
    public static function limiteDepartAnticipe(CarbonInterface|string|null $date = null): Carbon
    {
        $d = Carbon::parse($date ?? today());
        return Carbon::parse(self::heureDepart($d))->subMinutes((int) self::regle($d)->tolerance_depart);
    }

    /** Heures des traitements automatiques en vigueur aujourd'hui (contrôle de saisie, Daily Scrum). */
    public static function heureControleSaisie(): string { return self::regle()->heureControleSaisie(); }
    public static function heureDailyAuto(): string { return self::regle()->heureDailyAuto(); }

    /** Nombre de jours ouvrés entre deux dates incluses. */
    public static function joursOuvres(CarbonInterface|string $debut, CarbonInterface|string $fin): int
    {
        $n = 0;
        for ($d = Carbon::parse($debut)->startOfDay(), $f = Carbon::parse($fin)->startOfDay(); $d->lte($f); $d->addDay()) {
            if (self::estJourOuvre($d)) $n++;
        }
        return $n;
    }
}
