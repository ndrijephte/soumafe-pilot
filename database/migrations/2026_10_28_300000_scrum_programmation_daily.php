<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Programmation paramétrable du Daily Scrum par projet (jours, horaire, durée, lieu, création automatique)
 * et circuit de publication : le scribe soumet, un pilote approuve avant toute diffusion.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('projets', 'daily_config')) {
            Schema::table('projets', fn(Blueprint $t) => $t->json('daily_config')->nullable());
        }
        if (!Schema::hasColumn('daily_scrums', 'soumis_le')) {
            Schema::table('daily_scrums', function (Blueprint $t) {
                $t->timestamp('soumis_le')->nullable();
                $t->foreignId('soumis_par')->nullable()->constrained('users')->nullOnDelete();
            });
        }
        // Titres générés des comptes rendus Scrum : « - » plutôt que « — » pour relier les textes
        DB::table('comptes_rendus')->whereIn('type', ['review_sprint', 'retrospective'])
            ->update(['titre' => DB::raw("REPLACE(titre, ' — ', ' - ')")]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('daily_scrums', 'soumis_le')) {
            Schema::table('daily_scrums', function (Blueprint $t) {
                $t->dropConstrainedForeignId('soumis_par');
                $t->dropColumn('soumis_le');
            });
        }
        if (Schema::hasColumn('projets', 'daily_config')) {
            Schema::table('projets', fn(Blueprint $t) => $t->dropColumn('daily_config'));
        }
    }
};
