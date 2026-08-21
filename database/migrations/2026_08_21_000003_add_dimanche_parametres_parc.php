<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('parametres_parc')) return;

        $now = now();

        // Renommer les libellés existants pour clarifier "férié" vs "dimanche"
        DB::table('parametres_parc')->where('cle', 'objectif_taxi_ferie')
            ->update(['libelle' => 'Objectif recette Taxi — jour férié (FCFA)']);
        DB::table('parametres_parc')->where('cle', 'objectif_vtc_ferie')
            ->update(['libelle' => 'Objectif recette VTC — jour férié (FCFA)']);

        // Ajouter les paramètres dimanche si absents
        if (!DB::table('parametres_parc')->where('cle', 'objectif_taxi_dimanche')->exists()) {
            DB::table('parametres_parc')->insert([
                'cle' => 'objectif_taxi_dimanche', 'valeur' => '10000',
                'libelle' => 'Objectif recette Taxi — dimanche (FCFA)',
                'groupe' => 'recettes', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        if (!DB::table('parametres_parc')->where('cle', 'objectif_vtc_dimanche')->exists()) {
            DB::table('parametres_parc')->insert([
                'cle' => 'objectif_vtc_dimanche', 'valeur' => '15000',
                'libelle' => 'Objectif recette VTC — dimanche (FCFA)',
                'groupe' => 'recettes', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('parametres_parc')->whereIn('cle', ['objectif_taxi_dimanche', 'objectif_vtc_dimanche'])->delete();
    }
};
