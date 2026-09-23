<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanningJournalier extends Model
{
    protected $table = 'planning_journalier';

    protected $fillable = [
        'date_jour', 'vehicule_id', 'chauffeur_id', 'chauffeur_nom',
        'action', 'motif', 'chauffeur_remplacant_id', 'chauffeur_remplacant_nom',
        'observations', 'cree_par', 'modifie_par', 'modifie_le',
    ];

    protected $casts = ['date_jour' => 'date'];

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class);
    }

    public function remplacant(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class, 'chauffeur_remplacant_id');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'en_service'    => 'En service',
            'repos'         => 'Repos',
            'indisponible'  => 'Indisponible',
            'remplacement'  => 'Remplacement',
            'conge'         => 'Congé',
            'suspension'    => 'Suspension',
            'autre'         => 'Autre',
            default         => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function estEnService(): bool
    {
        return $this->action === 'en_service' || $this->action === 'remplacement';
    }
}
