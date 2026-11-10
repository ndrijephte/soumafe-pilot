<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Niveau d'exécution d'une user story déclaré par les développeurs (0 à 100 %, par pas de 10),
 * avec une note et la trace de la dernière déclaration. Il complète l'avancement calculé à partir des tâches.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_stories', function (Blueprint $t) {
            if (!Schema::hasColumn('user_stories', 'avancement')) $t->unsignedTinyInteger('avancement')->nullable()->after('statut');
            if (!Schema::hasColumn('user_stories', 'avancement_note')) $t->string('avancement_note', 500)->nullable()->after('avancement');
            if (!Schema::hasColumn('user_stories', 'avancement_par')) $t->foreignId('avancement_par')->nullable()->after('avancement_note')->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('user_stories', 'avancement_le')) $t->timestamp('avancement_le')->nullable()->after('avancement_par');
        });
    }

    public function down(): void
    {
        Schema::table('user_stories', function (Blueprint $t) {
            if (Schema::hasColumn('user_stories', 'avancement_par')) $t->dropConstrainedForeignId('avancement_par');
            foreach (['avancement_le', 'avancement_note', 'avancement'] as $c) if (Schema::hasColumn('user_stories', $c)) $t->dropColumn($c);
        });
    }
};
