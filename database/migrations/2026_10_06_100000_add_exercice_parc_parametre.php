<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('parametres_parc')) return;
        if (DB::table('parametres_parc')->where('cle', 'exercice_regles')->exists()) return;

        // Règles « exercice de départ:jour de début », séparées par « ; »
        DB::table('parametres_parc')->insert([
            'cle' => 'exercice_regles', 'valeur' => '2000-01:5',
            'libelle' => "Jour de début de l'exercice mensuel (historique des règles)",
            'groupe' => 'exercice', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('parametres_parc')->where('cle', 'exercice_regles')->delete();
    }
};
