<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Chauffeur extends Model
{
    protected $fillable = ['nom', 'prenom', 'numero_permis', 'telephone', 'statut', 'observations'];

    public function vehicules(): BelongsToMany
    {
        return $this->belongsToMany(Vehicule::class, 'chauffeur_vehicule')
            ->withPivot('role', 'actif')
            ->withTimestamps();
    }

    public function nomComplet(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }
}
