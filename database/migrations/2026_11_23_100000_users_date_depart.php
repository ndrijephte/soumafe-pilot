<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * Date de départ d'un collaborateur (compte désactivé) : avant cette date il reste membre de ses projets,
 * de ses sprints et de ses Daily Scrum ; après, il n'est plus proposé (rôles, lignes de Daily, rotation).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'date_depart')) {
            Schema::table('users', fn(Blueprint $t) => $t->date('date_depart')->nullable()->after('actif'));
        }
        // Comptes déjà désactivés : départ à la date de la dernière modification du compte
        DB::table('users')->where('actif', false)->whereNull('date_depart')->update(['date_depart' => DB::raw('DATE(updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('users', fn(Blueprint $t) => $t->dropColumn('date_depart'));
    }
};
