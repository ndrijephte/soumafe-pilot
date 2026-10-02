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
        'type', 'titre', 'sprint_id', 'redacteur_id', 'designe_par', 'president_id',
        'date_reunion', 'heure_debut', 'heure_fin', 'lieu', 'statut',
        'donnees_json', 'fichier_path',
    ];

    protected $casts = [
        'date_reunion' => 'date',
    ];

    /* --- Relations --- */

    public function redacteur(): BelongsTo { return $this->belongsTo(User::class, 'redacteur_id'); }
    public function designePar(): BelongsTo { return $this->belongsTo(User::class, 'designe_par'); }
    public function president(): BelongsTo { return $this->belongsTo(User::class, 'president_id'); }
    public function sprint(): BelongsTo { return $this->belongsTo(Sprint::class); }

    /* --- Helpers --- */

    public function estBrouillon(): bool { return $this->statut === 'brouillon'; }
    public function estConfirme(): bool { return $this->statut === 'confirme'; }
    public function estReunion(): bool { return in_array($this->type, ['reunion_ordinaire', 'reunion_extraordinaire']); }

    public function typeLibelle(): string
    {
        return match ($this->type) {
            'reunion_ordinaire'      => 'Réunion ordinaire (hebdomadaire)',
            'reunion_extraordinaire' => 'Réunion extraordinaire (stratégique)',
            'demo_sprint'            => 'Démonstration sprint',
            default                  => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function typeCourt(): string
    {
        return match ($this->type) {
            'reunion_ordinaire'      => 'Réunion ordinaire',
            'reunion_extraordinaire' => 'Réunion extraordinaire',
            'demo_sprint'            => 'Démo sprint',
            default                  => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function getDonnees(): array
    {
        $d = json_decode($this->donnees_json ?? '{}', true) ?: [];

        // Anciens CR : la fonction n'était pas enregistrée dans la liste de présence
        if (!empty($d['presences']) && collect($d['presences'])->contains(fn($p) => !array_key_exists('fonction', $p))) {
            $fonctions = User::all()->mapWithKeys(fn($u) => [$u->nomComplet() => $u->roleLibelle()]);
            foreach ($d['presences'] as &$p) {
                $p['fonction'] ??= $fonctions[$p['nom'] ?? ''] ?? '';
            }
            unset($p);
        }

        return $d;
    }

    public function setDonnees(array $data): void
    {
        $this->donnees_json = json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
