<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ENUM → VARCHAR pour éviter le rejet silencieux de valeurs non listées
        DB::statement("ALTER TABLE sprints MODIFY COLUMN statut VARCHAR(30) NOT NULL DEFAULT 'planifie'");

        // Uniformiser : 'clos' → 'termine' (ancien nom ENUM vs code actuel)
        DB::table('sprints')->where('statut', 'clos')->update(['statut' => 'termine']);
    }

    public function down(): void
    {
        DB::table('sprints')->where('statut', 'termine')->update(['statut' => 'clos']);
        DB::statement("ALTER TABLE sprints MODIFY COLUMN statut ENUM('planifie','en_cours','clos') NOT NULL DEFAULT 'planifie'");
    }
};
