<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('maintenances_preventives')) {
            if (!Schema::hasColumn('maintenances_preventives', 'statut')) {
                DB::statement("ALTER TABLE maintenances_preventives ADD COLUMN statut VARCHAR(20) NOT NULL DEFAULT 'planifiee' AFTER observations");
            }
            if (!Schema::hasColumn('maintenances_preventives', 'date_realisation')) {
                DB::statement("ALTER TABLE maintenances_preventives ADD COLUMN date_realisation DATE NULL AFTER statut");
            }
            if (!Schema::hasColumn('maintenances_preventives', 'cout')) {
                DB::statement("ALTER TABLE maintenances_preventives ADD COLUMN cout INT UNSIGNED NULL AFTER date_realisation");
            }
            if (!Schema::hasColumn('maintenances_preventives', 'prestataire')) {
                DB::statement("ALTER TABLE maintenances_preventives ADD COLUMN prestataire VARCHAR(150) NULL AFTER cout");
            }
            if (!Schema::hasColumn('maintenances_preventives', 'depense_id')) {
                DB::statement("ALTER TABLE maintenances_preventives ADD COLUMN depense_id BIGINT UNSIGNED NULL AFTER prestataire");
            }
        }
    }

    public function down(): void
    {
        $cols = ['statut', 'date_realisation', 'cout', 'prestataire', 'depense_id'];
        foreach ($cols as $col) {
            if (Schema::hasColumn('maintenances_preventives', $col)) {
                DB::statement("ALTER TABLE maintenances_preventives DROP COLUMN {$col}");
            }
        }
    }
};
