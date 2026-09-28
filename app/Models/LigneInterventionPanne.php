<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneInterventionPanne extends Model
{
    protected $table = 'lignes_intervention_panne';

    protected $fillable = [
        'panne_id', 'designation', 'quantite', 'prix_unitaire', 'montant',
    ];

    public function panne(): BelongsTo
    {
        return $this->belongsTo(Panne::class);
    }
}
