<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table des types d'événement paramétrables
        Schema::create('types_evenement', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('libelle', 100);
            $t->string('couleur_fond', 20)->default('#f1f5f9');
            $t->string('couleur_texte', 20)->default('#64748b');
            $t->boolean('systeme')->default(false);
            $t->timestamps();
        });

        // Seed des types système par défaut
        $types = [
            ['code' => 'interruption_service', 'libelle' => 'Interruption de service', 'couleur_fond' => '#fee2e2', 'couleur_texte' => '#b91c1c', 'systeme' => true],
            ['code' => 'absence', 'libelle' => 'Absence', 'couleur_fond' => '#fef3c7', 'couleur_texte' => '#b45309', 'systeme' => true],
            ['code' => 'condoleance', 'libelle' => 'Condoléances', 'couleur_fond' => '#e2e8f0', 'couleur_texte' => '#475569', 'systeme' => true],
            ['code' => 'reunion_exceptionnelle', 'libelle' => 'Réunion exceptionnelle', 'couleur_fond' => '#dbeafe', 'couleur_texte' => '#1d4ed8', 'systeme' => true],
            ['code' => 'autre', 'libelle' => 'Autre', 'couleur_fond' => '#f1f5f9', 'couleur_texte' => '#64748b', 'systeme' => true],
        ];
        $now = now();
        foreach ($types as $type) {
            \DB::table('types_evenement')->insert(array_merge($type, ['created_at' => $now, 'updated_at' => $now]));
        }

        // Ajouter action_engagee sur evenements
        Schema::table('evenements', function (Blueprint $t) {
            $t->text('action_engagee')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $t) {
            $t->dropColumn('action_engagee');
        });
        Schema::dropIfExists('types_evenement');
    }
};
