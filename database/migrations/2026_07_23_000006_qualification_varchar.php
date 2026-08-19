<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('taches', 'qualification')) {
            DB::statement("ALTER TABLE taches MODIFY qualification VARCHAR(30) NULL DEFAULT NULL");
        }
    }

    public function down(): void
    {
        // Pas de rollback sur ce changement
    }
};
