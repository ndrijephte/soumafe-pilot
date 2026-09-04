<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('depenses', 'vehicule_id')) {
            DB::statement("ALTER TABLE depenses ADD COLUMN vehicule_id BIGINT UNSIGNED NULL AFTER type_depense");
            DB::statement("ALTER TABLE depenses ADD CONSTRAINT fk_dep_vehicule FOREIGN KEY (vehicule_id) REFERENCES vehicules(id) ON DELETE SET NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('depenses', 'vehicule_id')) {
            DB::statement("ALTER TABLE depenses DROP FOREIGN KEY fk_dep_vehicule");
            DB::statement("ALTER TABLE depenses DROP COLUMN vehicule_id");
        }
    }
};
