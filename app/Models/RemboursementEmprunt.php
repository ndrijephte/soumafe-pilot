<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemboursementEmprunt extends Model
{
    protected $table = 'remboursements_emprunt';

    protected $fillable = [
        'emprunt_id', 'montant', 'date_remboursement', 'mode_paiement',
        'observations', 'cree_par',
    ];

    protected $casts = ['date_remboursement' => 'date'];

    public function emprunt(): BelongsTo { return $this->belongsTo(Emprunt::class); }
}
