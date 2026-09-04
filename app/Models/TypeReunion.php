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
            'fin_sprint' => 'Fin de sprint',
            default => ucfirst($this->recurrence),
        };
    }

    public function couleur(): string
    {
        return match ($this->code) {
            'daily_scrum' => '#2E74B5',
            'coordination' => '#16a34a',
            'retrospective' => '#9333ea',
            'sprint_review' => '#ea580c',
            'planification' => '#0891b2',
            default => '#64748b',
        };
    }

    public function iconePath(): string
    {
        return match ($this->code) {
            'daily_scrum' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
            'coordination' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'retrospective' => '<polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>',
            'sprint_review' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
            'planification' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
            default => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        };
    }
}
