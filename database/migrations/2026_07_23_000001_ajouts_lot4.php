<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Table domaines d'activité
        Schema::create('domaines', function (Blueprint $t) {
            $t->id();
            $t->string('libelle', 100);
            $t->timestamps();
        });

        // 2. Table services
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('libelle', 100);
            $t->foreignId('domaine_id')->nullable()->constrained('domaines')->nullOnDelete();
            $t->timestamps();
        });

        // 3. Rattacher pôles aux services
        Schema::table('poles', function (Blueprint $t) {
            $t->foreignId('service_id')->nullable()->after('libelle')->constrained('services')->nullOnDelete();
        });

        // 4. Délégation sprint par projet
        Schema::table('projets', function (Blueprint $t) {
            $t->boolean('delegation_sprint')->default(false)->after('statut');
        });

        // 5. Table événements (faits marquants)
        Schema::create('evenements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->enum('type', ['interruption_service', 'absence', 'condoleance', 'reunion_exceptionnelle', 'autre'])->default('autre');
            $t->string('description', 500);
            $t->string('impact', 255)->nullable();
            $t->timestamps();
        });

        // 6. Table confirmations quotidiennes de tâches
        Schema::create('confirmations_taches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tache_id')->constrained('taches')->cascadeOnDelete();
            $t->date('date');
            $t->enum('statut', ['confirmee', 'infirmee'])->default('confirmee');
            $t->string('motif', 500)->nullable();
            $t->string('plan_action', 500)->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'tache_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confirmations_taches');
        Schema::dropIfExists('evenements');
        Schema::table('projets', fn (Blueprint $t) => $t->dropColumn('delegation_sprint'));
        Schema::table('poles', fn (Blueprint $t) => $t->dropColumn('service_id'));
        Schema::dropIfExists('services');
        Schema::dropIfExists('domaines');
    }
};
