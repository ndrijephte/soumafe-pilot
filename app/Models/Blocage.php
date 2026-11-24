<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blocage extends Model
{
    use Concerns\JournaliseActivite;

    public function journalModule(): string { return 'blocages'; }
    public function journalCible(): string { return 'Blocage : ' . (Tache::withTrashed()->whereKey($this->tache_id)->value('intitule') ?? "tâche #{$this->tache_id}"); }
    public function journalChamps(): array { return ['date_levee', 'intervenant_id', 'motif']; }

    protected $fillable = ['tache_id', 'motif', 'type', 'tache_liee_id', 'recommandation', 'leveur_pressenti', 'intervenant_id', 'date_blocage', 'date_levee'];
    protected $casts = ['date_blocage' => 'datetime', 'date_levee' => 'datetime'];

    public function tache(): BelongsTo { return $this->belongsTo(Tache::class); }
    public function tacheLiee(): BelongsTo { return $this->belongsTo(Tache::class, 'tache_liee_id'); }
    public function intervenant(): BelongsTo { return $this->belongsTo(User::class, 'intervenant_id'); }

    /** Délai de levée en heures - KPI « délai de levée de blocage ». */
    public function delaiHeures(): ?float
    {
        return $this->date_levee ? $this->date_blocage->diffInHours($this->date_levee) : null;
    }
}
