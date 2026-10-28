<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class Projet extends Model
{
    use SoftDeletes;
    protected $fillable = ['code', 'libelle', 'client', 'responsable_projet_id', 'statut', 'delegation_sprint', 'date_debut', 'date_cible', 'daily_config'];
    protected $casts = ['date_debut' => 'date', 'date_cible' => 'date', 'delegation_sprint' => 'boolean', 'daily_config' => 'array'];

    /** Jours possibles du Daily Scrum (numéro ISO => libellé). */
    public const JOURS_DAILY = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];

    /**
     * Programmation du Daily Scrum du projet : jours, horaire, durée, lieu, création automatique.
     * Valeurs par défaut : du lundi au vendredi, à l'heure du type de réunion « Daily Scrum » du planning.
     */
    public function configDaily(): array
    {
        $type = TypeReunion::where('code', 'daily_scrum')->first();
        $defaut = ['jours' => [1, 2, 3, 4, 5], 'heure_debut' => $type?->heure ? substr($type->heure, 0, 5) : '09:00', 'duree' => 15, 'lieu' => null, 'creation_auto' => false];
        $c = array_merge($defaut, array_filter($this->daily_config ?? [], fn($v) => $v !== null && $v !== []));
        $c['jours'] = array_values(array_map('intval', $c['jours']));
        sort($c['jours']);
        $c['heure_fin'] = \Carbon\Carbon::createFromFormat('H:i', $c['heure_debut'])->addMinutes((int) $c['duree'])->format('H:i');
        return $c;
    }

    public function responsable(): BelongsTo { return $this->belongsTo(User::class, 'responsable_projet_id'); }
    /** Équipe du projet : collaborateurs actifs (ajout / retrait à tout moment). */
    public function equipe(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'projet_collaborateurs')->withPivot('role_projet', 'date_ajout', 'actif')->wherePivot('actif', true);
    }
    public function collaborateurs(): HasMany { return $this->hasMany(ProjetCollaborateur::class); }
    public function userStories(): HasMany { return $this->hasMany(UserStory::class); }

    /** L'utilisateur peut consulter l'espace du projet : pilote, responsable ou membre actif. */
    public function estVisiblePour(User $u): bool
    {
        return $u->estPilote() || (int) $this->responsable_projet_id === (int) $u->id
            || $this->collaborateurs()->where('user_id', $u->id)->where('actif', true)->exists();
    }
    public function sprints(): HasMany { return $this->hasMany(Sprint::class); }
    public function taches(): HasMany { return $this->hasMany(Tache::class); }
}
