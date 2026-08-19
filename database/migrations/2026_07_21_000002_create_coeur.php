<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projets', function (Blueprint $t) {
            $t->id(); $t->string('code')->unique(); $t->string('libelle'); $t->string('client')->nullable();
            $t->foreignId('chef_projet_id')->nullable()->constrained('users')->nullOnDelete();
            $t->enum('statut', ['cadrage', 'en_cours', 'livre', 'suspendu', 'archive'])->default('cadrage');
            $t->date('date_debut')->nullable(); $t->date('date_cible')->nullable();
            $t->timestamps(); $t->softDeletes();
        });

        Schema::create('projet_user', function (Blueprint $t) {
            $t->foreignId('projet_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->primary(['projet_id', 'user_id']);
        });

        Schema::create('sprints', function (Blueprint $t) {
            $t->id(); $t->foreignId('projet_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('numero'); $t->text('objectif')->nullable();
            $t->date('date_debut'); $t->date('date_fin');
            $t->unsignedSmallInteger('engagement_points')->default(0);
            $t->enum('statut', ['planifie', 'en_cours', 'clos'])->default('planifie');
            $t->timestamps();
            $t->unique(['projet_id', 'numero']);
        });

        Schema::create('taches', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained();
            $t->enum('type', ['T1', 'T2', 'T3']);
            $t->string('intitule');
            $t->foreignId('projet_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('sprint_id')->nullable()->constrained()->nullOnDelete(); // obligatoire si T1 (FormRequest)
            $t->string('activite')->nullable(); // rattachement transverse des T2/T3
            $t->enum('priorite', ['haute', 'moyenne', 'basse'])->default('moyenne');
            $t->date('date_debut'); $t->date('echeance')->nullable(); $t->date('date_fin_reelle')->nullable();
            $t->unsignedTinyInteger('points')->nullable(); // estimation T1
            $t->enum('statut', ['a_faire', 'en_cours', 'bloque', 'termine', 'reporte'])->default('a_faire');
            $t->timestamps(); $t->softDeletes();
            $t->index(['user_id', 'date_debut']);
        });

        Schema::create('blocages', function (Blueprint $t) {
            $t->id(); $t->foreignId('tache_id')->constrained()->cascadeOnDelete();
            $t->string('motif'); $t->string('leveur_pressenti')->nullable();
            $t->dateTime('date_blocage'); $t->dateTime('date_levee')->nullable();
            $t->timestamps();
        });

        Schema::create('commentaires', function (Blueprint $t) {
            $t->id(); $t->foreignId('tache_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained();
            $t->text('contenu'); $t->timestamps();
        });
    }
    public function down(): void
    {
        foreach (['commentaires', 'blocages', 'taches', 'sprints', 'projet_user', 'projets'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
