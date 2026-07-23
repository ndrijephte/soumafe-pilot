<?php
namespace Database\Seeders;

use App\Models\JourFerie;
use Illuminate\Database\Seeder;

class JoursFeriesSeeder extends Seeder
{
    /** Jours fériés Côte d'Ivoire 2026. Les fêtes religieuses mobiles (*) sont à
     *  confirmer par décret — ajustables dans Administration > Jours fériés. */
    public function run(): void
    {
        foreach ([
            ['2026-01-01', 'Jour de l\'An'],
            ['2026-03-20', 'Lendemain de la Nuit du Destin (Laylat al-Qadr) *'],
            ['2026-03-21', 'Fête de fin du Ramadan (Aïd el-Fitr) *'],
            ['2026-04-06', 'Lundi de Pâques'],
            ['2026-05-01', 'Fête du Travail'],
            ['2026-05-14', 'Ascension'],
            ['2026-05-25', 'Lundi de Pentecôte'],
            ['2026-05-27', 'Fête de la Tabaski (Aïd el-Kebir) *'],
            ['2026-08-07', 'Fête de l\'Indépendance'],
            ['2026-08-15', 'Assomption'],
            ['2026-08-26', 'Lendemain du Maouloud *'],
            ['2026-11-01', 'Toussaint'],
            ['2026-11-15', 'Journée Nationale de la Paix'],
            ['2026-12-25', 'Noël'],
        ] as [$date, $libelle]) {
            JourFerie::firstOrCreate(['date' => $date], ['libelle' => $libelle, 'annee' => 2026]);
        }
    }
}
