<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('sprints', 'justification_arret')) {
            Schema::table('sprints', function ($t) {
                $t->text('justification_arret')->nullable()->after('statut');
                $t->timestamp('arrete_le')->nullable()->after('justification_arret');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sprints', function ($t) {
            $t->dropColumn(['justification_arret', 'arrete_le']);
        });
    }
};
