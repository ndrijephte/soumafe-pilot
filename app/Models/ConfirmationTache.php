<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfirmationTache extends Model
{
    protected $table = 'confirmations_taches';
    protected $fillable = ['user_id', 'tache_id', 'date', 'statut', 'motif', 'plan_action'];
    protected $casts = ['date' => 'date'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function tache(): BelongsTo { return $this->belongsTo(Tache::class); }
}
