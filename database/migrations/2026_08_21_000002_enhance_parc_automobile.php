<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajout catégorie + objectif_recette nullable sur vehicules
        if (Schema::hasTable('vehicules')) {
            if (!Schema::hasColumn('vehicules', 'categorie')) {
                DB::statement("ALTER TABLE vehicules ADD COLUMN categorie VARCHAR(10) NOT NULL DEFAULT 'taxi' AFTER immatriculation");
            }
            DB::statement("ALTER TABLE vehicules MODIFY objectif_recette INT UNSIGNED NULL DEFAULT 0");
        }

        // 2. Ajout type_jour sur recettes_vehicules
        if (Schema::hasTable('recettes_vehicules') && !Schema::hasColumn('recettes_vehicules', 'type_jour')) {
            DB::statement("ALTER TABLE recettes_vehicules ADD COLUMN type_jour VARCHAR(15) NOT NULL DEFAULT 'ouvrable' AFTER `date`");
        }

        // 3. Table paramètres parc (objectifs configurables)
        if (!Schema::hasTable('parametres_parc')) {
            Schema::create('parametres_parc', function (Blueprint $t) {
                $t->id();
                $t->string('cle', 60)->unique();
                $t->string('valeur', 255)->nullable();
                $t->string('libelle', 120);
                $t->string('groupe', 40)->default('general');
                $t->timestamps();
            });

            // Valeurs par défaut
            $now = now();
            DB::table('parametres_parc')->insert([
                ['cle' => 'objectif_taxi_ouvrable', 'valeur' => '20000', 'libelle' => 'Objectif recette Taxi — jour ouvrable (FCFA)', 'groupe' => 'recettes', 'created_at' => $now, 'updated_at' => $now],
                ['cle' => 'objectif_taxi_ferie', 'valeur' => '10000', 'libelle' => 'Objectif recette Taxi — férié / dimanche (FCFA)', 'groupe' => 'recettes', 'created_at' => $now, 'updated_at' => $now],
                ['cle' => 'objectif_vtc_ouvrable', 'valeur' => '25000', 'libelle' => 'Objectif recette VTC — jour ouvrable (FCFA)', 'groupe' => 'recettes', 'created_at' => $now, 'updated_at' => $now],
                ['cle' => 'objectif_vtc_ferie', 'valeur' => '15000', 'libelle' => 'Objectif recette VTC — férié / dimanche (FCFA)', 'groupe' => 'recettes', 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // 4. Table pénalités
        if (!Schema::hasTable('penalites')) {
            Schema::create('penalites', function (Blueprint $t) {
                $t->id();
                $t->foreignId('vehicule_id')->constrained('vehicules')->cascadeOnDelete();
                $t->date('date_infraction');
                $t->string('type', 40); // contravention, exces_vitesse, stationnement, defaut_document, autre
                $t->string('chauffeur', 100)->nullable();
                $t->string('lieu', 120)->nullable();
                $t->text('description')->nullable();
                $t->unsignedInteger('montant')->default(0); // FCFA
                $t->string('statut', 20)->default('non_payee'); // non_payee, payee, contestee
                $t->date('date_paiement')->nullable();
                $t->text('observations')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penalites');
        Schema::dropIfExists('parametres_parc');

        if (Schema::hasTable('recettes_vehicules') && Schema::hasColumn('recettes_vehicules', 'type_jour')) {
            Schema::table('recettes_vehicules', fn (Blueprint $t) => $t->dropColumn('type_jour'));
        }
        if (Schema::hasTable('vehicules') && Schema::hasColumn('vehicules', 'categorie')) {
            Schema::table('vehicules', fn (Blueprint $t) => $t->dropColumn('categorie'));
        }
    }
};
