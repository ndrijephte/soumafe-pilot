<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Objectif extends Model
{
    protected $fillable = ['user_id', 'source', 'libelle', 'ponderation', 'periode', 'avancement', 'statut_validation'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function scopePeriode($q, string $p) { return $q->where('periode', $p); }
    public function scopeValides($q) { return $q->where('statut_validation', 'valide'); }
}
