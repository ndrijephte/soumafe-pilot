<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Équipe dynamique : un collaborateur peut rejoindre le projet en cours de développement
 * (date d'ajout / de retrait) et être ajouté ou retiré d'un sprint donné.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('projet_collaborateurs', 'date_retrait')) {
            Schema::table('projet_collaborateurs', function (Blueprint $t) {
                $t->date('date_retrait')->nullable()->after('date_ajout');
            });
            // Membres déjà retirés : date de retrait = dernière modification
            DB::table('projet_collaborateurs')->where('actif', false)->update(['date_retrait' => DB::raw('DATE(updated_at)')]);
        }

        if (!Schema::hasTable('sprint_equipe')) {
            Schema::create('sprint_equipe', function (Blueprint $t) {
                $t->id();
                $t->foreignId('sprint_id')->constrained('sprints')->cascadeOnDelete();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->string('statut', 10); // ajoute | retire
                $t->string('motif', 255)->nullable();
                $t->foreignId('par_id')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();
                $t->unique(['sprint_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_equipe');
        if (Schema::hasColumn('projet_collaborateurs', 'date_retrait')) {
            Schema::table('projet_collaborateurs', fn(Blueprint $t) => $t->dropColumn('date_retrait'));
        }
    }
};
