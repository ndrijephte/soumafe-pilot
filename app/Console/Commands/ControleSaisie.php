<?php
namespace App\Console\Commands;

use App\Models\{JourFerie, JourSaisie, NotificationApp, Tache, User};
use Illuminate\Console\Command;

class ControleSaisie extends Command
{
    protected $signature = 'soumafe:controle-saisie {--date= : Date à contrôler (défaut : hier)}';
    protected $description = 'Contrôle de saisie quotidienne — marque les jours non saisis et notifie';

    public function handle(): int
    {
        $date = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : today()->subDay();

        // Pas de contrôle week-end ni férié
        if ($date->isWeekend()) { $this->info("$date est un week-end — pas de contrôle."); return 0; }
        if (JourFerie::whereDate('date', $date)->exists()) { $this->info("$date est un jour férié — pas de contrôle."); return 0; }

        $users = User::where('actif', true)->get();
        $manquants = 0;

        foreach ($users as $u) {
            // Déjà traité ?
            if (JourSaisie::where('user_id', $u->id)->whereDate('date', $date)->exists()) continue;

            // A saisi au moins une tâche ce jour-là ?
            $aSaisi = Tache::where('user_id', $u->id)->whereDate('date_debut', $date)->exists();

            JourSaisie::create([
                'user_id' => $u->id,
                'date' => $date,
                'statut' => $aSaisi ? 'saisi' : 'a_justifier',
            ]);

            if (!$aSaisi) {
                NotificationApp::create([
                    'user_id' => $u->id,
                    'type' => 'saisie_manquante',
                    'contenu' => "Aucune tâche saisie le {$date->format('d/m/Y')}. Merci d'ajouter une justification.",
                ]);
                $manquants++;
            }
        }

        $this->info("Contrôle du {$date->format('d/m/Y')} : {$manquants} collaborateur(s) sans saisie.");
        return 0;
    }
}
