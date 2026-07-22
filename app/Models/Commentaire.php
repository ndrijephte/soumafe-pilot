<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commentaire extends Model
{
    protected $fillable = ['tache_id', 'user_id', 'contenu'];
    public function tache(): BelongsTo { return $this->belongsTo(Tache::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
