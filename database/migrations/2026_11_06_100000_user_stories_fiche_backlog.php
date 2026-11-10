<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fiche d'une user story du Product Backlog : profil / acteur et Definition of Ready.
 * Le statut « planifiée » disparaît : une story engagée reste « à faire » jusqu'au début de son exécution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_stories', function (Blueprint $t) {
            $t->string('profil', 150)->nullable()->after('epic');
            $t->boolean('dor')->default(false)->after('profil');
        });
        DB::table('user_stories')->where('statut', 'planifiee')->update(['statut' => 'a_faire']);
    }

    public function down(): void
    {
        Schema::table('user_stories', fn(Blueprint $t) => $t->dropColumn(['profil', 'dor']));
    }
};
