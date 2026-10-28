<?php

use App\Services\TexteRiche;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Publication du rapport de Daily Scrum : une fois publié, seul un pilote (administrateur
 * ou chargé des opérations) peut le modifier. Normalise aussi l'encodage des textes déjà saisis
 * (ex. « s&#039;est » enregistré au lieu de « s'est »).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('daily_scrums', 'publie_le')) {
            Schema::table('daily_scrums', function (Blueprint $t) {
                $t->timestamp('publie_le')->nullable();
                $t->foreignId('publie_par')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        foreach (['daily_scrums' => ['commentaire_general', 'observation'], 'daily_scrum_entrees' => ['taches_hier', 'taches_jour', 'defis']] as $table => $champs) {
            foreach (DB::table($table)->select(array_merge(['id'], $champs))->get() as $ligne) {
                $maj = [];
                foreach ($champs as $c) {
                    if ($ligne->$c !== null && ($v = TexteRiche::nettoyer($ligne->$c)) !== $ligne->$c) $maj[$c] = $v;
                }
                if ($maj) DB::table($table)->where('id', $ligne->id)->update($maj);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('daily_scrums', 'publie_le')) {
            Schema::table('daily_scrums', function (Blueprint $t) {
                $t->dropConstrainedForeignId('publie_par');
                $t->dropColumn('publie_le');
            });
        }
    }
};
