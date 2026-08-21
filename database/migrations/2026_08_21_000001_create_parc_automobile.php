<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajout délégation parc sur users
        if (!Schema::hasColumn('users', 'delegation_parc')) {
            Schema::table('users', fn (Blueprint $t) => $t->boolean('delegation_parc')->default(false)->after('actif'));
        }

        // Fiche véhicules
        if (!Schema::hasTable('vehicules')) {
            Schema::create('vehicules', function (Blueprint $t) {
                $t->id();
                $t->string('immatriculation', 20)->unique();
                $t->string('marque', 50)->nullable();
                $t->string('modele', 50)->nullable();
                $t->date('date_mise_en_service')->nullable();
                $t->string('chauffeur_affecte', 100)->nullable();
                $t->unsignedInteger('kilometrage')->nullable();
                $t->string('statut', 20)->default('en_service'); // en_service, immobilise, reforme
                $t->text('observations')->nullable();
                $t->unsignedInteger('objectif_recette')->default(0); // FCFA
                $t->timestamps();
            });
        }

        // Suivi pannes / interventions
        if (!Schema::hasTable('pannes')) {
            Schema::create('pannes', function (Blueprint $t) {
                $t->id();
                $t->foreignId('vehicule_id')->constrained('vehicules')->cascadeOnDelete();
                $t->date('date_declaration')->nullable();
                $t->text('description');
                $t->string('urgence', 20)->default('moyenne'); // faible, moyenne, haute, critique
                $t->text('diagnostic')->nullable();
                $t->string('decision', 60)->nullable();
                $t->date('date_debut_intervention')->nullable();
                $t->date('date_cloture')->nullable();
                $t->integer('delai_resolution')->nullable();
                $t->unsignedInteger('cout_reel')->nullable(); // FCFA
                $t->string('statut', 20)->default('declaree'); // declaree, en_diagnostic, en_intervention, cloturee
                $t->timestamps();
            });
        }

        // Recette journalière
        if (!Schema::hasTable('recettes_vehicules')) {
            Schema::create('recettes_vehicules', function (Blueprint $t) {
                $t->id();
                $t->date('date');
                $t->foreignId('vehicule_id')->constrained('vehicules')->cascadeOnDelete();
                $t->string('chauffeur', 100)->nullable();
                $t->unsignedInteger('recette')->default(0); // FCFA
                $t->unsignedInteger('objectif')->default(0); // FCFA
                $t->integer('ecart')->default(0); // FCFA
                $t->string('mode_remise', 30)->nullable(); // especes, mobile_money, cheque, virement, autre
                $t->string('controle_par', 100)->nullable();
                $t->text('observations')->nullable();
                $t->timestamps();
                $t->unique(['date', 'vehicule_id']);
            });
        }

        // Maintenance préventive
        if (!Schema::hasTable('maintenances_preventives')) {
            Schema::create('maintenances_preventives', function (Blueprint $t) {
                $t->id();
                $t->foreignId('vehicule_id')->constrained('vehicules')->cascadeOnDelete();
                $t->string('type_echeance', 40); // vidange, assurance, visite_technique, patente, carte_stationnement, autre
                $t->date('derniere_realisation')->nullable();
                $t->date('prochaine_echeance')->nullable();
                $t->text('observations')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances_preventives');
        Schema::dropIfExists('recettes_vehicules');
        Schema::dropIfExists('pannes');
        Schema::dropIfExists('vehicules');
        if (Schema::hasColumn('users', 'delegation_parc')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('delegation_parc'));
        }
    }
};
