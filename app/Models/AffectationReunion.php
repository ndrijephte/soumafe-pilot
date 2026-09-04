<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffectationReunion extends Model
{
    protected $table = 'affectations_reunion';

    protected $fillable = ['type_reunion_id', 'role_reunion_id', 'user_id', 'date_reunion', 'statut', 'remplacant_id'];

    protected $casts = ['date_reunion' => 'date'];

    public function typeReunion(): BelongsTo { return $this->belongsTo(TypeReunion::class, 'type_reunion_id'); }
    public function role(): BelongsTo { return $this->belongsTo(RoleReunion::class, 'role_reunion_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function remplacant(): BelongsTo { return $this->belongsTo(User::class, 'remplacant_id'); }

    public function statutLabel(): string
    {
        return match ($this->statut) {
            'prevu' => 'Prévu',
            'present' => 'Présent',
            'absent' => 'Absent',
            'remplace' => 'Remplacé',
            default => ucfirst($this->statut),
        };
    }
}
