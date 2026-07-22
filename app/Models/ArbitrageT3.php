<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArbitrageT3 extends Model
{
    protected $table = 'arbitrages_t3';
    protected $fillable = ['tache_id', 'demandeur_id', 'decideur_id', 'decision'];

    public function tache(): BelongsTo { return $this->belongsTo(Tache::class); }
    public function demandeur(): BelongsTo { return $this->belongsTo(User::class, 'demandeur_id'); }
    public function decideur(): BelongsTo { return $this->belongsTo(User::class, 'decideur_id'); }
}
