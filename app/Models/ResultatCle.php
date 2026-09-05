<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultatCle extends Model
{
    protected $table = 'resultats_cles';

    protected $fillable = [
        'objectif_id', 'libelle', 'type_mesure', 'unite',
        'valeur_cible', 'valeur_actuelle', 'poids', 'ordre',
        'valide', 'date_validation',
    ];

    protected $casts = [
        'valeur_cible' => 'float',
        'valeur_actuelle' => 'float',
        'poids' => 'integer',
        'ordre' => 'integer',
        'valide' => 'boolean',
        'date_validation' => 'date',
    ];

    /** Statut de validation : en_attente | atteint | non_atteint */
    public function statutValidation(): string
    {
        if (is_null($this->valide)) return 'en_attente';
        return $this->valide ? 'atteint' : 'non_atteint';
    }

    public function objectif(): BelongsTo
    {
        return $this->belongsTo(Objectif::class);
    }

    /** Progression 0-100 de ce KR. */
    public function progression(): int
    {
        if ($this->type_mesure === 'binaire') {
            return $this->valeur_actuelle >= 1 ? 100 : 0;
        }
        if ($this->valeur_cible <= 0) return 0;
        return (int) min(100, round($this->valeur_actuelle / $this->valeur_cible * 100));
    }

    /** Label court pour le type de mesure. */
    public function labelUnite(): string
    {
        if ($this->unite) return $this->unite;
        return match ($this->type_mesure) {
            'pourcentage' => '%',
            'monnaie' => 'FCFA',
            'binaire' => 'Oui/Non',
            default => '',
        };
    }
}
