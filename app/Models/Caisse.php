<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caisse extends Model
{
    protected $fillable = ['code', 'libelle', 'description', 'solde_initial', 'actif'];

    protected $casts = ['actif' => 'boolean', 'solde_initial' => 'integer'];

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementCaisse::class, 'caisse_code', 'code');
    }

    public function soldeActuel(): int
    {
        $entrees = $this->mouvements()->where('sens', 'entree')->sum('montant');
        $sorties = $this->mouvements()->where('sens', 'sortie')->sum('montant');
        return $this->solde_initial + $entrees - $sorties;
    }
}
