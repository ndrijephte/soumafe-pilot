<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Recouvrement extends Model
{
    protected $fillable = [
        'recette_vehicule_id', 'penalite_id', 'chauffeur_id', 'chauffeur_nom', 'vehicule_id',
        'date_recette', 'montant_verse', 'objectif', 'deficit', 'montant_recouvre',
        'solde', 'statut', 'type_recouvrement', 'motif', 'justification_valable', 'observations', 'cree_par',
        'motif_annulation', 'annule_par', 'annule_le',
    ];

    protected $casts = ['date_recette' => 'date', 'justification_valable' => 'boolean'];

    public function recette(): BelongsTo { return $this->belongsTo(RecetteVehicule::class, 'recette_vehicule_id'); }
    public function penalite(): BelongsTo { return $this->belongsTo(Penalite::class); }
    public function chauffeur(): BelongsTo { return $this->belongsTo(Chauffeur::class); }
    public function vehicule(): BelongsTo { return $this->belongsTo(Vehicule::class); }
    public function versements(): HasMany { return $this->hasMany(VersementRecouvrement::class); }

    public function estActif(): bool { return $this->statut === 'actif'; }
    public function estSolde(): bool { return $this->statut === 'solde'; }

    public function recalculerSolde(): void
    {
        $this->montant_recouvre = $this->versements()->sum('montant');
        $this->solde = $this->deficit - $this->montant_recouvre;
        if ($this->solde <= 0) {
            $this->solde = 0;
            $this->statut = 'solde';
        }
        $this->save();
    }
}
