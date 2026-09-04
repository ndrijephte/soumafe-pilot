<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeReunion extends Model
{
    protected $table = 'types_reunion';

    protected $fillable = ['code', 'libelle', 'recurrence', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    public function roles(): HasMany { return $this->hasMany(RoleReunion::class, 'type_reunion_id')->orderBy('ordre'); }

    public function participants(): HasMany { return $this->hasMany(ParticipantReunion::class, 'type_reunion_id'); }

    public function affectations(): HasMany { return $this->hasMany(AffectationReunion::class, 'type_reunion_id'); }

    public function recurrenceLabel(): string
    {
        return match ($this->recurrence) {
            'quotidien' => 'Quotidien',
            'hebdomadaire' => 'Hebdomadaire',
            'bimensuel' => 'Bimensuel',
            'mensuel' => 'Mensuel',
            default => ucfirst($this->recurrence),
        };
    }
}
