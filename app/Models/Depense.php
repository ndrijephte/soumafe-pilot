<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depense extends Model
{
    protected $fillable = [
        'date_depense', 'type_depense', 'fournisseur', 'vehicule_id', 'montant',
        'moyen_paiement', 'justificatif', 'caisse_source',
        'commentaire', 'cree_par', 'emprunt_id',
    ];

    protected $casts = ['date_depense' => 'date', 'montant' => 'integer'];

    protected static function booted(): void
    {
        static::saved(fn(Depense $d) => $d->synchroniserCaisse());
        static::deleted(fn(Depense $d) => MouvementCaisse::where('source_type', 'depense')->where('source_id', $d->id)->delete());
    }

    /** La dépense sort de sa caisse (générale par défaut) : un seul mouvement, tenu à jour. */
    public function synchroniserCaisse(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('mouvements_caisse')) return;
        MouvementCaisse::updateOrCreate(
            ['source_type' => 'depense', 'source_id' => $this->id],
            ['caisse_code' => $this->caisse_source ?: 'generale', 'sens' => 'sortie', 'montant' => $this->montant,
             'date_mouvement' => $this->date_depense, 'libelle' => 'Dépense — ' . $this->typeLibelle(),
             'cree_par' => auth()->user()?->name ?? $this->cree_par]
        );
    }

    public function vehicule(): BelongsTo { return $this->belongsTo(Vehicule::class); }
    public function emprunt(): BelongsTo { return $this->belongsTo(Emprunt::class); }

    public function typeLibelle(): string
    {
        $types = TypeParc::depenses()->pluck('libelle', 'code')->toArray();
        return $types[$this->type_depense] ?? ucfirst(str_replace('_', ' ', $this->type_depense));
    }
}
