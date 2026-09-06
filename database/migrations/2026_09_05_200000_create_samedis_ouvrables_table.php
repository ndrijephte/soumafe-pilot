<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('samedis_ouvrables')) {
            DB::statement("CREATE TABLE samedis_ouvrables (
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
        Schema::dropIfExists('samedis_ouvrables');
    }
};
