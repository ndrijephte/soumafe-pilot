<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module Scrum, phases 4 à 6 : rôles du Daily, origine des user stories. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_scrums', function (Blueprint $t) {
            // {"facilitateur": user_id, "scribe": user_id, "gardien_temps": user_id, "observateur": user_id}
            $t->json('roles')->nullable()->after('heure_fin');
            $t->foreignId('cree_par')->nullable()->after('observation')->constrained('users')->nullOnDelete();
        });
        Schema::table('user_stories', function (Blueprint $t) {
            // manuel | import | retrospective
            $t->string('origine', 20)->default('manuel')->after('effort_estime');
        });
    }

    public function down(): void
    {
        Schema::table('daily_scrums', function (Blueprint $t) {
            $t->dropConstrainedForeignId('cree_par');
            $t->dropColumn('roles');
        });
        Schema::table('user_stories', fn(Blueprint $t) => $t->dropColumn('origine'));
    }
};
