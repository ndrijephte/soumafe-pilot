<?php
namespace App\Services;

use App\Models\{AffectationReunion, User};
use Carbon\Carbon;

/**
 * Règle du planning des réunions : le scribe du jour est le rédacteur de tous les rapports
 * de la journée (Daily Scrum, Review, Rétrospective, comptes rendus).
 */
class RedacteurDuJour
{
    /** Identifiant du scribe affecté à cette date (remplaçant s'il est remplacé, personne s'il est absent). */
    public static function id(Carbon|string|null $date, ?int $projetId = null): ?int
    {
        if (!$date) return null;
        $date = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $affectations = AffectationReunion::with(['role', 'typeReunion'])
            ->whereDate('date_reunion', $date)
            ->whereHas('role', fn($q) => $q->where('code', 'scribe'))
            ->where(fn($q) => $q->whereNull('projet_id')->when($projetId, fn($p) => $p->orWhere('projet_id', $projetId)))
            ->get()
            // Priorité : affectation du projet, puis Daily Scrum, puis les autres réunions
            ->sortBy(fn($a) => [$a->projet_id && $a->projet_id === $projetId ? 0 : 1, $a->typeReunion?->code === 'daily_scrum' ? 0 : 1, $a->id]);
        foreach ($affectations as $a) {
            if ($a->statut === 'absent') continue;
            $id = $a->statut === 'remplace' && $a->remplacant_id ? $a->remplacant_id : $a->user_id;
            if ($id) return (int) $id;
        }
        return null;
    }

    public static function user(Carbon|string|null $date, ?int $projetId = null): ?User
    {
        $id = self::id($date, $projetId);
        return $id ? User::find($id) : null;
    }
}
