<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        // Ajout colonne motif_absence sur recettes_vehicules
        if (Schema::hasTable('recettes_vehicules') && !Schema::hasColumn('recettes_vehicules', 'motif_absence')) {
            DB::statement("ALTER TABLE recettes_vehicules ADD COLUMN motif_absence VARCHAR(80) NULL AFTER observations");
        }

        // Seed motifs d'absence dans types_parc
        if (Schema::hasTable('types_parc')) {
            $motifs = [
                ['vehicule_panne', 'Véhicule en panne', 1],
                ['jour_repos', 'Jour de repos', 2],
                ['chauffeur_absent', 'Chauffeur absent', 3],
                ['vehicule_garage', 'Véhicule au garage', 4],
                ['controle_technique', 'Contrôle technique', 5],
                ['suspension', 'Suspension temporaire', 6],
                ['autre', 'Autre (préciser)', 99],
            ];
            foreach ($motifs as [$code, $libelle, $ordre]) {
                $exists = DB::table('types_parc')
                    ->where('categorie', 'motif_absence')
                    ->where('code', $code)
                    ->exists();
                if (!$exists) {
                    DB::table('types_parc')->insert([
                        'categorie' => 'motif_absence',
                        'code' => $code,
                        'libelle' => $libelle,
                        'actif' => true,
                        'ordre' => $ordre,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('recettes_vehicules') && Schema::hasColumn('recettes_vehicules', 'motif_absence')) {
            DB::statement("ALTER TABLE recettes_vehicules DROP COLUMN motif_absence");
        }
        if (Schema::hasTable('types_parc')) {
            DB::table('types_parc')->where('categorie', 'motif_absence')->delete();
        }
    }
};
