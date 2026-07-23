<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('poles', function (Blueprint $t) {
            $t->id(); $t->string('libelle'); $t->timestamps();
        });

        Schema::create('jours_feries', function (Blueprint $t) {
            $t->id(); $t->date('date')->unique(); $t->string('libelle'); $t->year('annee'); $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('prenom')->nullable()->after('name');
            $t->foreignId('pole_id')->nullable()->constrained('poles')->nullOnDelete();
            $t->enum('profil_mesure', ['developpeur', 'operationnel', 'support'])->default('support');
            $t->enum('role', ['administrateur', 'direction', 'chef_projet', 'collaborateur', 'invite'])->default('collaborateur');
            $t->boolean('actif')->default(true);
        });
    }
    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['prenom', 'pole_id', 'profil_mesure', 'role', 'actif']));
        Schema::dropIfExists('jours_feries');
        Schema::dropIfExists('poles');
    }
};
