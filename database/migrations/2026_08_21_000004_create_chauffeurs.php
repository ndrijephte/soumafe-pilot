<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chauffeurs')) {
            Schema::create('chauffeurs', function (Blueprint $table) {
                $table->id();
                $table->string('nom', 60);
                $table->string('prenom', 60);
                $table->string('numero_permis', 30)->nullable();
                $table->string('telephone', 20)->nullable();
                $table->enum('statut', ['actif', 'inactif'])->default('actif');
                $table->text('observations')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('chauffeur_vehicule')) {
            Schema::create('chauffeur_vehicule', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chauffeur_id')->constrained('chauffeurs')->cascadeOnDelete();
                $table->foreignId('vehicule_id')->constrained('vehicules')->cascadeOnDelete();
                $table->enum('role', ['principal', 'alternant'])->default('principal');
                $table->boolean('actif')->default(true);
                $table->timestamps();

                $table->unique(['chauffeur_id', 'vehicule_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chauffeur_vehicule');
        Schema::dropIfExists('chauffeurs');
    }
};
