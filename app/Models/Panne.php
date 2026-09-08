<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Panne extends Model
{
    protected $fillable = [
        'vehicule_id', 'date_declaration', 'description', 'urgence',
        'diagnostic', 'decision', 'date_debut_intervention', 'date_cloture',
        'delai_resolution', 'cout_reel', 'statut', 'emprunt_id',
    ];

    protected $casts = [
        'date_declaration' => 'date',
        'date_debut_intervention' => 'date',
        'date_cloture' => 'date',
    ];

    public function vehicule(): BelongsTo { return $this->belongsTo(Vehicule::class); }
    public function emprunt(): BelongsTo { return $this->belongsTo(Emprunt::class); }

    public function statutLabel(): string
    {
        return match ($this->statut) {
            'declaree'         => 'Déclarée',
            'en_diagnostic'    => 'En diagnostic',
            'en_intervention'  => 'En intervention',
            'cloturee'         => 'Clôturée',
            default            => ucfirst(str_replace('_', ' ', $this->statut)),
        };
    }
}
