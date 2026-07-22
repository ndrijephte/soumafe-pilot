<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('taches', function (Blueprint $t) {
            $t->enum('qualification', ['a_temps', 'en_retard', 'anticipee', 'non_planifiee'])->nullable()->after('statut');
            $t->boolean('effort_volontaire')->default(false)->after('qualification'); // terminée un week-end/férié
            $t->boolean('justification_requise')->default(false)->after('effort_volontaire');
        });
    }
    public function down(): void
    {
        Schema::table('taches', fn (Blueprint $t) => $t->dropColumn(['qualification', 'effort_volontaire', 'justification_requise']));
    }
};
