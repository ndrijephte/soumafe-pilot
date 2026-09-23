<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementCaisse extends Model
{
    protected $table = 'mouvements_caisse';

    protected $fillable = [
        'caisse_code', 'sens', 'montant', 'date_mouvement',
        'source_type', 'source_id', 'libelle', 'reference', 'cree_par',
    ];

    protected $casts = ['date_mouvement' => 'date', 'montant' => 'integer'];

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class, 'caisse_code', 'code');
    }

    public function sensLabel(): string
    {
        return $this->sens === 'entree' ? 'Entrée' : 'Sortie';
    }
}
