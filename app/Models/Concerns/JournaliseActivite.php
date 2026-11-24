<?php
namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Services\JournalActivite;

/**
 * Trace automatiquement, dans le journal d'audit, les actions d'un utilisateur connecté sur le modèle :
 * création, changements des champs suivis (statut, attribution, échéance…) et suppression.
 * Le modèle définit journalModule(), journalCible() et journalChamps().
 * Les traitements de masse (imports) passent par JournalActivite::sans() pour ne pas inonder le journal.
 */
trait JournaliseActivite
{
    protected static function bootJournaliseActivite(): void
    {
        static::created(fn($m) => $m->tracerActivite('creation'));
        static::updated(function ($m) {
            $champs = array_values(array_intersect(array_keys($m->getChanges()), $m->journalChamps()));
            if (!$champs) return;
            $details = [];
            foreach ($champs as $c) $details[] = $m->journalLibelleChamp($c) . ' : ' . $m->journalValeur($c, $m->getOriginal($c)) . ' → ' . $m->journalValeur($c, $m->getAttribute($c));
            $action = match (true) {
                $champs === ['statut'] => 'changement_statut',
                $champs === ['user_id'] => 'attribution',
                default => 'modification',
            };
            $m->tracerActivite($action, implode(' ; ', $details));
        });
        static::deleted(fn($m) => $m->tracerActivite('suppression'));
    }

    protected function tracerActivite(string $action, ?string $details = null): void
    {
        if (!JournalActivite::actif()) return;
        try {
            AuditLog::log($action, $this->journalModule(), $this->journalCible(), $details);
        } catch (\Throwable) {
            // le journal ne doit jamais bloquer l'action elle-même
        }
    }

    /** Libellé lisible d'un champ suivi. */
    public function journalLibelleChamp(string $champ): string
    {
        return JournalActivite::CHAMPS[$champ] ?? ucfirst(str_replace('_', ' ', $champ));
    }

    /** Valeur lisible d'un champ suivi (dates, personnes, statuts). */
    public function journalValeur(string $champ, $valeur): string
    {
        if ($valeur === null || $valeur === '') return '—';
        if ($valeur instanceof \DateTimeInterface) return $valeur->format('d/m/Y');
        if ($champ === 'statut' && method_exists($this, 'journalStatut')) return $this->journalStatut((string) $valeur);
        if (in_array($champ, ['user_id', 'intervenant_id'], true)) return \App\Models\User::find($valeur)?->nomComplet() ?? "#{$valeur}";
        if (is_bool($valeur)) return $valeur ? 'oui' : 'non';
        if (preg_match('/^\d{4}-\d{2}-\d{2}( 00:00:00)?$/', (string) $valeur)) return \Carbon\Carbon::parse($valeur)->format('d/m/Y');
        return mb_strimwidth((string) $valeur, 0, 80, '…');
    }
}
