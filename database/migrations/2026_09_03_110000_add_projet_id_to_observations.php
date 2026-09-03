<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('observations') && !Schema::hasColumn('observations', 'projet_id')) {
            DB::statement("ALTER TABLE observations ADD COLUMN projet_id BIGINT UNSIGNED NULL AFTER code");
            DB::statement("ALTER TABLE observations ADD CONSTRAINT fk_obs_projet FOREIGN KEY (projet_id) REFERENCES projets(id) ON DELETE SET NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('observations', 'projet_id')) {
            DB::statement("ALTER TABLE observations DROP FOREIGN KEY fk_obs_projet");
            DB::statement("ALTER TABLE observations DROP COLUMN projet_id");
        }
    }
};
