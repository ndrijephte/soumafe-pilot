<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_intervention_panne', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panne_id')->constrained('pannes')->onDelete('cascade');
            $table->string('designation', 200);
            $table->unsignedInteger('quantite')->default(1);
            $table->unsignedInteger('prix_unitaire')->default(0);
            $table->unsignedInteger('montant')->default(0); // quantite * prix_unitaire
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_intervention_panne');
    }
};
