<?php
namespace App\Services;

use App\Models\{Pointage, User};
use App\Services\ClasseurXlsx as X;
use Carbon\Carbon;

/**
 * Registre de pointage au format Excel de l'entreprise (classeur maison : titre, sous-titre, en-têtes bleus,
 * lignes alternées, retards et absences en rouge) :
 *  - « Synthèse » : une ligne par collaborateur (jours ouvrés, présences, retards, absences, heures, ponctualité) ;
 *  - « Détail » : une ligne par jour ouvré et par collaborateur, avec l'horaire attendu ce jour-là.
 */
final class ExportPointage
{
    public static function classeur(Carbon $du, Carbon $au, ?User $par = null): ClasseurXlsx
    {
        $du = $du->copy()->startOfDay(); $au = $au->copy()->startOfDay();
        $pointages = Pointage::whereBetween('date', [$du->toDateString(), $au->toDateString()])->get()
            ->groupBy(fn($p) => Carbon::parse($p->date)->toDateString() . '|' . $p->user_id);
        $userIds = $pointages->flatten()->pluck('user_id')->unique();
        $users = User::where(fn($q) => $q->where('actif', true)->whereNotIn('role', ['invite', 'administrateur', 'direction']))
            ->orWhereIn('id', $userIds)->orderBy('name')->orderBy('prenom')->get();

        // Jours retenus : jours ouvrés (dont jours exceptionnels déclarés) et tout jour pointé
        $jours = [];
        $limite = $au->copy()->min(today());
        for ($d = $du->copy(); $d->lte($limite); $d->addDay()) {
            $cle = $d->toDateString();
            if (CalendrierTravail::estJourOuvre($d) || $pointages->keys()->contains(fn($k) => str_starts_with($k, $cle . '|'))) $jours[] = $d->copy();
        }

        $periode = 'Du ' . $du->format('d/m/Y') . ' au ' . $au->format('d/m/Y');
        $edition = 'Édité le ' . now()->format('d/m/Y à H:i') . ($par ? ' par ' . $par->nomComplet() : '');
        $c = new ClasseurXlsx();
        $synthese = $c->feuille('Synthèse')->largeurs([30, 12, 12, 12, 12, 12, 12, 12, 14, 14]);
        $detail = $c->feuille('Détail')->largeurs([12, 11, 30, 16, 10, 10, 10, 15, 60]);

        // ---- Détail ----
        $detail->ligne(['SOUMAFÊ - REGISTRE DE POINTAGE'], X::TITRE)->fusionner(0, 8);
        $detail->ligne([$periode . ' · ' . $edition], X::SOUS_TITRE)->fusionner(0, 8)->vide();
        $detail->ligne(['Date', 'Jour', 'Collaborateur', 'Horaire attendu', 'Arrivée', 'Départ', 'Durée', 'Statut', 'Observations'], X::ENTETE);
        $cumul = [];
        foreach ($jours as $n => $d) {
            $cle = $d->toDateString();
            $attendu = CalendrierTravail::heureArrivee($d) . ' - ' . CalendrierTravail::heureDepart($d)
                . (CalendrierTravail::estJourExceptionnel($d) ? ' (except.)' : '');
            $ouvre = CalendrierTravail::estJourOuvre($d);
            foreach ($users as $u) {
                $p = $pointages->get($cle . '|' . $u->id)?->first();
                if (!$p && (!$ouvre || ($u->created_at && $u->created_at->gt($d->copy()->endOfDay())))) continue;
                $statut = $p?->statut ?? 'non_pointe';
                $lib = $p ? $p->statutLibelle() : 'Non pointé';
                $duree = $p?->dureeHeures();
                $obs = collect([
                    $p?->motif_retard ? 'Retard : ' . $p->motif_retard : null,
                    $p?->motif_depart ? 'Départ : ' . $p->motif_depart : null,
                    $p?->motif_absence ? 'Absence : ' . $p->motif_absence : null,
                    $p?->motif_correction ? 'Correction : ' . $p->motif_correction : null,
                ])->filter()->map(fn($t) => trim(strip_tags($t)))->join(' · ');
                $base = $n % 2 ? X::CELLULE_ALT : X::CELLULE;
                $etat = in_array($statut, ['retard', 'absent', 'non_pointe'], true) ? X::ALERTE : (in_array($statut, ['present'], true) ? X::OK : X::CENTRE);
                $detail->ligne([
                    ['v' => $d->format('d/m/Y'), 's' => $base], ['v' => ucfirst($d->translatedFormat('l')), 's' => $base],
                    ['v' => $u->nomComplet(), 's' => $base], ['v' => $attendu, 's' => $base],
                    ['v' => $p?->heure_arrivee ? substr($p->heure_arrivee, 0, 5) : '', 's' => $base],
                    ['v' => $p?->heure_depart ? substr($p->heure_depart, 0, 5) : '', 's' => $base],
                    ['v' => $duree !== null ? self::heures($duree) : '', 's' => $base],
                    ['v' => $lib, 's' => $etat], ['v' => $obs, 's' => $base],
                ]);
                $s = &$cumul[$u->id];
                $s ??= ['ouvres' => 0, 'present' => 0, 'retard' => 0, 'sorti_tot' => 0, 'absent' => 0, 'non_pointe' => 0, 'heures' => 0.0];
                if ($ouvre) $s['ouvres']++;
                $s[$statut] = ($s[$statut] ?? 0) + 1;
                $s['heures'] += $duree ?? 0;
                unset($s);
            }
        }
        if (!$jours) $detail->ligne(['Aucun jour ouvré sur la période.'])->fusionner(0, 8);

        // ---- Synthèse ----
        $synthese->ligne(['SOUMAFÊ - SYNTHÈSE DU POINTAGE'], X::TITRE)->fusionner(0, 9);
        $synthese->ligne([$periode . ' · ' . count(array_filter($jours, fn($d) => CalendrierTravail::estJourOuvre($d))) . ' jour(s) ouvré(s) · ' . $edition], X::SOUS_TITRE)->fusionner(0, 9);
        $regle = CalendrierTravail::regle($au);
        $synthese->ligne(['Horaires : ' . $regle->arrivee(1) . ' - ' . $regle->depart(1) . ' en semaine ; ' . implode(' - ', $regle->exceptionnel()) . ' le samedi et les jours exceptionnels travaillés (fériés déclarés ouvrables).'])->fusionner(0, 9)->vide();
        $synthese->ligne(['Collaborateur', 'Jours ouvrés', 'À l\'heure', 'Retards', 'Sorties anticipées', 'Absences', 'Non pointés', 'Présences', 'Heures travaillées', 'Ponctualité'], X::ENTETE);
        $tot = ['ouvres' => 0, 'present' => 0, 'retard' => 0, 'sorti_tot' => 0, 'absent' => 0, 'non_pointe' => 0, 'heures' => 0.0];
        $i = 0;
        foreach ($users as $u) {
            $s = $cumul[$u->id] ?? null;
            if (!$s) continue;
            $presences = $s['present'] + $s['retard'] + $s['sorti_tot'];
            $ponct = $s['ouvres'] ? round($s['present'] / $s['ouvres'] * 100) : null;
            $base = $i++ % 2 ? X::CELLULE_ALT : X::CELLULE;
            $synthese->ligne([
                ['v' => $u->nomComplet() . ($u->actif ? '' : ' (ancien)'), 's' => $base],
                ['v' => $s['ouvres'], 's' => X::CENTRE], ['v' => $s['present'], 's' => X::CENTRE],
                ['v' => $s['retard'], 's' => $s['retard'] ? X::ALERTE : X::CENTRE], ['v' => $s['sorti_tot'], 's' => X::CENTRE],
                ['v' => $s['absent'], 's' => $s['absent'] ? X::ALERTE : X::CENTRE], ['v' => $s['non_pointe'], 's' => $s['non_pointe'] ? X::ALERTE : X::CENTRE],
                ['v' => $presences, 's' => X::CENTRE], ['v' => self::heures($s['heures']), 's' => X::CENTRE],
                ['v' => $ponct !== null ? $ponct . ' %' : '-', 's' => $ponct === null ? X::CENTRE : ($ponct >= 90 ? X::OK : ($ponct < 75 ? X::ALERTE : X::CENTRE))],
            ]);
            foreach ($tot as $k => $v) $tot[$k] += $s[$k];
        }
        if ($i) {
            $p = $tot['ouvres'] ? round($tot['present'] / $tot['ouvres'] * 100) : null;
            $synthese->ligne(['Total', $tot['ouvres'], $tot['present'], $tot['retard'], $tot['sorti_tot'], $tot['absent'], $tot['non_pointe'],
                $tot['present'] + $tot['retard'] + $tot['sorti_tot'], self::heures($tot['heures']), $p !== null ? $p . ' %' : '-'], X::LIBELLE);
        } else {
            $synthese->ligne(['Aucun pointage sur la période.'])->fusionner(0, 9);
        }
        return $c;
    }

    /** 7.5 → « 7h30 » */
    private static function heures(float $h): string
    {
        $m = (int) round($h * 60);
        return intdiv($m, 60) . 'h' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT);
    }
}
