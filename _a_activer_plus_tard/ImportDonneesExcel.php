<?php
namespace App\Console\Commands;

use App\Models\{Pole, Projet, User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import initial depuis les fichiers Excel de pilotage existants.
 * php artisan soumafe:import chemin/peronnel_soumafe2.xlsx --projets=chemin/listes.xlsx
 */
class ImportDonneesExcel extends Command
{
    protected $signature = 'soumafe:import {personnel} {--projets=}';
    protected $description = 'Importe collaborateurs (et projets) depuis les classeurs Excel actuels';

    public function handle(): int
    {
        $anomalies = [];
        $lignes = Excel::toCollection(null, $this->argument('personnel'))->first();
        $entetes = $lignes->first()->map(fn ($v) => mb_strtolower(trim((string) $v)));
        $iNom = $entetes->search(fn ($v) => str_contains($v, 'nom'));
        $iMail = $entetes->search(fn ($v) => str_contains($v, 'mail'));
        $iPole = $entetes->search(fn ($v) => str_contains($v, 'pole') || str_contains($v, 'pôle') || str_contains($v, 'departement'));

        if ($iNom === false) { $this->error('Colonne nom introuvable.'); return self::FAILURE; }

        foreach ($lignes->skip(1) as $n => $l) {
            $nom = trim((string) $l[$iNom]);
            if ($nom === '') continue;
            $email = $iMail !== false && filter_var(trim((string) $l[$iMail]), FILTER_VALIDATE_EMAIL)
                ? trim((string) $l[$iMail])
                : mb_strtolower(str_replace(' ', '.', $nom)) . '@soumafe.ci';
            if ($iMail === false || !filter_var(trim((string) ($l[$iMail] ?? '')), FILTER_VALIDATE_EMAIL)) {
                $anomalies[] = "Ligne " . ($n + 1) . " : e-mail absent/invalide pour {$nom} → {$email}";
            }
            $pole = $iPole !== false && trim((string) $l[$iPole]) !== ''
                ? Pole::firstOrCreate(['libelle' => trim((string) $l[$iPole])])->id : null;

            User::firstOrCreate(['email' => $email], [
                'name' => $nom, 'password' => Hash::make('ChangezMoi!2026'),
                'pole_id' => $pole, 'profil_mesure' => 'support',
            ])->syncRoles(['collaborateur']);
        }
        $this->info(User::count() . ' utilisateurs en base.');

        if ($f = $this->option('projets')) {
            foreach (Excel::toCollection(null, $f)->first()->skip(1) as $l) {
                $lib = trim((string) $l[0]);
                if ($lib === '') continue;
                Projet::firstOrCreate(['code' => mb_strtoupper(mb_substr(preg_replace('/[^A-Za-z]/', '', $lib), 0, 6))],
                    ['libelle' => $lib, 'statut' => 'en_cours']);
            }
            $this->info(Projet::count() . ' projets en base.');
        }

        foreach ($anomalies as $a) $this->warn($a);
        $this->info('Import terminé — ' . count($anomalies) . ' anomalie(s) à vérifier.');
        return self::SUCCESS;
    }
}
