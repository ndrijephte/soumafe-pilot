<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('objectifs') && !Schema::hasColumn('objectifs', 'date_debut')) {
            Schema::table('objectifs', function (Blueprint $t) {
                $t->date('date_debut')->nullable()->after('ponderation');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('objectifs') && Schema::hasColumn('objectifs', 'date_debut')) {
            Schema::table('objectifs', fn (Blueprint $t) => $t->dropColumn('date_debut'));
        }
    }
};
