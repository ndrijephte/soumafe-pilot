<?php
namespace App\Console\Commands;

use App\Models\{AuditLog, Projet, Sprint};
use App\Services\RepartitionPoints;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Répartit les story points sur les tâches de tous les sprints (historique compris) : la somme des points des tâches
 * d'une story, dans un sprint, devient égale aux points de la story (figés à la clôture pour un sprint clôturé),
 * au prorata de la durée des tâches. Les sprints ouverts sont tenus à jour automatiquement ; cette commande aligne
 * l'existant. Sans --appliquer, simple simulation.
 */
class RepartirPointsTaches extends Command
{
    protected $signature = 'scrum:repartir-points
        {projet? : Code du projet (par défaut : tous les projets)}
        {--sprint= : Numéro d\'un sprint du projet}
        {--appliquer : Enregistre la répartition (sinon simulation, rien n\'est modifié)}';
    protected $description = 'Répartit les story points sur les tâches de chaque story (prorata de la durée), historique compris';

    public function handle(): int
    {
        $appliquer = (bool) $this->option('appliquer');
        $this->line($appliquer ? '<fg=yellow>RÉPARTITION</> (les points des tâches seront enregistrés)' : '<fg=cyan>SIMULATION</> (rien n\'est enregistré ; relancez avec --appliquer)');
        $q = Sprint::with('projet:id,code,libelle')->orderBy('projet_id')->orderBy('numero');
        if ($code = $this->argument('projet')) {
            $projet = Projet::where('code', $code)->first();
            if (!$projet) { $this->error("Projet « {$code} » introuvable. Codes : " . Projet::pluck('code')->join(', ')); return self::FAILURE; }
            $q->where('projet_id', $projet->id);
            if ($this->option('sprint')) $q->where('numero', (int) $this->option('sprint'));
        }

        $lignes = [];
        $total = 0;
        DB::beginTransaction();
        try {
            foreach ($q->get() as $sp) {
                $avant = (int) $sp->taches()->whereNotNull('user_story_id')->where('statut', '!=', 'reporte')->sum('points');
                $changes = RepartitionPoints::sprint($sp);
                if (!$changes) continue;
                $apres = (int) $sp->taches()->whereNotNull('user_story_id')->where('statut', '!=', 'reporte')->sum('points');
                $total += count($changes);
                $lignes[] = [$sp->projet->code, "Sprint {$sp->numero}", $sp->statut === 'termine' ? 'clôturé' : $sp->statut, count($changes), "{$avant} -> {$apres}", $sp->pointsEngages()];
            }
            if (!$lignes) { DB::rollBack(); $this->info('Rien à répartir : les points des tâches correspondent déjà à ceux des stories.'); return self::SUCCESS; }
            $this->table(['Projet', 'Sprint', 'État', 'Tâches modifiées', 'Σ points des tâches de stories', 'Points engagés (stories)'], $lignes);
            if (!$appliquer) { DB::rollBack(); $this->line("<fg=cyan>SIMULATION terminée :</> {$total} tâche(s) à mettre à jour. Relancez avec --appliquer."); return self::SUCCESS; }
            AuditLog::log('repartition_points', 'taches', $this->argument('projet') ?: 'Tous projets',
                "Story points répartis sur les tâches (prorata de la durée) : {$total} tâche(s) mises à jour dans " . count($lignes) . ' sprint(s).');
            DB::commit();
            $this->info("Répartition enregistrée : {$total} tâche(s) mises à jour.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Erreur, rien n\'a été modifié : ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
