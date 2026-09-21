<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!\Schema::hasColumn('recettes_vehicules', 'pointage')) {
            DB::statement("ALTER TABLE recettes_vehicules ADD COLUMN pointage INT UNSIGNED DEFAULT 0 AFTER ecart");
        }
    }
    public function down(): void
    {
        \Schema::dropColumns('recettes_vehicules', ['pointage']);
    }
};
