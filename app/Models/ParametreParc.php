<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ParametreParc extends Model
{
    protected $table = 'parametres_parc';
    protected $fillable = ['cle', 'valeur', 'libelle', 'groupe'];

    /** Récupère une valeur par sa clé, avec fallback. */
    public static function val(string $cle, string $default = '0'): string
    {
        if (!Schema::hasTable('parametres_parc')) return $default;
        return static::where('cle', $cle)->value('valeur') ?? $default;
    }

    /** Retourne l'objectif recette selon catégorie véhicule + type de jour. */
    public static function objectifRecette(string $categorie, string $typeJour): int
    {
        $suffixe = in_array($typeJour, ['ferie', 'dimanche']) ? 'ferie' : 'ouvrable';
        $cle = "objectif_{$categorie}_{$suffixe}";
        return (int) static::val($cle, '0');
    }
}
