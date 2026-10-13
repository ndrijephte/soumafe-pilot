<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Panne extends Model
{
    protected $fillable = [
        'vehicule_id', 'date_declaration', 'description', 'urgence',
        'diagnostic', 'decision', 'date_debut_intervention', 'date_cloture',
        'delai_resolution', 'cout_reel', 'statut', 'emprunt_id',
    ];

    protected $casts = [
        'date_declaration' => 'date',
        'date_debut_intervention' => 'date',
        'date_cloture' => 'date',
    ];

    public function vehicule(): BelongsTo { return $this->belongsTo(Vehicule::class); }
    public function emprunt(): BelongsTo { return $this->belongsTo(Emprunt::class); }
    public function lignes(): HasMany { return $this->hasMany(LigneInterventionPanne::class); }

    protected static function booted(): void
    {
        static::saved(fn(Panne $p) => $p->synchroniserCaisse());
        static::deleted(fn(Panne $p) => MouvementCaisse::where('source_type', 'panne')->where('source_id', $p->id)->delete());
    }

    /** Le paiement de l'intervention (total de la facture) sort de la caisse générale ; rien tant que le coût est nul. */
    public function synchroniserCaisse(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('mouvements_caisse')) return;
        if ((int) $this->cout_reel <= 0) {
            MouvementCaisse::where('source_type', 'panne')->where('source_id', $this->id)->delete();
            return;
        }
        MouvementCaisse::updateOrCreate(
            ['source_type' => 'panne', 'source_id' => $this->id],
            ['caisse_code' => 'generale', 'sens' => 'sortie', 'montant' => (int) $this->cout_reel,
             'date_mouvement' => $this->date_cloture ?? $this->date_debut_intervention ?? $this->date_declaration ?? today(),
             'libelle' => 'Intervention panne — ' . ($this->vehicule?->immatriculation ?? ''),
             'cree_par' => auth()->user()?->name]
        );
    }

    /** Recalculer le coût réel depuis les lignes d'intervention */
    public function recalculerCout(): void
    {
        $this->cout_reel = $this->lignes()->sum('montant');
        $this->save();
    }

    public function statutLabel(): string
    {
        return match ($this->statut) {
            'declaree'         => 'Déclarée',
            'en_diagnostic'    => 'En diagnostic',
            'en_intervention'  => 'En intervention',
            'cloturee'         => 'Clôturée',
            default            => ucfirst(str_replace('_', ' ', $this->statut)),
        };
    }
}
