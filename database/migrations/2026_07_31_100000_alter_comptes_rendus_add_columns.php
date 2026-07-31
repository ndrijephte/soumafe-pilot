<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('comptes_rendus', function (Blueprint $table) {
            if (!Schema::hasColumn('comptes_rendus', 'president_id')) {
                $table->foreignId('president_id')->nullable()->after('designe_par')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('comptes_rendus', 'heure_debut')) {
                $table->string('heure_debut', 10)->nullable()->after('date_reunion');
            }
            if (!Schema::hasColumn('comptes_rendus', 'heure_fin')) {
                $table->string('heure_fin', 10)->nullable()->after('heure_debut');
            }
        });
    }

    public function down(): void
    {
        Schema::table('comptes_rendus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('president_id');
            $table->dropColumn(['heure_debut', 'heure_fin']);
        });
    }
};
