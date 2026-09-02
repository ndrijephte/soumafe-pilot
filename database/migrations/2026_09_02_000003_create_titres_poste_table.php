<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('titres_poste')) {
            Schema::create('titres_poste', function (Blueprint $t) {
                $t->id();
                $t->string('libelle', 100)->unique();
                $t->timestamps();
            });

            // Valeurs initiales
            $titres = [
                'Lead Developer',
                'Responsable Logistique',
                'Développeur mobile',
                'Développeur Full-Stack',
                'Chargé des Opérations & Projets',
                'Chef de Mission',
                'Contrôleur de Travaux',
                'Directeur Général',
                'Administrateur',
            ];
            foreach ($titres as $t) {
                DB::table('titres_poste')->insert(['libelle' => $t, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('titres_poste');
    }
};
