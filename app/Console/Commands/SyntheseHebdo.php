<?php
namespace App\Console\Commands;

use App\Models\{NotificationApp, Tache, User};
use Illuminate\Console\Command;

class SyntheseHebdo extends Command
{
    protected $signature = 'soumafe:synthese-hebdo';
    protected $description = 'Envoie une synthèse hebdomadaire à chaque collaborateur actif';

    public function handle(): int
    {
        $debut = today()->startOfWeek();
        $fin = today()->endOfWeek();

        $users = User::where('actif', true)->get();
        foreach ($users as $u) {
            $terminees = Tache::where('user_id', $u->id)->where('statut', 'termine')
                ->whereBetween('date_fin_reelle', [$debut, $fin])->count();
            $enCours = Tache::where('user_id', $u->id)->where('statut', 'en_cours')->count();
            $bloquees = Tache::where('user_id', $u->id)->where('statut', 'bloque')->count();
            $enRetard = Tache::where('user_id', $u->id)->enRetard()->count();

            NotificationApp::create([
                'user_id' => $u->id,
                'type' => 'synthese_hebdo',
                'contenu' => "Bilan semaine : {$terminees} terminée(s), {$enCours} en cours, {$bloquees} bloquée(s), {$enRetard} en retard.",
            ]);
        }

        $this->info("Synthèse envoyée à {$users->count()} collaborateur(s).");
        return 0;
    }
}
