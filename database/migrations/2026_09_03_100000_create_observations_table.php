<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE TABLE IF NOT EXISTS observations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(20) NOT NULL,
            date_reception DATE NOT NULL,
            description TEXT NOT NULL,
            type_difficulte VARCHAR(100) NOT NULL DEFAULT 'Technique / bug',
            priorite VARCHAR(30) NOT NULL DEFAULT 'Moyenne',
            responsable_id BIGINT UNSIGNED NULL,
            statut VARCHAR(30) NOT NULL DEFAULT 'nouveau',
            origine VARCHAR(100) NOT NULL DEFAULT 'Côté utilisateur',
            commentaires TEXT NULL,
            mode_execution VARCHAR(100) NULL,
            date_livraison DATE NULL,
            commentaire_resolution TEXT NULL,
            resolu_par BIGINT UNSIGNED NULL,
            resolu_le DATETIME NULL,
            declare_par BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            FOREIGN KEY (responsable_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (resolu_par) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (declare_par) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
