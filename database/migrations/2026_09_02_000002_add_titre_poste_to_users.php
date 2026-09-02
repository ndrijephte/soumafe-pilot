<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'titre_poste')) {
            DB::statement("ALTER TABLE users ADD COLUMN titre_poste VARCHAR(100) NULL AFTER role");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'titre_poste')) {
            DB::statement("ALTER TABLE users DROP COLUMN titre_poste");
        }
    }
};
