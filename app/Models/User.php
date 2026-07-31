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

    public function projetsResponsable(): HasMany { return $this->hasMany(Projet::class, 'responsable_projet_id'); }

    public function nomComplet(): string { return trim("{$this->name} {$this->prenom}"); }

    public static function libelleRole(string $code): string
    {
        return match ($code) {
            'administrateur'     => 'Administrateur',
            'direction'          => 'Direction Générale',
            'charge_operations'  => 'Chargé des Opérations et Projets',
            'collaborateur'      => 'Collaborateur',
            'invite'             => 'Invité',
            default              => ucfirst(str_replace('_', ' ', $code)),
        };
    }

    public function roleLibelle(): string { return self::libelleRole($this->role); }
    public function estAdmin(): bool { return in_array($this->role, ['administrateur', 'charge_operations'], true); }
    public function aRole(string ...$roles): bool { return in_array($this->role, $roles, true); }
    public function estPilote(): bool { return $this->aRole('administrateur', 'direction', 'charge_operations'); }

    /** Vérifie si l'utilisateur est responsable d'un projet donné. */
    public function estResponsableProjet(?int $projetId = null): bool
    {
        if ($projetId === null) return $this->projetsResponsable()->exists();
        return Projet::where('id', $projetId)->where('responsable_projet_id', $this->id)->exists();
    }

    /** Peut gérer les sprints/tâches d'un projet (pilote toujours, responsable si délégation active). */
    public function peutGererProjet(?int $projetId = null): bool
    {
        if ($this->estPilote()) return true;
        if ($projetId === null) {
            // Sans argument : vérifie si responsable d'au moins un projet avec délégation
            return Projet::where('responsable_projet_id', $this->id)
                ->where('delegation_sprint', true)->exists();
        }
        $projet = Projet::find($projetId);
        if (!$projet) return false;
        return $projet->delegation_sprint && $projet->responsable_projet_id === $this->id;
    }

    /** Retourne les projets que l'utilisateur peut gérer (pour filtrer les selects). */
    public function projetsGerables()
    {
        if ($this->estPilote()) return Projet::where('statut', 'actif')->orderBy('libelle')->get();
        return Projet::where('responsable_projet_id', $this->id)
            ->where('delegation_sprint', true)->where('statut', 'actif')
            ->orderBy('libelle')->get();
    }
}
