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

        // Colonnes limitées (action 30, module 50, cible 100) : un texte long est coupé et conservé en entier dans « details »
        if ($cible !== null && mb_strlen($cible) > 100) {
            $details = trim($cible . ($details ? "\n" . $details : ''));
            $cible = mb_substr($cible, 0, 99) . '…';
        }
        $user = auth()->user();
        static::create([
            'user_id'    => $user?->id,
            'user_name'  => mb_substr($user?->name ?? 'Système', 0, 100),
            'action'     => mb_substr($action, 0, 30),
            'module'     => mb_substr($module, 0, 50),
            'cible'      => $cible,
            'details'    => $details,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
