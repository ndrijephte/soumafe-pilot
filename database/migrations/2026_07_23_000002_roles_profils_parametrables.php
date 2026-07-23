<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        // 1. Transformer enum role → varchar
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'collaborateur'");

        // 2. Transformer enum profil_mesure → varchar
        DB::statement("ALTER TABLE users MODIFY COLUMN profil_mesure VARCHAR(50) NOT NULL DEFAULT 'support'");

        // 3. Table des rôles paramétrables
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('libelle', 100);
            $t->boolean('systeme')->default(false); // rôles système non supprimables
            $t->timestamps();
        });

        // 4. Table des profils de mesure paramétrables
        Schema::create('profils_mesure', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('libelle', 100);
            $t->boolean('systeme')->default(false);
            $t->timestamps();
        });

        // 5. Insérer les rôles système
        DB::table('roles')->insert([
            ['code' => 'administrateur', 'libelle' => 'Administrateur', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'direction', 'libelle' => 'Direction', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'charge_operations', 'libelle' => 'Chargé des opérations', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'collaborateur', 'libelle' => 'Collaborateur', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'invite', 'libelle' => 'Invité', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 6. Insérer les profils système
        DB::table('profils_mesure')->insert([
            ['code' => 'developpeur', 'libelle' => 'Développeur', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'operationnel', 'libelle' => 'Opérationnel', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'support', 'libelle' => 'Support', 'systeme' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('profils_mesure');
        Schema::dropIfExists('roles');
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('administrateur','direction','charge_operations','collaborateur','invite') DEFAULT 'collaborateur'");
        DB::statement("ALTER TABLE users MODIFY COLUMN profil_mesure ENUM('developpeur','operationnel','support') DEFAULT 'support'");
    }
};
