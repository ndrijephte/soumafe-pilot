<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Blocages catégorisés
        if (Schema::hasTable('blocages')) {
            if (!Schema::hasColumn('blocages', 'type')) {
                Schema::table('blocages', function (Blueprint $t) {
                    $t->string('type', 30)->default('technique')->after('motif');
                    $t->unsignedBigInteger('tache_liee_id')->nullable()->after('type');
                    $t->text('recommandation')->nullable()->after('leveur_pressenti');
                    $t->foreign('tache_liee_id')->references('id')->on('taches')->nullOnDelete();
                });
            }
        }

        // Commentaires contextuels
        if (Schema::hasTable('commentaires')) {
            if (!Schema::hasColumn('commentaires', 'contexte')) {
                Schema::table('commentaires', function (Blueprint $t) {
                    $t->string('contexte', 30)->nullable()->after('contenu');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('blocages') && Schema::hasColumn('blocages', 'type')) {
            Schema::table('blocages', function (Blueprint $t) {
                $t->dropForeign(['tache_liee_id']);
                $t->dropColumn(['type', 'tache_liee_id', 'recommandation']);
            });
        }
        if (Schema::hasTable('commentaires') && Schema::hasColumn('commentaires', 'contexte')) {
            Schema::table('commentaires', function (Blueprint $t) {
                $t->dropColumn('contexte');
            });
        }
    }
};
