<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueAvancement extends Model
{
    protected $table = 'historique_avancement';

    protected $fillable = [
        'objectif_id', 'date', 'avancement', 'source',
    ];

    protected $casts = [
        'date' => 'date',
        'avancement' => 'integer',
    ];

    public function objectif(): BelongsTo
    {
        return $this->belongsTo(Objectif::class);
    }
}
