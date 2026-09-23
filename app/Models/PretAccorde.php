<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class PretAccorde extends Model
{
    protected $table = 'prets_accordes';

    protected $fillable = [
        'reference', 'montant', 'date_pret', 'beneficiaire', 'chauffeur_id',
        'motif', 'caisse_source', 'montant_rembourse', 'statut',
        'date_echeance', 'modalites', 'cree_par',
    ];

    protected $casts = [
        'date_pret' => 'date', 'date_echeance' => 'date',
        'montant' => 'integer', 'montant_rembourse' => 'integer',
    ];

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class);
    }

    public function remboursements(): HasMany
    {
        return $this->hasMany(RemboursementPret::class);
    }

    public function soldeRestant(): int
    {
        return max(0, $this->montant - ($this->montant_rembourse ?? 0));
    }

    public function recalculerRemboursement(): void
    {
        $total = $this->remboursements()->sum('montant');
        $this->montant_rembourse = $total;
        $this->statut = $total >= $this->montant ? 'rembourse' : 'en_cours';
        $this->save();
    }

    public function statutLabel(): string
    {
        return match ($this->statut) {
            'rembourse'      => 'Remboursé',
            'irrécouvrable'  => 'Irrécouvrable',
            default          => 'En cours',
        };
    }
}
