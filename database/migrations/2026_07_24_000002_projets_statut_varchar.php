<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('projets')) return;

        // ENUM → VARCHAR(30)
        DB::statement("ALTER TABLE projets MODIFY statut VARCHAR(30) NOT NULL DEFAULT 'actif'");

        // Mapper anciennes valeurs vers nouvelles
        DB::table('projets')->where('statut', 'cadrage')->update(['statut' => 'actif']);
        DB::table('projets')->where('statut', 'en_cours')->update(['statut' => 'actif']);
        DB::table('projets')->where('statut', 'livre')->update(['statut' => 'termine']);
        DB::table('projets')->where('statut', 'suspendu')->update(['statut' => 'en_pause']);
        DB::table('projets')->where('statut', 'archive')->update(['statut' => 'annule']);
    }

    public function down(): void
    {
        // Pas de retour ENUM
    }
};
