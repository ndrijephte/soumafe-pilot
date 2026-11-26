<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * Horaire par défaut : 08:00 → 17:00 (jours exceptionnels travaillés : 08:00 → 12:00, réglés dans l'application).
 * Seule la règle initiale est ajustée, et seulement si aucun horaire n'a été paramétré depuis : un réglage
 * fait par l'administration n'est jamais modifié.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('horaires_travail')) return;
        if (DB::table('horaires_travail')->count() !== 1) return;
        DB::table('horaires_travail')->where('heure_arrivee', '08:00:00')->where('heure_depart', '18:00:00')
            ->update(['heure_depart' => '17:00:00', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Rien à défaire : l'horaire reste paramétrable dans l'administration.
    }
};
