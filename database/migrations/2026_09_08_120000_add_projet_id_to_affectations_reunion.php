<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('affectations_reunion', 'projet_id')) {
            DB::statement("ALTER TABLE affectations_reunion ADD COLUMN projet_id BIGINT UNSIGNED NULL AFTER remplacant_id");
            DB::statement("ALTER TABLE affectations_reunion ADD CONSTRAINT fk_aff_reunion_projet FOREIGN KEY (projet_id) REFERENCES projets(id) ON DELETE SET NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('affectations_reunion', 'projet_id')) {
            DB::statement("ALTER TABLE affectations_reunion DROP FOREIGN KEY fk_aff_reunion_projet");
            DB::statement("ALTER TABLE affectations_reunion DROP COLUMN projet_id");
        }
    }
};
