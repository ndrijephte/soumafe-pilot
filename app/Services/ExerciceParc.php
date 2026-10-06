<?php
namespace App\Services;

use App\Models\ParametreParc;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Exercice mensuel du parc : du jour J du mois au jour J-1 du mois suivant inclus (J = 5 par défaut).
 * Un exercice est identifié par le mois où il commence ('Y-m').
 *
 * J est paramétrable avec date d'effet (paramètre « exercice_regles » : « 2000-01:5;2027-01:1 »).
 * Chaque exercice garde la règle en vigueur à son mois de départ, les exercices passés ne bougent donc pas ;
 * l'exercice qui précède un changement s'arrête la veille du premier exercice de la nouvelle règle.
 */
final class ExerciceParc
{
    public const CLE_PARAMETRE = 'exercice_regles';
    public const JOUR_DEFAUT = 5;
    public const JOUR_MAX = 28;

    /** @var array<string, int>|null mois de départ => jour, trié */
    private static ?array $regles = null;

    /** @return array<string, int> */
    public static function regles(): array
    {
        if (self::$regles !== null) return self::$regles;

        $regles = [];
        foreach (explode(';', ParametreParc::val(self::CLE_PARAMETRE, '2000-01:' . self::JOUR_DEFAUT)) as $r) {
            if (preg_match('/^(\d{4}-\d{2}):(\d{1,2})$/', trim($r), $m) && $m[2] >= 1 && $m[2] <= self::JOUR_MAX) {
                $regles[$m[1]] = (int) $m[2];
            }
        }
        if (!$regles) $regles = ['2000-01' => self::JOUR_DEFAUT];
        ksort($regles);
        return self::$regles = $regles;
    }

    /** Enregistre une nouvelle règle ; les règles postérieures à sa date d'effet sont remplacées. */
    public static function definirRegle(string $aPartirDe, int $jour): void
    {
        $regles = array_filter(self::regles(), fn($k) => $k < $aPartirDe, ARRAY_FILTER_USE_KEY);
        $regles[$aPartirDe] = $jour;
        ksort($regles);
        // Une règle identique à la précédente n'apporte rien
        $propres = [];
        foreach ($regles as $mois => $j) {
            if (end($propres) !== $j) $propres[$mois] = $j;
        }
        if (!$propres) $propres = ['2000-01' => $jour];

        ParametreParc::updateOrCreate(['cle' => self::CLE_PARAMETRE], [
            'valeur' => collect($propres)->map(fn($j, $m) => "{$m}:{$j}")->join(';'),
            'libelle' => "Jour de début de l'exercice mensuel (historique des règles)",
            'groupe' => 'exercice',
        ]);
        self::$regles = null;
    }

    /** Jour de début applicable à l'exercice qui commence le mois $cle. */
    public static function jourDebut(?string $cle = null): int
    {
        $cle ??= self::cle();
        $jour = self::JOUR_DEFAUT;
        foreach (self::regles() as $depuis => $j) {
            if ($depuis > $cle) break;
            $jour = $j;
        }
        return $jour;
    }

    private static function debutDe(string $cle): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', "{$cle}-" . sprintf('%02d', self::jourDebut($cle)))->startOfDay();
    }

    /** Clé de l'exercice auquel appartient une date (avec J = 5, le 04/11 appartient à l'exercice d'octobre). */
    public static function cle(?CarbonInterface $date = null): string
    {
        $d = Carbon::parse($date ?? today());
        $cle = $d->format('Y-m');
        return $d->lt(self::debutDe($cle)) ? self::decaler($cle, -1) : $cle;
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public static function bornes(string $cle): array
    {
        $debut = self::debutDe($cle);
        $fin = self::debutDe(self::decaler($cle, 1))->subDay()->endOfDay();
        return [$debut, $fin];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public static function courant(): array
    {
        return self::bornes(self::cle());
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public static function precedent(?string $cle = null): array
    {
        return self::bornes(self::decaler($cle ?? self::cle(), -1));
    }

    public static function decaler(string $cle, int $nbExercices): string
    {
        return Carbon::createFromFormat('Y-m-d', "{$cle}-01")->addMonthsNoOverflow($nbExercices)->format('Y-m');
    }

    /** « Octobre 2026 » */
    public static function nom(string $cle): string
    {
        return ucfirst(Carbon::createFromFormat('Y-m-d', "{$cle}-01")->translatedFormat('F Y'));
    }

    /** « Octobre 2026 (05/10 au 04/11/2026) » */
    public static function libelle(string $cle): string
    {
        [$d, $f] = self::bornes($cle);
        return self::nom($cle) . ' (' . $d->format('d/m') . ' au ' . $f->format('d/m/Y') . ')';
    }

    /**
     * Exercices du plus récent au plus ancien.
     * @return array<string, array{libelle: string, debut: string, fin: string}>
     */
    public static function liste(int $nb = 12): array
    {
        $liste = [];
        $cle = self::cle();
        for ($i = 0; $i < $nb; $i++) {
            [$d, $f] = self::bornes($cle);
            $liste[$cle] = ['libelle' => self::libelle($cle), 'debut' => $d->toDateString(), 'fin' => $f->toDateString()];
            $cle = self::decaler($cle, -1);
        }
        return $liste;
    }
}
