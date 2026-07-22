<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class Projet extends Model
{
    use SoftDeletes;
    protected $fillable = ['code', 'libelle', 'client', 'chef_projet_id', 'statut', 'date_debut', 'date_cible'];
    protected $casts = ['date_debut' => 'date', 'date_cible' => 'date'];

    public function chefProjet(): BelongsTo { return $this->belongsTo(User::class, 'chef_projet_id'); }
    public function equipe(): BelongsToMany { return $this->belongsToMany(User::class); }
    public function sprints(): HasMany { return $this->hasMany(Sprint::class); }
    public function taches(): HasMany { return $this->hasMany(Tache::class); }
}
