<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TypeParc extends Model
{
    protected $table = 'types_parc';
    protected $fillable = ['categorie', 'code', 'libelle', 'actif', 'ordre'];
    protected $casts = ['actif' => 'boolean'];

    public static function penalites()
    {
        if (!Schema::hasTable('types_parc')) return collect();
        return static::where('categorie', 'penalite')->where('actif', true)->orderBy('ordre')->get();
    }

    public static function maintenances()
    {
        if (!Schema::hasTable('types_parc')) return collect();
        return static::where('categorie', 'maintenance')->where('actif', true)->orderBy('ordre')->get();
    }

    public static function motifsAbsence()
    {
        if (!Schema::hasTable('types_parc')) return collect();
        return static::where('categorie', 'motif_absence')->where('actif', true)->orderBy('ordre')->get();
    }

    public static function depenses()
    {
        if (!Schema::hasTable('types_parc')) return collect();
        return static::where('categorie', 'depense')->where('actif', true)->orderBy('ordre')->get();
    }

    public static function actionsPlanning()
    {
        if (!Schema::hasTable('types_parc')) return collect();
        return static::where('categorie', 'action_planning')->where('actif', true)->orderBy('ordre')->get();
    }

    public static function autresRessources()
    {
        if (!Schema::hasTable('types_parc')) return collect();
        return static::where('categorie', 'autre_ressource')->where('actif', true)->orderBy('ordre')->get();
    }
}
