<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recouvrements') && !Schema::hasColumn('recouvrements', 'motif_annulation')) {
            DB::statement("ALTER TABLE recouvrements ADD COLUMN motif_annulation TEXT NULL AFTER statut");
            DB::statement("ALTER TABLE recouvrements ADD COLUMN annule_par VARCHAR(100) NULL AFTER motif_annulation");
            DB::statement("ALTER TABLE recouvrements ADD COLUMN annule_le TIMESTAMP NULL AFTER annule_par");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('recouvrements')) {
            if (Schema::hasColumn('recouvrements', 'motif_annulation')) DB::statement("ALTER TABLE recouvrements DROP COLUMN motif_annulation");
            if (Schema::hasColumn('recouvrements', 'annule_par')) DB::statement("ALTER TABLE recouvrements DROP COLUMN annule_par");
            if (Schema::hasColumn('recouvrements', 'annule_le')) DB::statement("ALTER TABLE recouvrements DROP COLUMN annule_le");
        }
    }
};
