<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('types_parc') && !Schema::hasColumn('types_parc', 'intervalle_jours')) {
            DB::statement("ALTER TABLE types_parc ADD COLUMN intervalle_jours INT UNSIGNED NULL AFTER libelle");

            // Intervalles par défaut pour les types de maintenance existants
            $intervalles = [
                'vidange'             => 90,
                'assurance'           => 365,
                'visite_technique'    => 365,
                'patente'             => 365,
                'carte_stationnement' => 365,
                'autre'               => 180,
            ];

            foreach ($intervalles as $code => $jours) {
                DB::table('types_parc')
                    ->where('categorie', 'maintenance')
                    ->where('code', $code)
                    ->update(['intervalle_jours' => $jours]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('types_parc', 'intervalle_jours')) {
            DB::statement("ALTER TABLE types_parc DROP COLUMN intervalle_jours");
        }
    }
};
