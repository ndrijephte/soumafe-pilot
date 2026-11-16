<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * Sprint Backlog et Product Backlog :
 * - instantané des user stories à la clôture du sprint (l'historique ne bouge plus) ;
 * - archivage d'une user story engagée dans un sprint clôturé (au lieu de la supprimer) ;
 * - date et nature de la dernière modification du contenu d'une story (signalement en cours de sprint).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sprint_user_stories', function (Blueprint $t) {
            if (!Schema::hasColumn('sprint_user_stories', 'instantane')) $t->json('instantane')->nullable()->after('statut_sprint');
            if (!Schema::hasColumn('sprint_user_stories', 'fige_le')) $t->timestamp('fige_le')->nullable()->after('instantane');
        });
        Schema::table('user_stories', function (Blueprint $t) {
            if (!Schema::hasColumn('user_stories', 'contenu_modifie_le')) $t->timestamp('contenu_modifie_le')->nullable();
            if (!Schema::hasColumn('user_stories', 'contenu_modifie_champs')) $t->string('contenu_modifie_champs', 255)->nullable();
            if (!Schema::hasColumn('user_stories', 'archivee_le')) $t->timestamp('archivee_le')->nullable();
            if (!Schema::hasColumn('user_stories', 'archive_motif')) $t->string('archive_motif', 500)->nullable();
        });

        // Sprints déjà clôturés : figés avec les valeurs actuelles des stories
        $lignes = DB::table('sprint_user_stories as p')
            ->join('sprints as s', 's.id', '=', 'p.sprint_id')
            ->join('user_stories as u', 'u.id', '=', 'p.user_story_id')
            ->where('s.statut', 'termine')->whereNull('p.fige_le')
            ->get(['p.id', 'u.numero', 'u.intitule', 'u.module', 'u.epic', 'u.profil', 'u.priorite_moscow', 'u.effort_estime', 'u.statut']);
        foreach ($lignes as $l) {
            DB::table('sprint_user_stories')->where('id', $l->id)->update([
                'instantane' => json_encode([
                    'numero' => $l->numero, 'intitule' => $l->intitule, 'module' => $l->module, 'epic' => $l->epic, 'profil' => $l->profil,
                    'priorite_moscow' => $l->priorite_moscow, 'effort_estime' => $l->effort_estime === null ? null : (int) $l->effort_estime, 'statut' => $l->statut,
                ], JSON_UNESCAPED_UNICODE),
                'fige_le' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('sprint_user_stories', fn(Blueprint $t) => $t->dropColumn(['instantane', 'fige_le']));
        Schema::table('user_stories', fn(Blueprint $t) => $t->dropColumn(['contenu_modifie_le', 'contenu_modifie_champs', 'archivee_le', 'archive_motif']));
    }
};
