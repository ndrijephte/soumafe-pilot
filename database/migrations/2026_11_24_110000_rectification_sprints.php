<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actions rétrospectives : un sprint clôturé peut être rouvert « en rectification » par un pilote (ou le responsable
 * du projet, sur délégation) pour harmoniser l'historique du projet (Sprint Backlog, planning, tâches, Daily, rapports),
 * puis refermé ; ouverture et fermeture sont motivées et tracées.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sprints', function (Blueprint $t) {
            if (!Schema::hasColumn('sprints', 'rectification_ouverte_le')) {
                $t->dateTime('rectification_ouverte_le')->nullable()->after('arrete_le');
                $t->foreignId('rectification_par')->nullable()->after('rectification_ouverte_le')->constrained('users')->nullOnDelete();
                $t->string('rectification_motif', 500)->nullable()->after('rectification_par');
            }
        });
        Schema::table('projets', function (Blueprint $t) {
            if (!Schema::hasColumn('projets', 'delegation_historique')) {
                $t->boolean('delegation_historique')->default(false)->after('delegation_sprint');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sprints', function (Blueprint $t) {
            $t->dropConstrainedForeignId('rectification_par');
            $t->dropColumn(['rectification_ouverte_le', 'rectification_motif']);
        });
        Schema::table('projets', fn(Blueprint $t) => $t->dropColumn('delegation_historique'));
    }
};
