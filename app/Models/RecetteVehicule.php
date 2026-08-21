<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecetteVehicule extends Model
{
    protected $table = 'recettes_vehicules';

    protected $fillable = [
        'date', 'type_jour', 'vehicule_id', 'chauffeur', 'recette', 'objectif',
        'ecart', 'mode_remise', 'controle_par', 'observations',
    ];

    protected $casts = ['date' => 'date'];

    public function vehicule(): BelongsTo { return $this->belongsTo(Vehicule::class); }
}
