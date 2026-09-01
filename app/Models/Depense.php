<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Depense extends Model
{
    protected $fillable = [
        'date_depense', 'type_depense', 'montant', 'commentaire', 'cree_par',
    ];

    protected $casts = ['date_depense' => 'date', 'montant' => 'integer'];

    public function typeLibelle(): string
    {
        $types = TypeParc::depenses()->pluck('libelle', 'code')->toArray();
        return $types[$this->type_depense] ?? ucfirst(str_replace('_', ' ', $this->type_depense));
    }
}
