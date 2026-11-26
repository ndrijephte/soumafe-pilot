<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/** Nature d'absence (congé, mission, maladie…) : liste paramétrable, enrichie depuis le formulaire de déclaration. */
class TypeAbsence extends Model
{
    protected $table = 'types_absence';
    protected $fillable = ['code', 'libelle', 'systeme'];
    protected $casts = ['systeme' => 'boolean'];

    /** Natures proposées, « Autre » en dernier. */
    public static function liste()
    {
        if (!Schema::hasTable('types_absence')) return collect();
        return static::orderByRaw("code = 'autre'")->orderBy('libelle')->get();
    }

    /** Libellé d'un code (mémorisé pour la requête). */
    public static function libelle(?string $code): ?string
    {
        static $cache = null;
        $cache ??= Schema::hasTable('types_absence') ? static::pluck('libelle', 'code')->all() : [];
        return $cache[$code] ?? null;
    }
}
