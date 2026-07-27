<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Objectif extends Model
{
    protected $fillable = [
        'user_id', 'type', 'projet_id', 'source', 'libelle',
        'ponderation', 'periode', 'date_debut', 'date_echeance',
        'avancement', 'evaluation', 'statut_validation', 'commentaire_pilote',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_echeance' => 'date',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function projet(): BelongsTo { return $this->belongsTo(Projet::class); }

    public function scopePeriode($q, string $p) { return $q->where('periode', $p); }
    public function scopeValides($q) { return $q->where('statut_validation', 'valide'); }
    public function scopeIndividuels($q) { return $q->where('type', 'individuel'); }
    public function scopeProjet($q) { return $q->where('type', 'projet'); }

    /** Mois disponibles pour le filtre (6 mois avant → 3 mois après). */
    public static function moisDisponibles(): array
    {
        $mois = [];
        $start = now()->subMonths(6)->startOfMonth();
        for ($i = 0; $i < 10; $i++) {
            $d = $start->copy()->addMonths($i);
            $mois[$d->format('Y-m')] = $d->translatedFormat('F Y');
        }
        return $mois;
    }

    /** Évaluation automatique basée sur avancement + échéance. */
    public function evaluerAuto(): string
    {
        if ($this->avancement >= 100) return 'atteint';
        if ($this->date_echeance && $this->date_echeance->isPast()) {
            return $this->avancement >= 60 ? 'partiel' : 'non_atteint';
        }
        return 'en_cours';
    }

    /** Score pondéré d'un ensemble d'objectifs. */
    public static function scoreGroupe($objectifs): int
    {
        $sommePond = $objectifs->sum('ponderation');
        if ($sommePond <= 0) return 0;
        return (int) round($objectifs->sum(fn($o) => $o->avancement * $o->ponderation / 100));
    }

    /** Jours restants avant échéance. */
    public function joursRestants(): ?int
    {
        if (!$this->date_echeance) return null;
        return (int) max(0, now()->diffInDays($this->date_echeance, false));
    }
}
