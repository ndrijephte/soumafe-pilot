<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersementRecouvrement extends Model
{
    protected $table = 'versements_recouvrement';

    protected $fillable = [
        'recouvrement_id', 'montant', 'date_versement', 'mode_paiement',
        'recu_par', 'observations',
    ];

    protected $casts = ['date_versement' => 'date'];

    public function recouvrement(): BelongsTo { return $this->belongsTo(Recouvrement::class); }
}
