<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id', 'user_name', 'action', 'module', 'cible', 'details', 'ip_address', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    /**
     * Enregistrer une action d'audit.
     */
    public static function log(string $action, string $module, ?string $cible = null, ?string $details = null): void
    {
        if (!Schema::hasTable('audit_logs')) return;

        $user = auth()->user();
        static::create([
            'user_id'    => $user?->id,
            'user_name'  => $user?->name ?? 'Système',
            'action'     => $action,
            'module'     => $module,
            'cible'      => $cible,
            'details'    => $details,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
