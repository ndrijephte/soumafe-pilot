<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

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

    /** Points livrés (T1 terminées) — base du burndown et de la vélocité. */
    public function pointsLivres(): int
    {
        return (int) $this->taches()->where('statut', 'termine')->sum('points');
    }
}
