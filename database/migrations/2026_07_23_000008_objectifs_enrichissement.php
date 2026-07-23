<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('objectifs')) {
            if (!Schema::hasColumn('objectifs', 'date_echeance')) {
                Schema::table('objectifs', function (Blueprint $t) {
                    $t->date('date_echeance')->nullable()->after('periode');
                });
            }
            if (!Schema::hasColumn('objectifs', 'evaluation')) {
                Schema::table('objectifs', function (Blueprint $t) {
                    $t->string('evaluation', 30)->nullable()->after('avancement');
                    // atteint, partiel, non_atteint (calculé ou saisi par pilote)
                });
            }
            if (!Schema::hasColumn('objectifs', 'type')) {
                Schema::table('objectifs', function (Blueprint $t) {
                    $t->string('type', 20)->default('individuel')->after('user_id');
                    // individuel = assigné à un collaborateur, projet = objectif d'équipe lié à un projet
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('objectifs')) {
            Schema::table('objectifs', function (Blueprint $t) {
                if (Schema::hasColumn('objectifs', 'date_echeance')) $t->dropColumn('date_echeance');
                if (Schema::hasColumn('objectifs', 'evaluation')) $t->dropColumn('evaluation');
                if (Schema::hasColumn('objectifs', 'type')) $t->dropColumn('type');
            });
        }
    }
};
