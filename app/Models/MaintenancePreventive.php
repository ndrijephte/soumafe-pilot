<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenancePreventive extends Model
{
    protected $table = 'maintenances_preventives';

    protected $fillable = [
        'vehicule_id', 'type_echeance', 'derniere_realisation',
        'prochaine_echeance', 'observations',
    ];

    protected $casts = [
        'derniere_realisation' => 'date',
        'prochaine_echeance' => 'date',
    ];

    public function vehicule(): BelongsTo { return $this->belongsTo(Vehicule::class); }

    public function joursRestants(): ?int
    {
        if (!$this->prochaine_echeance) return null;
        return (int) today()->diffInDays($this->prochaine_echeance, false);
    }

    public function alerte(): string
    {
        $j = $this->joursRestants();
        if ($j === null) return 'inconnu';
        if ($j < 0) return 'en_retard';
        if ($j <= 15) return 'proche';
        return 'ok';
    }

    public function typeLabel(): string
    {
        return match ($this->type_echeance) {
            'vidange'              => 'Vidange',
            'assurance'            => 'Assurance',
            'visite_technique'     => 'Visite technique',
            'patente'              => 'Patente',
            'carte_stationnement'  => 'Carte de stationnement',
            default                => ucfirst(str_replace('_', ' ', $this->type_echeance)),
        };
    }
}
