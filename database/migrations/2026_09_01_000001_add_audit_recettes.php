<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        // Colonnes verrouillage recette
        if (Schema::hasTable('recettes_vehicules')) {
            if (!Schema::hasColumn('recettes_vehicules', 'valide_par')) {
                DB::statement("ALTER TABLE recettes_vehicules ADD COLUMN valide_par VARCHAR(100) NULL AFTER controle_par");
            }
            if (!Schema::hasColumn('recettes_vehicules', 'valide_le')) {
                DB::statement("ALTER TABLE recettes_vehicules ADD COLUMN valide_le TIMESTAMP NULL AFTER valide_par");
            }
            if (!Schema::hasColumn('recettes_vehicules', 'motif_modification')) {
                DB::statement("ALTER TABLE recettes_vehicules ADD COLUMN motif_modification TEXT NULL AFTER valide_le");
            }
            if (!Schema::hasColumn('recettes_vehicules', 'modifie_par')) {
                DB::statement("ALTER TABLE recettes_vehicules ADD COLUMN modifie_par VARCHAR(100) NULL AFTER motif_modification");
            }
            if (!Schema::hasColumn('recettes_vehicules', 'modifie_le')) {
                DB::statement("ALTER TABLE recettes_vehicules ADD COLUMN modifie_le TIMESTAMP NULL AFTER modifie_par");
            }
        }
    }

    public function down(): void {}
};
