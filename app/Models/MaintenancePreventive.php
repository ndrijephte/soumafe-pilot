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
        'statut', 'date_realisation', 'cout', 'prestataire', 'depense_id',
    ];

    protected $casts = [
        'derniere_realisation' => 'date',
        'prochaine_echeance' => 'date',
        'date_realisation' => 'date',
    ];

    public function vehicule(): BelongsTo { return $this->belongsTo(Vehicule::class); }
    public function depense(): BelongsTo { return $this->belongsTo(Depense::class); }

    public function estRealisee(): bool { return $this->statut === 'realisee'; }

    public function statutLabel(): string
    {
        return match($this->statut) {
            'realisee' => 'Réalisée',
            default => 'Planifiée',
        };
    }

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
        $tp = \App\Models\TypeParc::where('categorie', 'maintenance')->where('code', $this->type_echeance)->first();
        return $tp ? $tp->libelle : ucfirst(str_replace('_', ' ', $this->type_echeance));
    }
}
