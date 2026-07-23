<?php
namespace Database\Seeders;

use App\Models\Pole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EquipeSeeder extends Seeder
{
    /** Comptes initiaux — mots de passe à changer à la première connexion. */
    public function run(): void
    {
        $dev = Pole::where('libelle', 'Developpement')->first()?->id;
        $ops = Pole::where('libelle', 'Conseil, Strategie et Operations')->first()?->id;

        $membres = [
            ['SOUMAFE',   'DG',            'dg@soumafe.ci',             'direction',           'support',     $ops],
            ['KOFFI',     'N\'dri Jephté', 'ndrijephtekoffi@gmail.com', 'charge_operations', 'support',     $ops],
            ['YAPO',      'Rodrigue',      'yapo@soumafe.ci',           'collaborateur',    'developpeur', $dev],
            ['KOUDJODJI', 'Koffi',         'koudjodji@soumafe.ci',      'collaborateur',    'developpeur', $dev],
            ['OUATTARA',  'Junior',        'ouattara@soumafe.ci',       'collaborateur',    'developpeur', $dev],
            ['ZRAN',      'Séverin',       'zran@soumafe.ci',           'collaborateur',  'developpeur', $dev],
            ['KANDAN',    'Aimé',          'kandan@soumafe.ci',         'collaborateur',  'developpeur', $dev],
        ];
        foreach ($membres as [$nom, $prenom, $email, $role, $profil, $pole]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $nom, 'prenom' => $prenom,
                'password' => Hash::make('ChangezMoi!2026'),
                'role' => $role, 'profil_mesure' => $profil, 'pole_id' => $pole,
            ]);
        }
    }
}
