<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Emprunt extends Model
{
    protected $fillable = [
        'montant', 'date_emprunt', 'preteur', 'motif',
        'source_type', 'source_id', 'vehicule_id',
        'montant_rembourse', 'statut', 'cree_par',
    ];

    protected $casts = ['date_emprunt' => 'date', 'montant' => 'integer', 'montant_rembourse' => 'integer'];

    public function vehicule(): BelongsTo { return $this->belongsTo(Vehicule::class); }

    public function remboursements(): HasMany { return $this->hasMany(RemboursementEmprunt::class); }

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

    public function sourceLabel(): string
    {
        return match ($this->source_type) {
            'panne'       => 'Panne',
            'penalite'    => 'Pénalité',
            'maintenance' => 'Maintenance',
            default       => 'Autre',
        };
    }

    public function statutLabel(): string
    {
        return $this->statut === 'rembourse' ? 'Remboursé' : 'En cours';
    }
}
