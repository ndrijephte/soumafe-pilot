<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleReunion extends Model
{
    protected $table = 'roles_reunion';

    protected $fillable = ['type_reunion_id', 'code', 'libelle', 'ordre'];

    public function typeReunion(): BelongsTo { return $this->belongsTo(TypeReunion::class, 'type_reunion_id'); }
}
