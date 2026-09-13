<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('sprints', 'visible_collaborateurs')) {
            DB::statement("ALTER TABLE sprints ADD COLUMN visible_collaborateurs TINYINT(1) NOT NULL DEFAULT 1 AFTER statut");
        }
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('sprints', 'visible_collaborateurs')) {
            DB::statement("ALTER TABLE sprints DROP COLUMN visible_collaborateurs");
        }
    }
};
