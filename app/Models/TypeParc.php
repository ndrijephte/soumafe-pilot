<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TypeParc extends Model
{
    protected $table = 'types_parc';
    protected $fillable = ['categorie', 'code', 'libelle', 'actif', 'ordre', 'intervalle_jours'];
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

    /** Libellé lisible d'un motif d'absence / d'écart enregistré sur une recette (« vehicule_garage » → « Véhicule au garage »). */
    public static function libelleMotif(?string $code): string
    {
        static $libelles = null;
        if ($code === null || $code === '') return '';
        $libelles ??= Schema::hasTable('types_parc')
            ? static::where('categorie', 'motif_absence')->pluck('libelle', 'code')->all() : [];
        $connus = ['sous_objectif' => 'Objectif non atteint', 'jour_repos' => 'Jour de repos'];
        return $libelles[$code] ?? $connus[$code] ?? ucfirst(str_replace('_', ' ', $code));
    }
}
