<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemboursementPret extends Model
{
    protected $table = 'remboursements_prets';

    protected $fillable = [
        'pret_accorde_id', 'montant', 'date_remboursement',
        'mode_paiement', 'caisse_destination', 'observations', 'cree_par',
    ];

    protected $casts = ['date_remboursement' => 'date', 'montant' => 'integer'];

    public function pret(): BelongsTo
    {
        return $this->belongsTo(PretAccorde::class, 'pret_accorde_id');
    }
}
