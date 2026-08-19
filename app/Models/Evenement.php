<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Evenement extends Model
{
    protected $fillable = ['user_id', 'date', 'type', 'description', 'action_engagee', 'impact', 'recommandation', 'traite_par_id', 'traite_le'];
    protected $casts = ['date' => 'date', 'traite_le' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function traitePar(): BelongsTo { return $this->belongsTo(User::class, 'traite_par_id'); }
    public function estTraite(): bool { return !empty($this->recommandation); }

    public function labelType(): string
    {
        if (Schema::hasTable('types_evenement')) {
            $type = TypeEvenement::where('code', $this->type)->first();
            if ($type) return $type->libelle;
        }
        return match ($this->type) {
            'interruption_service' => 'Interruption de service',
            'absence' => 'Absence',
            'condoleance' => 'Condoléances',
            'reunion_exceptionnelle' => 'Réunion exceptionnelle',
            default => 'Autre',
        };
    }

    public function couleurType(): array
    {
        if (Schema::hasTable('types_evenement')) {
            $type = TypeEvenement::where('code', $this->type)->first();
            if ($type) return ['fond' => $type->couleur_fond, 'texte' => $type->couleur_texte];
        }
        return match ($this->type) {
            'interruption_service' => ['fond' => '#fee2e2', 'texte' => '#b91c1c'],
            'absence' => ['fond' => '#fef3c7', 'texte' => '#b45309'],
            'condoleance' => ['fond' => '#e2e8f0', 'texte' => '#475569'],
            'reunion_exceptionnelle' => ['fond' => '#dbeafe', 'texte' => '#1d4ed8'],
            default => ['fond' => '#f1f5f9', 'texte' => '#64748b'],
        };
    }
}
