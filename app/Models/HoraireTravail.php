<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Règle d'horaires et de jours de travail, en vigueur de sa date d'effet jusqu'à la règle suivante.
 * Horaire général + horaires propres à certains jours, pause déjeuner, tolérances de pointage,
 * heures des traitements automatiques.
 */
class HoraireTravail extends Model
{
    protected $table = 'horaires_travail';
    protected $fillable = ['date_effet', 'heure_arrivee', 'heure_depart', 'jours', 'horaires_jours', 'pause_debut', 'pause_fin',
        'tolerance_arrivee', 'tolerance_depart', 'heure_controle_saisie', 'heure_daily_auto', 'motif', 'cree_par'];
    protected $casts = ['date_effet' => 'date', 'horaires_jours' => 'array', 'tolerance_arrivee' => 'integer', 'tolerance_depart' => 'integer'];

    public const JOURS = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];

    public function createur() { return $this->belongsTo(User::class, 'cree_par'); }

    /** @return int[] jours ISO travaillés */
    public function joursTravailles(): array
    {
        return array_map('intval', array_filter(explode(',', (string) $this->jours)));
    }

    /** Horaire propre à un jour ISO (null : horaire général). */
    public function horaireDuJour(?int $jourIso): ?array
    {
        $h = $jourIso ? ($this->horaires_jours[$jourIso] ?? $this->horaires_jours[(string) $jourIso] ?? null) : null;
        return !empty($h['arrivee']) && !empty($h['depart']) ? $h : null;
    }

    /** Heure de début (« 08:00 ») : du jour si elle lui est propre, sinon générale. */
    public function arrivee(?int $jourIso = null): string { return substr($this->horaireDuJour($jourIso)['arrivee'] ?? $this->heure_arrivee, 0, 5); }
    public function depart(?int $jourIso = null): string { return substr($this->horaireDuJour($jourIso)['depart'] ?? $this->heure_depart, 0, 5); }

    /** Pause déjeuner [« 12:30 », « 13:30 »] ou null. */
    public function pause(): ?array
    {
        return $this->pause_debut && $this->pause_fin ? [substr($this->pause_debut, 0, 5), substr($this->pause_fin, 0, 5)] : null;
    }

    public function heureControleSaisie(): string { return substr($this->heure_controle_saisie ?: '08:00', 0, 5); }
    public function heureDailyAuto(): string { return substr($this->heure_daily_auto ?: '07:00', 0, 5); }

    /** « Lundi - Vendredi » ou « Lundi, Mardi, Jeudi ». */
    public function joursLibelle(): string
    {
        $j = $this->joursTravailles();
        if (!$j) return 'Aucun';
        $suite = $j === range($j[0], end($j));
        return $suite && count($j) > 2 ? self::JOURS[$j[0]] . ' - ' . self::JOURS[end($j)] : collect($j)->map(fn($n) => self::JOURS[$n])->join(', ');
    }

    /** Jours ayant un horaire propre : « Vendredi 08:00 → 16:00 ». */
    public function exceptionsLibelle(): array
    {
        return collect($this->joursTravailles())->filter(fn($j) => $this->horaireDuJour($j))
            ->map(fn($j) => self::JOURS[$j] . ' ' . $this->arrivee($j) . ' → ' . $this->depart($j))->values()->all();
    }

    /** « Lundi - Vendredi, 08:00 → 18:00 (vendredi 08:00 → 16:00), pause 12:30 → 13:30 » */
    public function libelle(): string
    {
        $exc = $this->exceptionsLibelle();
        return $this->joursLibelle() . ', ' . $this->arrivee() . ' → ' . $this->depart()
            . ($exc ? ' (' . mb_strtolower(implode(', ', $exc)) . ')' : '')
            . (($p = $this->pause()) ? ", pause {$p[0]} → {$p[1]}" : '')
            . ($this->tolerance_arrivee || $this->tolerance_depart ? ", tolérance {$this->tolerance_arrivee}/{$this->tolerance_depart} min" : '');
    }

    /** Minutes entre deux heures « HH:MM ». */
    public static function minutes(string $de, string $a): int
    {
        [$h1, $m1] = array_map('intval', explode(':', $de));
        [$h2, $m2] = array_map('intval', explode(':', $a));
        return ($h2 * 60 + $m2) - ($h1 * 60 + $m1);
    }

    /** Minutes de pause comprises dans une plage horaire. */
    public function pauseDans(string $de, string $a): int
    {
        if (!($p = $this->pause())) return 0;
        $debut = max(self::minutes('00:00', $de), self::minutes('00:00', $p[0]));
        $fin = min(self::minutes('00:00', $a), self::minutes('00:00', $p[1]));
        return max(0, $fin - $debut);
    }

    /** Durée de travail d'un jour en heures, pause déduite (jour ISO : horaire propre éventuel). */
    public function dureeJour(?int $jourIso = null): float
    {
        $a = $this->arrivee($jourIso);
        $d = $this->depart($jourIso);
        return round((self::minutes($a, $d) - $this->pauseDans($a, $d)) / 60, 2);
    }

    /** Durée de travail hebdomadaire en heures. */
    public function dureeSemaine(): float
    {
        return round(collect($this->joursTravailles())->sum(fn($j) => $this->dureeJour($j)), 2);
    }

    /** Empreinte de la règle (hors date et motif) : sert à détecter un enregistrement sans changement. */
    public function empreinte(): string
    {
        return json_encode([$this->jours, $this->arrivee(), $this->depart(), collect($this->joursTravailles())->mapWithKeys(fn($j) => [$j => $this->horaireDuJour($j)])->filter()->all(),
            $this->pause(), (int) $this->tolerance_arrivee, (int) $this->tolerance_depart, $this->heureControleSaisie(), $this->heureDailyAuto()]);
    }
}
