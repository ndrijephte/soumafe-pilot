<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('types_blocage')) {
            Schema::create('types_blocage', function (Blueprint $t) {
                $t->id();
                $t->string('code', 50)->unique();
                $t->string('libelle', 100);
                $t->boolean('systeme')->default(false);
                $t->timestamps();
            });
            // Seed par défaut
            DB::table('types_blocage')->insert([
                ['code' => 'technique', 'libelle' => 'Technique', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'ressource', 'libelle' => 'Ressource', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'dependance', 'libelle' => 'Dépendance', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'decision', 'libelle' => 'Décision', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'externe', 'libelle' => 'Externe', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Ajouter intervenant_id sur blocages (remplace leveur_pressenti texte → FK user)
        if (Schema::hasTable('blocages') && !Schema::hasColumn('blocages', 'intervenant_id')) {
            Schema::table('blocages', function (Blueprint $t) {
                $t->unsignedBigInteger('intervenant_id')->nullable()->after('leveur_pressenti');
                $t->foreign('intervenant_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('blocages') && Schema::hasColumn('blocages', 'intervenant_id')) {
            Schema::table('blocages', function (Blueprint $t) {
                $t->dropForeign(['intervenant_id']);
                $t->dropColumn('intervenant_id');
            });
        }
        Schema::dropIfExists('types_blocage');
    }
};
