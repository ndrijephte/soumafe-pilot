<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        // Si l'ancienne table existe, la renommer
        if (Schema::hasTable('samedis_ouvrables') && !Schema::hasTable('jours_ouvrables_speciaux')) {
            DB::statement("RENAME TABLE samedis_ouvrables TO jours_ouvrables_speciaux");
        }

        // Si aucune table n'existe, la créer
        if (!Schema::hasTable('jours_ouvrables_speciaux')) {
            DB::statement("CREATE TABLE jours_ouvrables_speciaux (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                date DATE NOT NULL UNIQUE,
                motif VARCHAR(255) NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('jours_ouvrables_speciaux') && !Schema::hasTable('samedis_ouvrables')) {
            DB::statement("RENAME TABLE jours_ouvrables_speciaux TO samedis_ouvrables");
        }
    }
};
