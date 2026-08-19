<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domaine extends Model
{
    protected $fillable = ['libelle'];
    public function services(): HasMany { return $this->hasMany(Service::class); }
}
