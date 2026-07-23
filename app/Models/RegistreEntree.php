<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistreEntree extends Model
{
    protected $table = 'registre_entrees';
    protected $fillable = ['registre_id', 'user_id', 'date', 'valeurs'];
    protected $casts = ['date' => 'date', 'valeurs' => 'array'];
    public function registre(): BelongsTo { return $this->belongsTo(Registre::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
