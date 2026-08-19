<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Service extends Model
{
    protected $fillable = ['libelle', 'domaine_id'];
    public function domaine(): BelongsTo { return $this->belongsTo(Domaine::class); }
    public function poles(): HasMany { return $this->hasMany(Pole::class); }
}
