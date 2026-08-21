<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penalite extends Model
{
    protected $fillable = [
        'vehicule_id', 'date_infraction', 'type', 'chauffeur', 'lieu',
        'description', 'montant', 'statut', 'date_paiement', 'observations',
    ];

    protected $casts = [
        'date_infraction' => 'date',
        'date_paiement' => 'date',
    ];

    public function vehicule(): BelongsTo { return $this->belongsTo(Vehicule::class); }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'contravention'    => 'Contravention',
            'exces_vitesse'    => 'Excès de vitesse',
            'stationnement'    => 'Stationnement',
            'defaut_document'  => 'Défaut de document',
            default            => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function statutLabel(): string
    {
        return match ($this->statut) {
            'non_payee' => 'Non payée',
            'payee'     => 'Payée',
            'contestee' => 'Contestée',
            default     => ucfirst(str_replace('_', ' ', $this->statut)),
        };
    }
}
