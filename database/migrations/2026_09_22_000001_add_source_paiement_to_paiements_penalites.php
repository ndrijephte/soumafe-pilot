<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('paiements_penalites') && !Schema::hasColumn('paiements_penalites', 'source_paiement')) {
            DB::statement("ALTER TABLE paiements_penalites ADD COLUMN source_paiement VARCHAR(30) NULL DEFAULT 'chauffeur' AFTER mode_paiement");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('paiements_penalites', 'source_paiement')) {
            DB::statement("ALTER TABLE paiements_penalites DROP COLUMN source_paiement");
        }
    }
};
