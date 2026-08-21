<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};

class Vehicule extends Model
{
    protected $fillable = [
        'immatriculation', 'categorie', 'marque', 'modele', 'date_mise_en_service',
        'chauffeur_affecte', 'kilometrage', 'statut', 'observations', 'objectif_recette',
    ];

    protected $casts = ['date_mise_en_service' => 'date'];

    public function pannes(): HasMany { return $this->hasMany(Panne::class); }
    public function recettes(): HasMany { return $this->hasMany(RecetteVehicule::class); }
    public function maintenances(): HasMany { return $this->hasMany(MaintenancePreventive::class); }
    public function penalites(): HasMany { return $this->hasMany(Penalite::class); }
    public function chauffeurs(): BelongsToMany
    {
        return $this->belongsToMany(Chauffeur::class, 'chauffeur_vehicule')
            ->withPivot('role', 'actif')
            ->withTimestamps();
    }

    public function categorieLabel(): string
    {
        return match ($this->categorie) {
            'vtc'  => 'VTC',
            'taxi' => 'Taxi',
            default => ucfirst($this->categorie),
        };
    }

    public function statutLabel(): string
    {
        return match ($this->statut) {
            'en_service'  => 'En service',
            'immobilise'  => 'Immobilisé',
            'reforme'     => 'Réformé',
            default       => ucfirst(str_replace('_', ' ', $this->statut)),
        };
    }
}
