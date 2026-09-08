<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaiementPenalite extends Model
{
    protected $table = 'paiements_penalites';

    protected $fillable = [
        'penalite_id', 'montant', 'date_paiement', 'mode_paiement',
        'recu_par', 'observations', 'emprunt_id',
    ];

    protected $casts = ['date_paiement' => 'date'];

    public function penalite(): BelongsTo { return $this->belongsTo(Penalite::class); }
    public function emprunt(): BelongsTo { return $this->belongsTo(Emprunt::class); }
}
