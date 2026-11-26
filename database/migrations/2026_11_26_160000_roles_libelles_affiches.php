<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les libellés de rôle viennent désormais du paramétrage (Administration > Rôles et profils).
 * Les libellés d'origine de la table sont alignés sur ceux affichés jusqu'ici, pour que rien ne change à l'écran.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) return;
        foreach (['direction' => ['Direction', 'Direction Générale'], 'charge_operations' => ['Chargé des opérations', 'Chargé des Opérations et Projets']] as $code => [$ancien, $nouveau]) {
            DB::table('roles')->where('code', $code)->where('libelle', $ancien)->update(['libelle' => $nouveau, 'updated_at' => now()]);
        }
    }

    public function down(): void {}
};
