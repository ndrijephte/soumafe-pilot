<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Observation extends Model
{
    protected $fillable = [
        'code', 'projet_id', 'date_reception', 'description', 'type_difficulte', 'priorite',
        'responsable_id', 'suivi_par', 'statut', 'origine', 'commentaires',
        'mode_execution', 'date_livraison', 'commentaire_resolution',
        'resolu_par', 'resolu_le', 'declare_par',
    ];

    protected $casts = [
        'date_reception' => 'date',
        'date_livraison' => 'date',
        'resolu_le'      => 'datetime',
    ];

    /* --- Relations --- */
    public function projet(): BelongsTo      { return $this->belongsTo(Projet::class); }
    public function responsable(): BelongsTo { return $this->belongsTo(User::class, 'responsable_id'); }
    public function suiveur(): BelongsTo     { return $this->belongsTo(User::class, 'suivi_par'); }
    public function declarant(): BelongsTo   { return $this->belongsTo(User::class, 'declare_par'); }
    public function resoluteur(): BelongsTo  { return $this->belongsTo(User::class, 'resolu_par'); }
    public function historiques(): HasMany   { return $this->hasMany(ObservationHistorique::class)->orderBy('created_at')->orderBy('id'); }

    /** Trace une action dans l'historique de l'observation (résumé des actions). */
    public function journaliser(string $action, ?string $details = null): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('observation_historiques')) return;
        try {
            $this->historiques()->create(['user_id' => auth()->id(), 'action' => $action, 'details' => $details ? mb_substr($details, 0, 1000) : null, 'created_at' => now()]);
        } catch (\Throwable $e) {
            // L'historique ne doit jamais empêcher l'enregistrement de l'observation elle-même
            report($e);
        }
    }

    /** Collaborateur désigné : celui à qui au moins un suivi a été délégué. */
    public static function estCollaborateurDesigne(?User $u): bool
    {
        return $u !== null && static::where('suivi_par', $u->id)->exists();
    }

    public static function peutDeclarer(?User $u): bool
    {
        return $u !== null && ($u->estPilote() || static::estCollaborateurDesigne($u));
    }

    /* --- Helpers --- */
    public function statutLibelle(): string
    {
        return match ($this->statut) {
            'nouveau'    => 'Nouveau',
            'en_cours'   => 'En cours',
            'resolu'     => 'Résolu',
            'bloque'     => 'Bloqué',
            'rejete'     => 'Rejeté',
            default      => ucfirst(str_replace('_', ' ', $this->statut)),
        };
    }

    public function prioriteLibelle(): string
    {
        return match ($this->priorite) {
            'critique' => 'Critique',
            'haute'    => 'Haute',
            'moyenne'  => 'Moyenne',
            'basse'    => 'Basse',
            default    => ucfirst($this->priorite),
        };
    }

    /** Génère le prochain code OBS-XXX */
    public static function prochainCode(): string
    {
        $last = static::orderByDesc('id')->value('code');
        $num  = $last ? ((int) substr($last, 4)) + 1 : 1;
        return 'OBS-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
}
