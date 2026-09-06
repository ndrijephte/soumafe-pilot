<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourOuvrableSpecial extends Model
{
    protected $table = 'jours_ouvrables_speciaux';
    protected $fillable = ['date', 'motif', 'created_by'];
    protected $casts = ['date' => 'date'];

    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Vérifie si une date (weekend, férié, etc.) est définie comme jour ouvrable spécial.
     */
    public static function estOuvrable(\Carbon\Carbon $date): bool
    {
        return self::where('date', $date->toDateString())->exists();
    }

    /**
     * Liste des jours ouvrables spéciaux d'une période.
     */
    public static function dansLaPeriode(\Carbon\Carbon $debut, \Carbon\Carbon $fin): \Illuminate\Support\Collection
    {
        return self::whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->pluck('date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString());
    }
}
