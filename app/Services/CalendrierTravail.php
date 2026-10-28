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

    /** Heure d'arrivée attendue (« 08:00 »). */
    public static function heureArrivee(CarbonInterface|string|null $date = null): string
    {
        return self::regle($date)->arrivee();
    }

    /** Heure de départ attendue (« 18:00 »). */
    public static function heureDepart(CarbonInterface|string|null $date = null): string
    {
        return self::regle($date)->depart();
    }

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
