<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    protected $fillable = ['user_id', 'date_debut', 'date_fin', 'type', 'motif', 'declare_par'];
    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function declarant(): BelongsTo { return $this->belongsTo(User::class, 'declare_par'); }

    /** Vérifie si une date donnée tombe dans cette période d'absence. */
    public function couvre(\Carbon\Carbon $date): bool
    {
        return $date->between($this->date_debut, $this->date_fin);
    }

    /** Label du type. */
    public function labelType(): string
    {
        return match ($this->type) {
            'conge' => 'Congé',
            'mission' => 'Mission',
            'maladie' => 'Maladie',
            'formation' => 'Formation',
            default => 'Autre',
        };
    }

    /** Scope : absences couvrant une date donnée pour un user. */
    public function scopePourDate($query, int $userId, $date)
    {
        return $query->where('user_id', $userId)
            ->whereDate('date_debut', '<=', $date)
            ->whereDate('date_fin', '>=', $date);
    }
}
