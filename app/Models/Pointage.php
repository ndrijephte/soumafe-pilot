<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Pointage extends Model
{
    protected $fillable = [
        'user_id', 'date', 'heure_arrivee', 'heure_depart',
        'statut', 'motif_retard', 'motif_depart', 'motif_absence',
        'corrige_par', 'motif_correction',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // --- Relations ---

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function correcteur()
    {
        return $this->belongsTo(User::class, 'corrige_par');
    }

    // --- Helpers ---

    /** Calcule le statut automatiquement selon les heures saisies */
    public function calculerStatut(): string
    {
        if (!$this->heure_arrivee && !$this->heure_depart) {
            return 'absent';
        }

        $arrivee = $this->heure_arrivee ? Carbon::parse($this->heure_arrivee) : null;
        $depart  = $this->heure_depart ? Carbon::parse($this->heure_depart) : null;

        $limiteArrivee = Carbon::parse('08:00');
        $limiteDepart  = Carbon::parse('18:00');

        $retard   = $arrivee && $arrivee->gt($limiteArrivee);
        $sortiTot = $depart && $depart->lt($limiteDepart);

        if ($retard && $sortiTot) return 'retard'; // priorise retard
        if ($retard) return 'retard';
        if ($sortiTot) return 'sorti_tot';

        return 'present';
    }

    /** Durée de présence en heures (décimal) */
    public function dureeHeures(): ?float
    {
        if (!$this->heure_arrivee || !$this->heure_depart) return null;
        $a = Carbon::parse($this->heure_arrivee);
        $d = Carbon::parse($this->heure_depart);
        return round($d->diffInMinutes($a) / 60, 2);
    }

    /** Durée formatée Xh Ymin */
    public function dureeFormatee(): string
    {
        $h = $this->dureeHeures();
        if ($h === null) return '—';
        $heures  = intval($h);
        $minutes = round(($h - $heures) * 60);
        return $heures . 'h' . ($minutes > 0 ? str_pad($minutes, 2, '0', STR_PAD_LEFT) : '00');
    }

    /** Label du statut */
    public function statutLibelle(): string
    {
        return match ($this->statut) {
            'present'    => 'Présent',
            'retard'     => 'Retard',
            'sorti_tot'  => 'Sorti tôt',
            'absent'     => 'Absent',
            'non_pointe' => 'Non pointé',
            default      => $this->statut,
        };
    }
}
