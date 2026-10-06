<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};

class Chauffeur extends Model
{
    protected $fillable = [
        'nom', 'prenom', 'date_naissance', 'adresse', 'numero_cni',
        'date_embauche', 'photo', 'personne_contact',
        'numero_permis', 'telephone', 'statut', 'observations',
    ];

    protected $casts = ['date_naissance' => 'date', 'date_embauche' => 'date'];

    public function vehicules(): BelongsToMany
    {
        return $this->belongsToMany(Vehicule::class, 'chauffeur_vehicule')
            ->withPivot('role', 'actif')
            ->withTimestamps();
    }

    public function planning(): HasMany
    {
        return $this->hasMany(PlanningJournalier::class);
    }

    public function pretsAccordes(): HasMany
    {
        return $this->hasMany(PretAccorde::class);
    }

    public function nomComplet(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    /** Seuls noms de chauffeur admis : ceux du formulaire Chauffeurs (prénom + nom). */
    public static function nomsValides(): array
    {
        return static::orderBy('nom')->get()->map->nomComplet()->all();
    }
}
