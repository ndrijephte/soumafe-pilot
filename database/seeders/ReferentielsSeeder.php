<?php
namespace Database\Seeders;

use App\Models\Pole;
use App\Models\Projet;
use Illuminate\Database\Seeder;

class ReferentielsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Direction Generale', 'Conseil, Strategie et Operations', 'Developpement', 'Administration'] as $p) {
            Pole::firstOrCreate(['libelle' => $p]);
        }
        // Projets actuels (feuilles LISTES des classeurs de pilotage)
        foreach ([
            ['code' => 'AGAT',  'libelle' => 'AGAT',                        'statut' => 'livre'],
            ['code' => 'CROU',  'libelle' => 'CROU Connect',                'statut' => 'en_cours'],
            ['code' => 'GCA',   'libelle' => 'GCA',                         'statut' => 'en_cours'],
            ['code' => 'FLOTTE','libelle' => 'Gestion Logistique (Flotte)', 'statut' => 'en_cours'],
            ['code' => 'EBAC',  'libelle' => 'EBAC',                        'statut' => 'cadrage'],
            ['code' => 'PILOT', 'libelle' => 'SOUMAFE PILOT',               'statut' => 'en_cours'],
        ] as $p) {
            Projet::firstOrCreate(['code' => $p['code']], $p);
        }
    }
}
