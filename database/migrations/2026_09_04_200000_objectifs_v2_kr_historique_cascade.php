<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Résultats clés (Key Results) ---
        Schema::create('resultats_cles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('objectif_id')->constrained('objectifs')->cascadeOnDelete();
            $t->string('libelle');
            $t->string('type_mesure', 30)->default('pourcentage'); // pourcentage|nombre|binaire|monnaie
            $t->string('unite', 30)->nullable(); // %, FCFA, jours, etc.
            $t->decimal('valeur_cible', 12, 2)->default(100);
            $t->decimal('valeur_actuelle', 12, 2)->default(0);
            $t->unsignedTinyInteger('poids')->default(1); // poids relatif intra-objectif
            $t->unsignedTinyInteger('ordre')->default(0);
            $t->timestamps();
            $t->index('objectif_id');
        });

        // --- Historique d'avancement ---
        Schema::create('historique_avancement', function (Blueprint $t) {
            $t->id();
            $t->foreignId('objectif_id')->constrained('objectifs')->cascadeOnDelete();
            $t->date('date');
            $t->unsignedTinyInteger('avancement');
            $t->string('source', 20)->default('manuel'); // manuel|kr_sync|auto
            $t->timestamps();
            $t->index(['objectif_id', 'date']);
        });

        // --- Pivot objectif ↔ tâche ---
        Schema::create('objectif_tache', function (Blueprint $t) {
            $t->id();
            $t->foreignId('objectif_id')->constrained('objectifs')->cascadeOnDelete();
            $t->foreignId('tache_id')->constrained('taches')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['objectif_id', 'tache_id']);
        });

        // --- Nouvelles colonnes sur objectifs ---
        if (Schema::hasTable('objectifs')) {
            if (!Schema::hasColumn('objectifs', 'parent_id')) {
                Schema::table('objectifs', function (Blueprint $t) {
                    $t->unsignedBigInteger('parent_id')->nullable()->after('id');
                    $t->foreign('parent_id')->references('id')->on('objectifs')->nullOnDelete();
                });
            }
            if (!Schema::hasColumn('objectifs', 'priorite')) {
                DB::statement("ALTER TABLE objectifs ADD COLUMN priorite VARCHAR(20) DEFAULT 'moyenne' AFTER evaluation");
            }
            if (!Schema::hasColumn('objectifs', 'note_auto')) {
                DB::statement("ALTER TABLE objectifs ADD COLUMN note_auto TINYINT UNSIGNED NULL AFTER commentaire_pilote");
            }
            if (!Schema::hasColumn('objectifs', 'commentaire_auto')) {
                DB::statement("ALTER TABLE objectifs ADD COLUMN commentaire_auto VARCHAR(500) NULL AFTER note_auto");
            }
            if (!Schema::hasColumn('objectifs', 'note_pilote')) {
                DB::statement("ALTER TABLE objectifs ADD COLUMN note_pilote TINYINT UNSIGNED NULL AFTER commentaire_auto");
            }
            if (!Schema::hasColumn('objectifs', 'date_evaluation')) {
                DB::statement("ALTER TABLE objectifs ADD COLUMN date_evaluation DATE NULL AFTER note_pilote");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('objectif_tache');
        Schema::dropIfExists('historique_avancement');
        Schema::dropIfExists('resultats_cles');

        if (Schema::hasTable('objectifs')) {
            Schema::table('objectifs', function (Blueprint $t) {
                if (Schema::hasColumn('objectifs', 'parent_id')) {
                    $t->dropForeign(['parent_id']);
                    $t->dropColumn('parent_id');
                }
            });
            $cols = ['priorite', 'note_auto', 'commentaire_auto', 'note_pilote', 'date_evaluation'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('objectifs', $col)) {
                    Schema::table('objectifs', fn(Blueprint $t) => $t->dropColumn($col));
                }
            }
        }
    }
};
