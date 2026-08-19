<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        // 1. Convertir les chef_projet existants en collaborateur
        DB::table('users')->where('role', 'chef_projet')->update(['role' => 'collaborateur']);

        // 2. Modifier l'enum role : supprimer chef_projet, ajouter charge_operations
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('administrateur','direction','charge_operations','collaborateur','invite') DEFAULT 'collaborateur'");

        // 3. Renommer chef_projet_id → responsable_projet_id dans projets
        Schema::table('projets', function (Blueprint $t) {
            $t->renameColumn('chef_projet_id', 'responsable_projet_id');
        });
    }

    public function down(): void
    {
        Schema::table('projets', function (Blueprint $t) {
            $t->renameColumn('responsable_projet_id', 'chef_projet_id');
        });
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('administrateur','direction','chef_projet','collaborateur','invite') DEFAULT 'collaborateur'");
    }
};
