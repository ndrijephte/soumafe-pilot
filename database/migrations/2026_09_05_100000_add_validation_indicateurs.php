<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('resultats_cles')) {
            if (!Schema::hasColumn('resultats_cles', 'valide')) {
                DB::statement("ALTER TABLE resultats_cles ADD COLUMN valide TINYINT(1) NULL DEFAULT NULL AFTER poids");
            }
            if (!Schema::hasColumn('resultats_cles', 'date_validation')) {
                DB::statement("ALTER TABLE resultats_cles ADD COLUMN date_validation DATE NULL AFTER valide");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('resultats_cles')) {
            if (Schema::hasColumn('resultats_cles', 'valide')) {
                Schema::table('resultats_cles', fn(Blueprint $t) => $t->dropColumn('valide'));
            }
            if (Schema::hasColumn('resultats_cles', 'date_validation')) {
                Schema::table('resultats_cles', fn(Blueprint $t) => $t->dropColumn('date_validation'));
            }
        }
    }
};
