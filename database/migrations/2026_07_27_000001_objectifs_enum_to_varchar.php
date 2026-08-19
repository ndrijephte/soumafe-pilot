<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('objectifs')) return;

        // Convertir source ENUM → VARCHAR(50)
        if (Schema::hasColumn('objectifs', 'source')) {
            DB::statement("ALTER TABLE objectifs MODIFY COLUMN source VARCHAR(50) DEFAULT 'personnel'");
        }

        // Convertir statut_validation ENUM → VARCHAR(30)
        if (Schema::hasColumn('objectifs', 'statut_validation')) {
            DB::statement("ALTER TABLE objectifs MODIFY COLUMN statut_validation VARCHAR(30) DEFAULT 'propose'");
        }
    }

    public function down(): void
    {
        // Pas de rollback pour les ENUM — les données pourraient ne plus correspondre
    }
};
