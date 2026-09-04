<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantReunion extends Model
{
    protected $table = 'participants_reunion';

    protected $fillable = ['type_reunion_id', 'user_id', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    public function typeReunion(): BelongsTo { return $this->belongsTo(TypeReunion::class, 'type_reunion_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
