<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Pole extends Model
{
    protected $fillable = ['libelle', 'service_id'];
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
}
