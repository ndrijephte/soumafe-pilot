<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Période d'exercice (05 → 04) d'un sprint à cheval sur deux périodes : arbitrage du pilote. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sprints', 'periode_exercice')) {
            Schema::table('sprints', function (Blueprint $t) {
                $t->date('periode_exercice')->nullable(); // début (le 05) de la période retenue
                $t->foreignId('periode_arbitree_par')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamp('periode_arbitree_le')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sprints', 'periode_exercice')) {
            Schema::table('sprints', function (Blueprint $t) {
                $t->dropConstrainedForeignId('periode_arbitree_par');
                $t->dropColumn(['periode_exercice', 'periode_arbitree_le']);
            });
        }
    }
};
