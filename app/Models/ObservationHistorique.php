<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservationHistorique extends Model
{
    public $timestamps = false;
    protected $fillable = ['observation_id', 'user_id', 'action', 'details', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function actionLibelle(): string
    {
        return match ($this->action) {
            'declaration'  => 'Déclaration',
            'statut'       => 'Changement de statut',
            'responsable'  => 'Responsable désigné',
            'delegation'   => 'Suivi délégué',
            'resolution'   => 'Résolution',
            'modification' => 'Modification',
            'commentaire'  => 'Commentaire de traitement',
            default        => ucfirst($this->action),
        };
    }
}
