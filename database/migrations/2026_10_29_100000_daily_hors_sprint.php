<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Daily Scrum hors sprint : un Daily peut être rattaché à un projet sans sprint, ou être transversal
 * (sans projet). Ses lignes sont pré-remplies avec les tâches du module Projets / Sprints.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_scrums', function (Blueprint $t) {
            $t->foreignId('sprint_id')->nullable()->change();
        });
        if (!Schema::hasColumn('daily_scrums', 'projet_id')) {
            Schema::table('daily_scrums', function (Blueprint $t) {
                $t->foreignId('projet_id')->nullable()->after('sprint_id')->constrained('projets')->nullOnDelete();
                $t->string('objet', 150)->nullable()->after('projet_id');
            });
        }
        // Daily existants : projet du sprint
        DB::table('daily_scrums')->whereNull('projet_id')->whereNotNull('sprint_id')
            ->update(['projet_id' => DB::raw('(SELECT projet_id FROM sprints WHERE sprints.id = daily_scrums.sprint_id)')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('daily_scrums', 'projet_id')) {
            Schema::table('daily_scrums', function (Blueprint $t) {
                $t->dropConstrainedForeignId('projet_id');
                $t->dropColumn('objet');
            });
        }
    }
};
