<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('sprints', 'collaborateurs_exclus')) {
            DB::statement("ALTER TABLE sprints ADD COLUMN collaborateurs_exclus TEXT NULL AFTER visible_collaborateurs");
        }
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('sprints', 'collaborateurs_exclus')) {
            DB::statement("ALTER TABLE sprints DROP COLUMN collaborateurs_exclus");
        }
    }
};
