<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Natures d'absence paramétrables (jusqu'ici figées dans le code) : les 5 natures historiques sont reprises
 * comme natures système ; d'autres peuvent être ajoutées depuis le formulaire de déclaration ou le paramétrage.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('types_absence')) {
            Schema::create('types_absence', function (Blueprint $t) {
                $t->id();
                $t->string('code', 50)->unique();
                $t->string('libelle', 80);
                $t->boolean('systeme')->default(false);
                $t->timestamps();
            });
        }
        foreach (['conge' => 'Congé', 'mission' => 'Mission', 'maladie' => 'Maladie', 'formation' => 'Formation', 'autre' => 'Autre'] as $code => $libelle) {
            if (!DB::table('types_absence')->where('code', $code)->exists()) {
                DB::table('types_absence')->insert(['code' => $code, 'libelle' => $libelle, 'systeme' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('types_absence');
    }
};
