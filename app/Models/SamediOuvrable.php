<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SamediOuvrable extends Model
{
    protected $table = 'samedis_ouvrables';
    protected $fillable = ['date', 'motif', 'created_by'];
    protected $casts = ['date' => 'date'];

    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Vérifie si une date (samedi) est ouvrable.
     */
    public static function estOuvrable(\Carbon\Carbon $date): bool
    {
        if ($date->dayOfWeek !== 6) return false; // pas un samedi
        return self::where('date', $date->toDateString())->exists();
    }

    /**
     * Liste des samedis ouvrables d'une période.
     */
    public static function dansLaPeriode(\Carbon\Carbon $debut, \Carbon\Carbon $fin): \Illuminate\Support\Collection
    {
        return self::whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->pluck('date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString());
    }
}
