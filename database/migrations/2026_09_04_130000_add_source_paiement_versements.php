<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('versements_recouvrement')) {
            if (!Schema::hasColumn('versements_recouvrement', 'source_paiement')) {
                DB::statement("ALTER TABLE versements_recouvrement ADD COLUMN source_paiement VARCHAR(20) NOT NULL DEFAULT 'chauffeur' AFTER mode_paiement");
            }
            if (!Schema::hasColumn('versements_recouvrement', 'depense_id')) {
                DB::statement("ALTER TABLE versements_recouvrement ADD COLUMN depense_id BIGINT UNSIGNED NULL AFTER source_paiement");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('versements_recouvrement', 'source_paiement')) {
            DB::statement("ALTER TABLE versements_recouvrement DROP COLUMN source_paiement");
        }
        if (Schema::hasColumn('versements_recouvrement', 'depense_id')) {
            DB::statement("ALTER TABLE versements_recouvrement DROP COLUMN depense_id");
        }
    }
};
