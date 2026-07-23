<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Illuminate\Database\Eloquent\SoftDeletes;

class Tache extends Model
{
    use SoftDeletes;
    protected $table = 'taches';
    protected $fillable = ['user_id', 'type', 'intitule', 'projet_id', 'sprint_id', 'activite',
        'priorite', 'date_debut', 'echeance', 'date_fin_reelle', 'points', 'statut'];
    protected $casts = ['date_debut' => 'date', 'echeance' => 'date', 'date_fin_reelle' => 'date'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function projet(): BelongsTo { return $this->belongsTo(Projet::class); }
    public function sprint(): BelongsTo { return $this->belongsTo(Sprint::class); }
    public function blocages(): HasMany { return $this->hasMany(Blocage::class); }
    public function commentaires(): HasMany { return $this->hasMany(Commentaire::class); }
    public function arbitrage(): HasOne { return $this->hasOne(ArbitrageT3::class); }

    public function scopeDuJour($q, $date = null) { return $q->whereDate('date_debut', $date ?? today()); }
    public function scopeEnRetard($q) { return $q->whereNull('date_fin_reelle')->whereDate('echeance', '<', today()); }
}
