<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * Horaires et jours de travail paramétrables, avec date d'effet.
 * Chaque règle s'applique de sa date d'effet jusqu'à la suivante : l'historique n'est jamais réécrit,
 * les statistiques passées (jours ouvrés, retards de pointage) restent calculées avec la règle de l'époque.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('horaires_travail', function (Blueprint $t) {
            $t->id();
            $t->date('date_effet')->unique();
            $t->time('heure_arrivee');
            $t->time('heure_depart');
            $t->string('jours', 20)->comment('Jours travaillés ISO (1 = lundi … 7 = dimanche), séparés par des virgules');
            $t->string('motif', 255)->nullable();
            $t->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        // Règle d'origine : celle appliquée jusqu'ici (lundi → vendredi, 08:00 → 18:00)
        DB::table('horaires_travail')->insert([
            'date_effet' => '2000-01-01', 'heure_arrivee' => '08:00:00', 'heure_depart' => '18:00:00',
            'jours' => '1,2,3,4,5', 'motif' => "Règle d'origine", 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('horaires_travail');
    }
};
