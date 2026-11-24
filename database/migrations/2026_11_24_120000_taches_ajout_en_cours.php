<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * Tâche ajoutée à un sprint déjà en cours (hors planification initiale) : date de l'ajout, pour la reconnaître
 * dans le Sprint Planning, Mes tâches et les rapports.
 * Reprise : dans les sprints en cours, les tâches arrivées après le premier lot de planification (plus de 30 minutes
 * après la première tâche du sprint, et après son début) sont marquées à leur date de création.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('taches', 'ajoutee_en_cours_le')) {
            Schema::table('taches', fn(Blueprint $t) => $t->dateTime('ajoutee_en_cours_le')->nullable()->after('sprint_id'));
        }
        foreach (DB::table('sprints')->where('statut', 'en_cours')->get(['id', 'date_debut']) as $sp) {
            $premiere = DB::table('taches')->where('sprint_id', $sp->id)->whereNull('deleted_at')->min('created_at');
            if (!$premiere) continue;
            $seuil = max(date('Y-m-d H:i:s', strtotime($premiere) + 1800), $sp->date_debut . ' 23:59:59');
            DB::table('taches')->where('sprint_id', $sp->id)->whereNull('ajoutee_en_cours_le')->where('created_at', '>', $seuil)
                ->update(['ajoutee_en_cours_le' => DB::raw('created_at')]);
        }
    }

    public function down(): void
    {
        Schema::table('taches', fn(Blueprint $t) => $t->dropColumn('ajoutee_en_cours_le'));
    }
};
