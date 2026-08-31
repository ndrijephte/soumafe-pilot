<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recettes_vehicules') && !Schema::hasColumn('recettes_vehicules', 'perspective')) {
            DB::statement("ALTER TABLE recettes_vehicules ADD COLUMN perspective TEXT NULL AFTER motif_absence");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('recettes_vehicules', 'perspective')) {
            DB::statement("ALTER TABLE recettes_vehicules DROP COLUMN perspective");
        }
    }
};
