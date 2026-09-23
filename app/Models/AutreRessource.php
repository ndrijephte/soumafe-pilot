<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutreRessource extends Model
{
    protected $table = 'autres_ressources';

    protected $fillable = [
        'date_ressource', 'type_ressource', 'montant', 'source',
        'description', 'caisse_destination', 'vehicule_id', 'cree_par',
    ];

    protected $casts = ['date_ressource' => 'date', 'montant' => 'integer'];

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function typeLibelle(): string
    {
        return match ($this->type_ressource) {
            'don'                       => 'Don',
            'vente'                     => 'Vente',
            'remboursement_fournisseur' => 'Remboursement fournisseur',
            'autre'                     => 'Autre',
            default                     => ucfirst(str_replace('_', ' ', $this->type_ressource)),
        };
    }
}
