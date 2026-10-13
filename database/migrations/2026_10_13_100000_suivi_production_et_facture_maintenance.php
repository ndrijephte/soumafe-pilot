<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('observation_historiques')) {
            Schema::create('observation_historiques', function (Blueprint $t) {
                $t->id();
                $t->foreignId('observation_id')->constrained('observations')->cascadeOnDelete();
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('action', 40);
                $t->string('details', 1000)->nullable();
                $t->timestamp('created_at')->useCurrent();
            });
        }
        if (!Schema::hasTable('lignes_intervention_maintenance')) {
            Schema::create('lignes_intervention_maintenance', function (Blueprint $t) {
                $t->id();
                $t->foreignId('maintenance_id')->constrained('maintenances_preventives')->cascadeOnDelete();
                $t->string('designation', 200);
                $t->unsignedInteger('quantite')->default(1);
                $t->unsignedInteger('prix_unitaire')->default(0);
                $t->unsignedInteger('montant')->default(0);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_intervention_maintenance');
        Schema::dropIfExists('observation_historiques');
    }
};
