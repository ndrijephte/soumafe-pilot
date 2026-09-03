<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Observation extends Model
{
    protected $fillable = [
        'code', 'projet_id', 'date_reception', 'description', 'type_difficulte', 'priorite',
        'responsable_id', 'statut', 'origine', 'commentaires',
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
    public function declarant(): BelongsTo   { return $this->belongsTo(User::class, 'declare_par'); }
    public function resoluteur(): BelongsTo  { return $this->belongsTo(User::class, 'resolu_par'); }

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
