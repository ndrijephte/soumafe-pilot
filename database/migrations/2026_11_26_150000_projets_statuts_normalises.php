<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Statuts de projet hérités (livre, en_cours, cadrage, suspendu) encore présents : ramenés aux 4 statuts actuels,
 * comme la migration d'origine, sans quoi le formulaire de modification les refuse.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('projets')) return;
        foreach (['cadrage' => 'actif', 'en_cours' => 'actif', 'livre' => 'termine', 'suspendu' => 'en_pause'] as $ancien => $nouveau) {
            DB::table('projets')->where('statut', $ancien)->update(['statut' => $nouveau]);
        }
    }

    public function down(): void {}
};
