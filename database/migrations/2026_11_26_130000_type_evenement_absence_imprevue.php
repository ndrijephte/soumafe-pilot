<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le type d'événement « Absence » faisait doublon avec la déclaration d'absence (congé, mission…) :
 * il devient « Absence imprévue ». Seul le libellé change (le code « absence » et l'historique sont conservés),
 * et seulement s'il n'a pas déjà été renommé.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('types_evenement')) return;
        DB::table('types_evenement')->where('code', 'absence')->where('libelle', 'Absence')
            ->update(['libelle' => 'Absence imprévue', 'updated_at' => now()]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('types_evenement')) return;
        DB::table('types_evenement')->where('code', 'absence')->where('libelle', 'Absence imprévue')
            ->update(['libelle' => 'Absence', 'updated_at' => now()]);
    }
};
