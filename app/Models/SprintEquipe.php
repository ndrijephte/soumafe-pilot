<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ajustement de l'équipe d'un sprint : collaborateur ajouté (hors équipe projet) ou retiré pour ce sprint. */
class SprintEquipe extends Model
{
    protected $table = 'sprint_equipe';
    protected $fillable = ['sprint_id', 'user_id', 'statut', 'motif', 'par_id'];

    public function sprint(): BelongsTo { return $this->belongsTo(Sprint::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function par(): BelongsTo { return $this->belongsTo(User::class, 'par_id'); }
}
