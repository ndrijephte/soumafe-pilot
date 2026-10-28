<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Règle d'horaires et de jours de travail, en vigueur de sa date d'effet jusqu'à la règle suivante. */
class HoraireTravail extends Model
{
    protected $table = 'horaires_travail';
    protected $fillable = ['date_effet', 'heure_arrivee', 'heure_depart', 'jours', 'motif', 'cree_par'];
    protected $casts = ['date_effet' => 'date'];

    public const JOURS = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];

    public function createur() { return $this->belongsTo(User::class, 'cree_par'); }

    /** @return int[] jours ISO travaillés */
    public function joursTravailles(): array
    {
        return array_map('intval', array_filter(explode(',', (string) $this->jours)));
    }

    public function arrivee(): string { return substr($this->heure_arrivee, 0, 5); }
    public function depart(): string { return substr($this->heure_depart, 0, 5); }

    /** « Lundi - Vendredi » ou « Lundi, Mardi, Jeudi ». */
    public function joursLibelle(): string
    {
        $j = $this->joursTravailles();
        if (!$j) return 'Aucun';
        $suite = $j === range($j[0], end($j));
        return $suite && count($j) > 2 ? self::JOURS[$j[0]] . ' - ' . self::JOURS[end($j)] : collect($j)->map(fn($n) => self::JOURS[$n])->join(', ');
    }

    /** « Lundi - Vendredi, 08:00 → 18:00 » */
    public function libelle(): string
    {
        return $this->joursLibelle() . ', ' . $this->arrivee() . ' → ' . $this->depart();
    }

    /** Durée journalière en heures. */
    public function dureeJour(): float
    {
        [$ha, $ma] = explode(':', $this->heure_arrivee);
        [$hd, $md] = explode(':', $this->heure_depart);
        return round(($hd * 60 + $md - $ha * 60 - $ma) / 60, 2);
    }
}
