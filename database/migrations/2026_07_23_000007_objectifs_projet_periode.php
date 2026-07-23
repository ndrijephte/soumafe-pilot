<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('objectifs')) {
            if (!Schema::hasColumn('objectifs', 'projet_id')) {
                Schema::table('objectifs', function (Blueprint $t) {
                    $t->foreignId('projet_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
                });
            }
            if (!Schema::hasColumn('objectifs', 'commentaire_pilote')) {
                Schema::table('objectifs', function (Blueprint $t) {
                    $t->text('commentaire_pilote')->nullable()->after('statut_validation');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('objectifs') && Schema::hasColumn('objectifs', 'projet_id')) {
            Schema::table('objectifs', fn (Blueprint $t) => $t->dropConstrainedForeignId('projet_id'));
        }
    }
};
