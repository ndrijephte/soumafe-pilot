<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commentaire extends Model
{
    protected $fillable = ['tache_id', 'user_id', 'contenu', 'contexte'];

    public const CONTEXTES = [
        'validation' => ['label' => 'Validation', 'icon' => '✓', 'color' => 'var(--ok)'],
        'rejet'      => ['label' => 'Rejet', 'icon' => '✗', 'color' => 'var(--ko)'],
        'blocage'    => ['label' => 'Blocage', 'icon' => '⚠', 'color' => '#b45309'],
        'arbitrage'  => ['label' => 'Arbitrage', 'icon' => '⚖', 'color' => '#7c3aed'],
        'suivi'      => ['label' => 'Suivi', 'icon' => '◉', 'color' => 'var(--accent)'],
    ];

    public function tache(): BelongsTo { return $this->belongsTo(Tache::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
