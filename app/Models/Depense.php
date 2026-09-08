<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depense extends Model
{
    protected $fillable = [
        'date_depense', 'type_depense', 'vehicule_id', 'montant', 'commentaire', 'cree_par', 'emprunt_id',
    ];

    protected $casts = ['date_depense' => 'date', 'montant' => 'integer'];

    public function vehicule(): BelongsTo { return $this->belongsTo(Vehicule::class); }
    public function emprunt(): BelongsTo { return $this->belongsTo(Emprunt::class); }

    public function typeLibelle(): string
    {
        $types = TypeParc::depenses()->pluck('libelle', 'code')->toArray();
        return $types[$this->type_depense] ?? ucfirst(str_replace('_', ' ', $this->type_depense));
    }
}
