<?php
namespace App\Console\Commands;

use App\Models\{Absence, ConfirmationTache, JourFerie, JourSaisie, NotificationApp, Tache, User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Schema};

class ControleSaisie extends Command
{
    protected $signature = 'soumafe:controle-saisie {--date= : Date à contrôler (défaut : hier)}';
    protected $description = 'Contrôle d\'assiduité quotidienne - vérifie si les tâches planifiées ont été confirmées';

    public function handle(): int
    {
        $date = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : today()->subDay();

        if ($date->isWeekend()) { $this->info("$date est un week-end - pas de contrôle."); return 0; }
        if (JourFerie::whereDate('date', $date)->exists()) { $this->info("$date est un jour férié - pas de contrôle."); return 0; }

        $users = User::where('actif', true)
            ->whereNotIn('role', ['direction', 'invite'])
            ->get();

        $manquants = 0;

        foreach ($users as $u) {
            // Déjà traité ?
            if (JourSaisie::where('user_id', $u->id)->whereDate('date', $date)->exists()) continue;

            // Absence déclarée → jour automatiquement justifié
            if (Schema::hasTable('absences') && Absence::pourDate($u->id, $date)->exists()) {
                $abs = Absence::pourDate($u->id, $date)->first();
                JourSaisie::create([
                    'user_id' => $u->id,
                    'date' => $date,
                    'statut' => 'justifie',
                ]);
                continue;
            }

            // Tâches planifiées ce jour-là (même logique que la page saisie)
            $tachesPlanifiees = Tache::where('user_id', $u->id)
                ->whereDate('date_debut', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('echeance')->orWhereDate('echeance', '>=', $date);
                })
                ->whereIn('statut', ['a_faire', 'en_cours', 'bloque', 'termine'])
                ->pluck('id');

            if ($tachesPlanifiees->isEmpty()) {
                // Aucune tâche planifiée ce jour → jour saisi par défaut (pas d'obligation)
                JourSaisie::create([
                    'user_id' => $u->id,
                    'date' => $date,
                    'statut' => 'saisi',
                ]);
                continue;
            }

            // Vérifier si le collaborateur a confirmé/infirmé au moins une tâche ce jour
            $nbConfirmations = 0;
            $nbNonConfirmees = 0;

            if (Schema::hasTable('confirmation_taches')) {
                $confirmees = ConfirmationTache::where('user_id', $u->id)
                    ->whereDate('date', $date)
                    ->pluck('tache_id');

                $nbConfirmations = $confirmees->count();
                $nbNonConfirmees = $tachesPlanifiees->diff($confirmees)->count();
            } else {
                $nbNonConfirmees = $tachesPlanifiees->count();
            }

            if ($nbNonConfirmees > 0) {
                // Des tâches planifiées n'ont pas été confirmées → à justifier
                JourSaisie::create([
                    'user_id' => $u->id,
                    'date' => $date,
                    'statut' => 'a_justifier',
                ]);

                NotificationApp::create([
                    'user_id' => $u->id,
                    'type' => 'saisie_manquante',
                    'contenu' => "{$nbNonConfirmees} tâche(s) planifiée(s) non confirmée(s) le {$date->format('d/m/Y')}. Merci de justifier.",
                    'lu' => false,
                ]);
                $manquants++;
            } else {
                // Toutes les tâches planifiées ont été confirmées/infirmées
                JourSaisie::create([
                    'user_id' => $u->id,
                    'date' => $date,
                    'statut' => 'saisi',
                ]);
            }
        }

        $this->info("Contrôle du {$date->format('d/m/Y')} : {$manquants} collaborateur(s) avec tâches non confirmées.");
        return 0;
    }
}
