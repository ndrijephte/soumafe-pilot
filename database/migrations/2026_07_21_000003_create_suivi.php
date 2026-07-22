<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jours_saisie', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->enum('statut', ['saisi', 'a_justifier', 'justifie', 'ferie', 'absence'])->default('saisi');
            $t->foreignId('justification_commentaire_id')->nullable()->constrained('commentaires')->nullOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'date']);
        });

        Schema::create('objectifs', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->enum('source', ['personnel', 'assigne_dg']);
            $t->string('libelle');
            $t->unsignedTinyInteger('ponderation'); // %
            $t->string('periode', 7); // AAAA-MM
            $t->unsignedTinyInteger('avancement')->default(0); // %
            $t->enum('statut_validation', ['propose', 'valide', 'refuse'])->default('propose');
            $t->timestamps();
            $t->index(['user_id', 'periode']);
        });

        Schema::create('arbitrages_t3', function (Blueprint $t) {
            $t->id(); $t->foreignId('tache_id')->constrained()->cascadeOnDelete();
            $t->foreignId('demandeur_id')->constrained('users');
            $t->foreignId('decideur_id')->nullable()->constrained('users'); // DG
            $t->enum('decision', ['en_attente', 'accorde', 'refuse'])->default('en_attente');
            $t->timestamps();
        });

        Schema::create('registres', function (Blueprint $t) {
            $t->id(); $t->string('libelle'); $t->text('description')->nullable();
            $t->enum('periodicite', ['quotidien', 'hebdomadaire', 'mensuel', 'evenementiel'])->default('evenementiel');
            $t->json('definition_colonnes'); // [{cle, libelle, type, seuil_alerte?}]
            $t->timestamps(); $t->softDeletes();
        });

        Schema::create('registre_entrees', function (Blueprint $t) {
            $t->id(); $t->foreignId('registre_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained();
            $t->date('date'); $t->json('valeurs');
            $t->timestamps();
            $t->index(['registre_id', 'date']);
        });

        Schema::create('notifications_app', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type'); $t->text('contenu');
            $t->boolean('lu')->default(false);
            $t->enum('canal', ['in_app', 'email'])->default('in_app');
            $t->timestamps();
            $t->index(['user_id', 'lu']);
        });
    }
    public function down(): void
    {
        foreach (['notifications_app', 'registre_entrees', 'registres', 'arbitrages_t3', 'objectifs', 'jours_saisie'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
