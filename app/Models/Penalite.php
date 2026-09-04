<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Penalite extends Model
{
    protected $fillable = [
        'vehicule_id', 'date_infraction', 'type', 'chauffeur', 'lieu',
        'description', 'montant', 'montant_paye', 'statut', 'date_paiement', 'observations',
    ];

    protected $casts = [
        'date_infraction' => 'date',
        'date_paiement' => 'date',
    ];

    public function vehicule(): BelongsTo { return $this->belongsTo(Vehicule::class); }

    public function paiements(): HasMany { return $this->hasMany(PaiementPenalite::class); }

    public function soldeRestant(): int
    {
        return max(0, $this->montant - ($this->montant_paye ?? 0));
    }

    public function recalculerPaiement(): void
    {
        $totalPaye = $this->paiements()->sum('montant');
        $this->montant_paye = $totalPaye;
        if ($totalPaye >= $this->montant) {
            $this->statut = 'payee';
            $this->date_paiement = now();
        } elseif ($totalPaye > 0) {
            $this->statut = 'en_cours';
        }
        $this->save();
    }

    public function typeLabel(): string
    {
        $tp = TypeParc::where('categorie', 'penalite')->where('code', $this->type)->first();
        return $tp ? $tp->libelle : ucfirst(str_replace('_', ' ', $this->type));
    }

    public function statutLabel(): string
    {
        return match ($this->statut) {
            'non_payee' => 'Non payée',
            'en_cours'  => 'En cours',
            'payee'     => 'Payée',
            'contestee' => 'Contestée',
            default     => ucfirst(str_replace('_', ' ', $this->statut)),
        };
    }
}
