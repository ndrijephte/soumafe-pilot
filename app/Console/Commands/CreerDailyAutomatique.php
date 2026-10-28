<?php
namespace App\Console\Commands;

use App\Models\{AuditLog, Sprint};
use App\Services\ScrumService;
use Illuminate\Console\Command;

/** Crée le Daily Scrum du jour des sprints en cours dont le projet a activé la création automatique. */
class CreerDailyAutomatique extends Command
{
    protected $signature = 'scrum:daily-auto';
    protected $description = 'Crée et pré-remplit le Daily Scrum du jour selon la programmation des projets';

    public function handle(): int
    {
        $n = 0;
        $sprints = Sprint::with('projet')->where('statut', 'en_cours')
            ->whereDate('date_debut', '<=', today())->whereDate('date_fin', '>=', today())->get();
        foreach ($sprints as $sprint) {
            if ($d = ScrumService::creationAutomatique($sprint)) {
                AuditLog::log('creation_daily', 'sprint', $sprint->projet->code . ' S' . $sprint->numero, $d->libelle() . ' (automatique)');
                $this->line("{$sprint->projet->libelle} - Sprint {$sprint->numero} : {$d->libelle()}");
                $n++;
            }
        }
        $this->info("{$n} Daily créé(s).");
        return self::SUCCESS;
    }
}
