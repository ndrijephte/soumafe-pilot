<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'prenom', 'email', 'password', 'pole_id', 'profil_mesure', 'role', 'actif'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['email_verified_at' => 'datetime', 'password' => 'hashed', 'actif' => 'boolean'];

    public function pole(): BelongsTo { return $this->belongsTo(Pole::class); }
    public function taches(): HasMany { return $this->hasMany(Tache::class); }
    public function objectifs(): HasMany { return $this->hasMany(Objectif::class); }
    public function joursSaisie(): HasMany { return $this->hasMany(JourSaisie::class); }
    public function projets(): BelongsToMany { return $this->belongsToMany(Projet::class); }

    public function nomComplet(): string { return trim("{$this->name} {$this->prenom}"); }
    public function estAdmin(): bool { return $this->role === 'administrateur'; }
    public function aRole(string ...$roles): bool { return in_array($this->role, $roles, true); }
}
