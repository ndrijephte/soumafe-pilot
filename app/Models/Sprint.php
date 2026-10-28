<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class Sprint extends Model
{
    protected $fillable = ['projet_id', 'numero', 'objectif', 'date_debut', 'date_fin', 'engagement_points', 'statut', 'visible_collaborateurs', 'collaborateurs_exclus', 'justification_arret', 'arrete_le', 'periode_exercice', 'periode_arbitree_par', 'periode_arbitree_le'];
    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date', 'arrete_le' => 'datetime', 'periode_exercice' => 'date', 'periode_arbitree_le' => 'datetime'];

    /* ─── Période d'exercice (05 → 04) ─── */

    /** Débuts des périodes d'exercice couvertes par le sprint. */
    public function periodesCouvertes(): \Illuminate\Support\Collection
    {
        return \App\Services\PeriodeExercice::couvertes($this->date_debut, $this->date_fin);
    }

    /** Sprint à cheval sur deux périodes d'exercice (ou plus). */
    public function estACheval(): bool { return $this->periodesCouvertes()->count() > 1; }

    /** Proposition d'arbitrage : la période qui contient le plus de jours ouvrés du sprint (à égalité, la dernière). */
    public function periodeProposee(): \Carbon\Carbon
    {
        return $this->periodesCouvertes()->sortBy(function ($debut) {
            [$d, $f] = \App\Services\PeriodeExercice::bornes($debut);
            return [-Tache::joursOuvres($this->date_debut->max($d), $this->date_fin->min($f)), -$debut->timestamp];
        })->first();
    }

    /** Période d'exercice retenue : unique, ou arbitrée par un pilote ; null tant qu'un sprint à cheval n'est pas arbitré. */
    public function periodeExercice(): ?\Carbon\Carbon
    {
        $couvertes = $this->periodesCouvertes();
        if ($this->periode_exercice && $couvertes->contains(fn($p) => $p->isSameDay($this->periode_exercice))) return $this->periode_exercice->copy();
        return $couvertes->count() === 1 ? $couvertes->first() : null;
    }

    public function arbitragePeriodeRequis(): bool { return $this->periodeExercice() === null; }
    public function arbitrePar(): BelongsTo { return $this->belongsTo(User::class, 'periode_arbitree_par'); }

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

    /** Durée du sprint en jours travaillés (horaires paramétrables). */
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
