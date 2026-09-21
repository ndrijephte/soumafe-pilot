<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!\Schema::hasColumn('recettes_vehicules', 'justification_valable')) {
            DB::statement("ALTER TABLE recettes_vehicules ADD COLUMN justification_valable TINYINT(1) NULL DEFAULT NULL AFTER perspective");
        }
    }

    public function down(): void
    {
        if (\Schema::hasColumn('recettes_vehicules', 'justification_valable')) {
            DB::statement("ALTER TABLE recettes_vehicules DROP COLUMN justification_valable");
        }
    }
};
