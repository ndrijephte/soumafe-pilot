<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class CompteRendu extends Model
{
    use HasFactory;

    protected $table = 'comptes_rendus';

    protected $fillable = [
        'type', 'titre', 'sprint_id', 'redacteur_id', 'designe_par',
        'date_reunion', 'lieu', 'statut', 'donnees_json', 'fichier_path',
    ];

    protected $casts = [
        'date_reunion' => 'date',
    ];

    /* --- Relations --- */

    public function redacteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redacteur_id');
    }

    public function designePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designe_par');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    /* --- Helpers --- */

    public function estBrouillon(): bool
    {
        return $this->statut === 'brouillon';
    }

    public function estConfirme(): bool
    {
        return $this->statut === 'confirme';
    }

    public function typeLibelle(): string
    {
        return match ($this->type) {
            'reunion_hebdo' => 'Réunion hebdomadaire',
            'demo_sprint'   => 'Démonstration sprint',
            default         => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function getDonnees(): array
    {
        return json_decode($this->donnees_json ?? '{}', true) ?: [];
    }

    public function setDonnees(array $data): void
    {
        $this->donnees_json = json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
