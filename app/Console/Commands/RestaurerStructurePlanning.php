<?php
namespace App\Console\Commands;

use App\Models\Projet;
use App\Services\RestaurationStructure;
use Illuminate\Console\Command;

/**
 * Restaure la structure du Sprint Planning (fonctionnalité → user story → tâches) d'après le classeur Scrum consolidé,
 * même pour les sprints clôturés, sans toucher à l'avancement des tâches ni au score (story points).
 */
class RestaurerStructurePlanning extends Command
{
    protected $signature = 'scrum:restaurer-structure
        {projet : Code du projet (ex. CROU)}
        {fichier : Chemin du classeur Scrum consolidé (.xlsx)}
        {--sprints= : Numéros des sprints, séparés par des virgules (par défaut : tous)}
        {--appliquer : Enregistre la restauration (sinon simulation, rien n\'est modifié)}';
    protected $description = 'Restaure fonctionnalités et user stories des tâches du Sprint Planning d\'après le classeur, sans toucher à l\'avancement';

    public function handle(): int
    {
        $appliquer = (bool) $this->option('appliquer');
        $this->line($appliquer ? '<fg=yellow>RESTAURATION</> (les rattachements seront enregistrés)' : '<fg=cyan>SIMULATION</> (rien n\'est enregistré ; relancez avec --appliquer)');
        $projet = Projet::where('code', $this->argument('projet'))->first();
        if (!$projet) { $this->error("Projet « {$this->argument('projet')} » introuvable."); return self::FAILURE; }
        if (!is_file($this->argument('fichier'))) { $this->error('Classeur introuvable : ' . $this->argument('fichier')); return self::FAILURE; }
        $numeros = $this->option('sprints') ? array_map('intval', explode(',', $this->option('sprints'))) : null;

        $r = RestaurationStructure::executer($projet, $this->argument('fichier'), $numeros, $appliquer);
        if (!$r['lignes']) { $this->info('Rien à restaurer : les tâches ont déjà leur user story et leur fonctionnalité.'); return self::SUCCESS; }
        $this->table(['Sprint', 'État', 'Tâches -> user story', 'Tâches -> fonctionnalité', 'Lignes du classeur absentes', 'Avancement et points'], $r['lignes']);
        if ($r['controle']) { $this->error('Avancement ou points modifiés pour : ' . implode(', ', $r['controle']) . '. Rien n\'a été enregistré.'); return self::FAILURE; }
        if (!$appliquer) { $this->line('<fg=cyan>SIMULATION terminée :</> relancez avec --appliquer.'); return self::SUCCESS; }
        $this->info("Structure restaurée : {$r['total']['stories']} rattachement(s) à une user story, {$r['total']['fonctionnalites']} à une fonctionnalité.");
        return self::SUCCESS;
    }
}
