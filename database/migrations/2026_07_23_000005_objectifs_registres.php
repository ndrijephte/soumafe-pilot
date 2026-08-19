<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('objectifs')) {
            Schema::create('objectifs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->string('source')->default('pilote'); // pilote, collaborateur
                $t->string('libelle');
                $t->integer('ponderation')->default(0); // poids en %
                $t->string('periode'); // ex: 2026-T3, 2026-S2, 2026-07
                $t->integer('avancement')->default(0); // 0-100
                $t->string('statut_validation')->default('en_attente'); // en_attente, valide, rejete
                $t->text('commentaire_pilote')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('registres')) {
            Schema::create('registres', function (Blueprint $t) {
                $t->id();
                $t->string('libelle');
                $t->text('description')->nullable();
                $t->string('periodicite')->default('quotidien'); // quotidien, hebdomadaire, mensuel
                $t->json('definition_colonnes'); // [{nom, type, obligatoire}]
                $t->softDeletes();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('registre_entrees')) {
            Schema::create('registre_entrees', function (Blueprint $t) {
                $t->id();
                $t->foreignId('registre_id')->constrained()->cascadeOnDelete();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->date('date');
                $t->json('valeurs');
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('registre_entrees');
        Schema::dropIfExists('registres');
        Schema::dropIfExists('objectifs');
    }
};
