<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersementRecouvrement extends Model
{
    protected $table = 'versements_recouvrement';

    protected $fillable = [
        'recouvrement_id', 'montant', 'date_versement', 'mode_paiement',
        'source_paiement', 'depense_id', 'recu_par', 'observations',
    ];

    protected $casts = ['date_versement' => 'date'];

    public function recouvrement(): BelongsTo { return $this->belongsTo(Recouvrement::class); }
    public function depense(): BelongsTo { return $this->belongsTo(Depense::class); }

    public function estEntreprise(): bool { return $this->source_paiement === 'entreprise'; }

    /**
     * Versements qui s'ajoutent à la recette d'un chauffeur : déficits de recette (pas les remboursements de pénalité)
     * payés par le chauffeur lui-même, comptés à la date du versement, quel que soit l'exercice du déficit d'origine.
     */
    public static function recetteChauffeurs(): Builder
    {
        return static::join('recouvrements', 'versements_recouvrement.recouvrement_id', '=', 'recouvrements.id')
            ->where(fn($q) => $q->where('versements_recouvrement.source_paiement', 'chauffeur')->orWhereNull('versements_recouvrement.source_paiement'))
            ->where(fn($q) => $q->where('recouvrements.type_recouvrement', 'recette')->orWhereNull('recouvrements.type_recouvrement'));
    }
}
