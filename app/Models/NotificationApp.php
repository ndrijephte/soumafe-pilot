<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationApp extends Model
{
    protected $table = 'notifications_app';
    protected $fillable = ['user_id', 'type', 'contenu', 'lu', 'canal'];
    protected $casts = ['lu' => 'boolean'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
