<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pointages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->time('heure_arrivee')->nullable();
            $t->time('heure_depart')->nullable();
            $t->string('statut', 30)->default('non_pointe'); // present, retard, sorti_tot, absent, non_pointe
            $t->text('motif_retard')->nullable();
            $t->text('motif_depart')->nullable();
            $t->text('motif_absence')->nullable();
            $t->foreignId('corrige_par')->nullable()->constrained('users')->nullOnDelete();
            $t->text('motif_correction')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pointages');
    }
};
