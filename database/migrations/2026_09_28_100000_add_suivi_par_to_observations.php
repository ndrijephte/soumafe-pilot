<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!$this->hasColumn('observations', 'suivi_par')) {
            DB::statement("ALTER TABLE observations ADD COLUMN suivi_par BIGINT UNSIGNED NULL AFTER responsable_id");
        }
    }

    public function down(): void
    {
        if ($this->hasColumn('observations', 'suivi_par')) {
            DB::statement("ALTER TABLE observations DROP COLUMN suivi_par");
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }
};
