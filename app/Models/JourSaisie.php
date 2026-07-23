<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourSaisie extends Model
{
    protected $table = 'jours_saisie';
    protected $fillable = ['user_id', 'date', 'statut', 'justification_commentaire_id'];
    protected $casts = ['date' => 'date'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function justification(): BelongsTo { return $this->belongsTo(Commentaire::class, 'justification_commentaire_id'); }
}
