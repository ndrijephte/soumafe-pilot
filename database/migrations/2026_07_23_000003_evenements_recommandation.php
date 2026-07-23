<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $t) {
            $t->text('recommandation')->nullable()->after('impact');
            $t->foreignId('traite_par_id')->nullable()->after('recommandation')->constrained('users');
            $t->timestamp('traite_le')->nullable()->after('traite_par_id');
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $t) {
            $t->dropForeign(['traite_par_id']);
            $t->dropColumn(['recommandation', 'traite_par_id', 'traite_le']);
        });
    }
};
