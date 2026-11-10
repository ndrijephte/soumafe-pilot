<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CR Review Sprint : colonnes pour le document formel (format review_v1)
 * et enrichissement des user stories (critères BDD, MoSCoW, epic).
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- user_stories : colonnes CR Review Sprint ---
        Schema::table('user_stories', function (Blueprint $t) {
            if (!Schema::hasColumn('user_stories', 'criteres_acceptation')) {
                $t->json('criteres_acceptation')->nullable()->after('effort_estime');
            }
            if (!Schema::hasColumn('user_stories', 'priorite_moscow')) {
                $t->string('priorite_moscow', 10)->nullable()->after('criteres_acceptation');
            }
            if (!Schema::hasColumn('user_stories', 'points_effort')) {
                $t->unsignedSmallInteger('points_effort')->nullable()->after('priorite_moscow');
            }
            if (!Schema::hasColumn('user_stories', 'epic')) {
                $t->string('epic', 150)->nullable()->after('points_effort');
            }
            if (!Schema::hasColumn('user_stories', 'source_cr_id')) {
                $t->foreignId('source_cr_id')->nullable()->after('epic')->constrained('comptes_rendus')->nullOnDelete();
            }
        });

        // --- comptes_rendus : assignation + pièce jointe ---
        Schema::table('comptes_rendus', function (Blueprint $t) {
            if (!Schema::hasColumn('comptes_rendus', 'assigne_a')) {
                $t->foreignId('assigne_a')->nullable()->after('statut')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('comptes_rendus', 'fichier_joint')) {
                $t->string('fichier_joint', 255)->nullable()->after('assigne_a');
            }
            if (!Schema::hasColumn('comptes_rendus', 'format')) {
                $t->string('format', 20)->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('comptes_rendus', function (Blueprint $t) {
            if (Schema::hasColumn('comptes_rendus', 'format')) $t->dropColumn('format');
            if (Schema::hasColumn('comptes_rendus', 'fichier_joint')) $t->dropColumn('fichier_joint');
            if (Schema::hasColumn('comptes_rendus', 'assigne_a')) $t->dropConstrainedForeignId('assigne_a');
        });
        Schema::table('user_stories', function (Blueprint $t) {
            if (Schema::hasColumn('user_stories', 'source_cr_id')) $t->dropConstrainedForeignId('source_cr_id');
            foreach (['epic', 'points_effort', 'priorite_moscow', 'criteres_acceptation'] as $c) {
                if (Schema::hasColumn('user_stories', $c)) $t->dropColumn($c);
            }
        });
    }
};
