<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('comptes_rendus')) {
            Schema::create('comptes_rendus', function (Blueprint $table) {
                $table->id();
                $table->string('type', 30);            // reunion_ordinaire | reunion_extraordinaire | demo_sprint
                $table->string('titre');
                $table->foreignId('sprint_id')->nullable()->constrained('sprints')->nullOnDelete();
                $table->foreignId('redacteur_id')->constrained('users');
                $table->foreignId('designe_par')->constrained('users');
                $table->foreignId('president_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('date_reunion');
                $table->string('heure_debut', 10)->nullable();
                $table->string('heure_fin', 10)->nullable();
                $table->string('lieu')->nullable();
                $table->string('statut', 20)->default('brouillon'); // brouillon | confirme
                $table->text('donnees_json')->nullable();           // contenu structuré JSON
                $table->string('fichier_path')->nullable();         // chemin du DOCX généré
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes_rendus');
    }
};
