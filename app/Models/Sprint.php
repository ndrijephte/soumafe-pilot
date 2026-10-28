<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class Sprint extends Model
{
    protected $fillable = ['projet_id', 'numero', 'objectif', 'date_debut', 'date_fin', 'engagement_points', 'statut', 'visible_collaborateurs', 'collaborateurs_exclus', 'justification_arret', 'arrete_le'];
    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date', 'arrete_le' => 'datetime'];

    /** IDs des collaborateurs exclus (JSON → array). */
    public function getExclusAttribute(): array
    {
        return json_decode($this->collaborateurs_exclus ?? '[]', true) ?: [];
    }

    /** Ce sprint est-il visible pour un collaborateur donné ? */
    public function visiblePour(int $userId): bool
    {
        if (!$this->visible_collaborateurs) return false;
        return !in_array($userId, $this->exclus);
    }

    public function projet(): BelongsTo { return $this->belongsTo(Projet::class); }
    public function taches(): HasMany { return $this->hasMany(Tache::class); }
    /** Sprint Backlog : user stories retenues pour ce sprint. */
    public function userStories(): BelongsToMany
    {
        return $this->belongsToMany(UserStory::class, 'sprint_user_stories')->withPivot('statut_sprint')->withTimestamps();
    }
    /** Ajustements d'équipe propres au sprint (ajouts hors équipe projet, retraits). */
    public function equipeAjustements(): HasMany { return $this->hasMany(SprintEquipe::class); }
    public function dailyScrums(): HasMany { return $this->hasMany(DailyScrum::class)->orderBy('numero_jour'); }

    /** Durée du sprint en jours ouvrés (lundi → vendredi). */
    public function dureeJours(): int
    {
        return Tache::joursOuvres($this->date_debut, $this->date_fin);
    }

    /**
     * Points engagés : somme des story points des user stories du Sprint Backlog
     * (repli sur l'engagement saisi si aucune story n'est estimée).
     */
    public function pointsEngages(): int
    {
        $pts = (int) $this->userStories->sum('effort_estime');
        return $pts ?: (int) $this->engagement_points;
    }

    /**
     * Points livrés : story points des user stories terminées dans le sprint ;
     * sans story estimée, points des tâches terminées (ancien mode).
     */
    public function pointsLivres(): int
    {
        if ($this->userStories->sum('effort_estime') > 0) {
            return (int) $this->userStories->where('pivot.statut_sprint', 'termine')->sum('effort_estime');
        }
        return (int) $this->taches()->where('statut', 'termine')->sum('points');
    }
}
