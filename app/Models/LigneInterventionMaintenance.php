<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneInterventionMaintenance extends Model
{
    protected $table = 'lignes_intervention_maintenance';
    protected $fillable = ['maintenance_id', 'designation', 'quantite', 'prix_unitaire', 'montant'];

    public function maintenance(): BelongsTo { return $this->belongsTo(MaintenancePreventive::class, 'maintenance_id'); }
}
