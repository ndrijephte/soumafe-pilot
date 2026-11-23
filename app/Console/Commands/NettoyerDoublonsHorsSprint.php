<?php
namespace App\Console\Commands;

use App\Models\{AuditLog, Projet, Sprint, Tache};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Nettoie les tâches importées hors sprint qui doublonnent les tâches d'un sprint.
 *
 * Cas type : un Sprint Planning importé d'abord sans sprint (tâches isolées, souvent sans projet), puis à nouveau
 * dans le sprint. Chaque tâche isolée est appariée à son double du sprint (même collaborateur, même intitulé, à la
 * casse et aux espaces près). Avant le retrait (suppression douce), ce qui a pu être fait sur la tâche isolée est
 * reporté sur son double : commentaires, blocages, confirmations, objectifs, arbitrages, et le statut s'il est plus
 * avancé. Une tâche isolée sans double dans le sprint n'est pas touchée.
 *
 * Seul un lot d'import est traité (tâches créées à la même minute) : une tâche personnelle de même intitulé
 * (« Réunion »…) saisie à un autre moment reste en place. Sans --appliquer, simple simulation.
 */
class NettoyerDoublonsHorsSprint extends Command
{
    protected $signature = 'taches:nettoyer-doublons-hors-sprint
        {projet : Code du projet (ex. BTP)}
        {sprint : Numéro du sprint (ex. 5)}
        {--lot= : Minute de création du lot importé hors sprint, « AAAA-MM-JJ HH:MM » (obligatoire s\'il y a plusieurs lots)}
        {--appliquer : Enregistre le nettoyage (sinon simulation, rien n\'est modifié)}';
    protected $description = 'Retire les tâches importées hors sprint qui doublonnent celles d\'un sprint (activité reportée sur le double)';

    private const RANG = ['a_faire' => 0, 'reporte' => 0, 'en_cours' => 1, 'bloque' => 1, 'a_valider' => 2, 'termine' => 3];

    public function handle(): int
    {
        $appliquer = (bool) $this->option('appliquer');
        $this->line($appliquer ? '<fg=yellow>NETTOYAGE</> (les modifications seront enregistrées)' : '<fg=cyan>SIMULATION</> (rien n\'est enregistré ; relancez avec --appliquer)');

        $projet = Projet::where('code', $this->argument('projet'))->first();
        if (!$projet) return $this->stop("Projet de code « {$this->argument('projet')} » introuvable. Codes : " . Projet::pluck('code')->join(', '));
        $sprint = Sprint::where('projet_id', $projet->id)->where('numero', (int) $this->argument('sprint'))->first();
        if (!$sprint) return $this->stop("Sprint {$this->argument('sprint')} introuvable dans {$projet->libelle}.");
        $this->info("{$projet->libelle} — Sprint {$sprint->numero} ({$sprint->statut}) : " . $sprint->taches()->count() . ' tâche(s)');

        $cle = fn($t) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $t->intitule))) . '|' . $t->user_id;
        $duSprint = $sprint->taches()->orderBy('id')->get()->groupBy($cle);

        // Tâches isolées (hors sprint, sans projet ou du projet) ayant un double dans le sprint
        $isolees = Tache::whereNull('sprint_id')->where(fn($q) => $q->whereNull('projet_id')->orWhere('projet_id', $projet->id))
            ->whereIn('user_id', $sprint->taches()->distinct()->pluck('user_id'))->orderBy('id')->get()
            ->filter(fn($t) => $duSprint->has($cle($t)));
        if ($isolees->isEmpty()) { $this->info('Aucune tâche hors sprint ne doublonne ce sprint : rien à nettoyer.'); return self::SUCCESS; }

        // Lots d'import (minute de création)
        $lots = $isolees->groupBy(fn($t) => $t->created_at->format('Y-m-d H:i'));
        $this->table(['Lot (création)', 'Tâches en double', 'Exemple'], $lots->map(fn($g, $m) => [$m, $g->count(), mb_strimwidth($g->first()->intitule, 0, 60, '…')])->values()->all());
        $lot = $this->option('lot');
        if ($lot) {
            if (!$lots->has($lot)) return $this->stop("Lot « {$lot} » introuvable parmi : " . $lots->keys()->join(', '));
            $isolees = $lots[$lot];
        } elseif ($lots->count() > 1) {
            return $this->stop('Plusieurs lots : précisez celui à nettoyer avec --lot="AAAA-MM-JJ HH:MM" (les autres peuvent être des tâches personnelles légitimes).');
        }

        // Appariement un à un (une tâche isolée par double du sprint)
        $paires = [];
        $sansDouble = 0;
        foreach ($isolees->groupBy($cle) as $k => $groupe) {
            $doubles = $duSprint[$k]->values();
            foreach ($groupe->values() as $i => $t) {
                if (!isset($doubles[$i])) { $sansDouble++; continue; }
                $paires[] = [$t, $doubles[$i]];
            }
        }

        $refs = [
            'commentaires' => ['commentaires', 'tache_id'],
            'blocages' => ['blocages', 'tache_id'],
            'blocages liés' => ['blocages', 'tache_liee_id'],
            'confirmations' => ['confirmations_taches', 'tache_id'],
            'objectifs' => ['objectif_tache', 'tache_id'],
            'arbitrages' => ['arbitrages_t3', 'tache_id'],
        ];
        $bilan = array_fill_keys(array_keys($refs), 0) + ['statuts repris' => 0, 'retirées' => 0];
        $details = [];

        DB::beginTransaction();
        try {
            foreach ($paires as [$t, $double]) {
                $activite = [];
                foreach ($refs as $lib => [$table, $col]) {
                    $q = DB::table($table)->where($col, $t->id);
                    if ($table === 'objectif_tache') {
                        // Un objectif déjà lié au double n'est pas dupliqué
                        $deja = DB::table('objectif_tache')->where('tache_id', $double->id)->pluck('objectif_id');
                        DB::table('objectif_tache')->where('tache_id', $t->id)->whereIn('objectif_id', $deja)->delete();
                    }
                    $n = $q->update([$col => $double->id]);
                    if ($n) { $bilan[$lib] += $n; $activite[] = "{$n} {$lib}"; }
                }
                // Statut plus avancé sur la tâche isolée : repris par le double (sauf sprint clôturé, qui reste tel quel)
                if ($sprint->statut !== 'termine' && (self::RANG[$t->statut] ?? 0) > (self::RANG[$double->statut] ?? 0)) {
                    $double->update(array_filter([
                        'statut' => $t->statut,
                        'date_fin_reelle' => $t->statut === 'termine' ? ($t->date_fin_reelle ?? $double->date_fin_reelle ?? today()) : null,
                        'teste' => $t->teste ?: null,
                    ], fn($v) => $v !== null));
                    $bilan['statuts repris']++;
                    $activite[] = "statut {$t->statut} repris";
                }
                $t->delete();
                $bilan['retirées']++;
                if ($activite) $details[] = ["#{$t->id} -> #{$double->id}", mb_strimwidth($t->intitule, 0, 50, '…'), implode(', ', $activite)];
            }
            if ($sprint->statut !== 'termine') {
                foreach ($sprint->taches()->whereNotNull('user_story_id')->distinct()->pluck('user_story_id') as $us) \App\Services\AvancementScrum::synchroniser($us);
            }

            if ($details) { $this->newLine(); $this->line('Activité reportée sur le double du sprint :'); $this->table(['Isolée -> sprint', 'Tâche', 'Reporté'], $details); }
            $this->newLine();
            $this->table(['Opération', 'Nombre'], collect($bilan)->map(fn($n, $k) => [$k, $n])->values()->push(['isolées sans double (laissées)', $sansDouble])->all());
            $this->line('Tâches du sprint : ' . $sprint->taches()->count() . ' (inchangé) ; tâches hors sprint restantes du lot : ' . ($sansDouble));

            if (!$appliquer) {
                DB::rollBack();
                $this->line('<fg=cyan>SIMULATION terminée : rien n\'a été enregistré.</> Si le résultat est celui attendu, relancez avec --appliquer' . ($lot ? " --lot=\"{$lot}\"" : '') . '.');
                return self::SUCCESS;
            }
            $ids = collect($paires)->map(fn($p) => $p[0]->id);
            AuditLog::log('nettoyage_import', 'taches', $projet->code,
                "Sprint {$sprint->numero} : {$bilan['retirées']} tâche(s) importée(s) hors sprint en double retirée(s) (suppression douce, ids {$ids->min()}-{$ids->max()}, lot " . ($lot ?? $lots->keys()->first()) . ') ; '
                . collect($bilan)->except('retirées')->filter()->map(fn($n, $k) => "{$n} {$k}")->join(', ') . ' reporté(s) sur les tâches du sprint.');
            DB::commit();
            $this->info('Nettoyage enregistré (journal d\'audit : « nettoyage_import »).');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->stop('Erreur, rien n\'a été modifié : ' . $e->getMessage());
        }
    }

    private function stop(string $message): int
    {
        $this->error($message);
        return self::FAILURE;
    }
}
