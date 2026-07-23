<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blocage extends Model
{
    protected $fillable = ['tache_id', 'motif', 'leveur_pressenti', 'date_blocage', 'date_levee'];
    protected $casts = ['date_blocage' => 'datetime', 'date_levee' => 'datetime'];

    public function tache(): BelongsTo { return $this->belongsTo(Tache::class); }

    /** Délai de levée en heures — KPI « délai de levée de blocage ». */
    public function delaiHeures(): ?float
    {
        return $this->date_levee ? $this->date_blocage->diffInHours($this->date_levee) : null;
    }
}
