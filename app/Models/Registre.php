<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registre extends Model
{
    use SoftDeletes;
    protected $fillable = ['libelle', 'description', 'periodicite', 'definition_colonnes'];
    protected $casts = ['definition_colonnes' => 'array'];
    public function entrees(): HasMany { return $this->hasMany(RegistreEntree::class); }
}
