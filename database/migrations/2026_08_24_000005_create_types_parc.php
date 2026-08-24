<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('types_parc')) {
            Schema::create('types_parc', function (Blueprint $table) {
                $table->id();
                $table->string('categorie', 30); // 'penalite' ou 'maintenance'
                $table->string('code', 50);
                $table->string('libelle', 80);
                $table->boolean('actif')->default(true);
                $table->integer('ordre')->default(0);
                $table->timestamps();
                $table->unique(['categorie', 'code']);
            });

            // Seed pénalités
            $penalites = [
                ['contravention', 'Contravention', 1],
                ['exces_vitesse', 'Excès de vitesse', 2],
                ['stationnement', 'Stationnement', 3],
                ['defaut_document', 'Défaut de document', 4],
                ['autre', 'Autre', 99],
            ];
            foreach ($penalites as [$code, $libelle, $ordre]) {
                DB::table('types_parc')->insert([
                    'categorie' => 'penalite', 'code' => $code, 'libelle' => $libelle,
                    'actif' => true, 'ordre' => $ordre, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            // Seed maintenance
            $maintenances = [
                ['vidange', 'Vidange', 1],
                ['assurance', 'Assurance', 2],
                ['visite_technique', 'Visite technique', 3],
                ['patente', 'Patente', 4],
                ['carte_stationnement', 'Carte de stationnement', 5],
                ['autre', 'Autre', 99],
            ];
            foreach ($maintenances as [$code, $libelle, $ordre]) {
                DB::table('types_parc')->insert([
                    'categorie' => 'maintenance', 'code' => $code, 'libelle' => $libelle,
                    'actif' => true, 'ordre' => $ordre, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('types_parc');
    }
};
