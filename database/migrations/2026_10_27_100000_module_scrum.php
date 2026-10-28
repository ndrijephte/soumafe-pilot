<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * Module Scrum : backlog produit (user stories), sprint planning, daily scrum, équipe projet.
 * Cycle : Product Backlog → Sprint Backlog → Sprint Planning → Daily 1..N → Review → Rétrospective.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('projet_collaborateurs')) {
            Schema::create('projet_collaborateurs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('projet_id')->constrained('projets')->cascadeOnDelete();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->string('role_projet', 40)->default('developpeur');
                $t->date('date_ajout');
                $t->boolean('actif')->default(true);
                $t->timestamps();
                $t->unique(['projet_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('user_stories')) {
            Schema::create('user_stories', function (Blueprint $t) {
                $t->id();
                $t->foreignId('projet_id')->constrained('projets')->cascadeOnDelete();
                $t->string('numero', 20);
                $t->string('module', 150)->nullable();
                $t->text('intitule');
                $t->text('exigences')->nullable();
                $t->text('taches_techniques')->nullable();
                $t->string('priorite', 20)->default('moyenne');
                $t->string('statut', 20)->default('a_faire');
                $t->unsignedSmallInteger('effort_estime')->nullable();
                $t->timestamps();
                $t->index(['projet_id', 'module']);
            });
        }

        if (!Schema::hasTable('sprint_user_stories')) {
            Schema::create('sprint_user_stories', function (Blueprint $t) {
                $t->id();
                $t->foreignId('sprint_id')->constrained('sprints')->cascadeOnDelete();
                $t->foreignId('user_story_id')->constrained('user_stories')->cascadeOnDelete();
                $t->string('statut_sprint', 20)->default('a_faire');
                $t->timestamps();
                $t->unique(['sprint_id', 'user_story_id']);
            });
        }

        if (!Schema::hasTable('daily_scrums')) {
            Schema::create('daily_scrums', function (Blueprint $t) {
                $t->id();
                $t->foreignId('sprint_id')->constrained('sprints')->cascadeOnDelete();
                $t->unsignedSmallInteger('numero_jour');
                $t->date('date_reunion');
                $t->string('heure_debut', 10)->nullable();
                $t->string('heure_fin', 10)->nullable();
                $t->text('commentaire_general')->nullable();
                $t->text('observation')->nullable();
                $t->timestamps();
                $t->unique(['sprint_id', 'numero_jour']);
            });
        }

        if (!Schema::hasTable('daily_scrum_entrees')) {
            Schema::create('daily_scrum_entrees', function (Blueprint $t) {
                $t->id();
                $t->foreignId('daily_scrum_id')->constrained('daily_scrums')->cascadeOnDelete();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->text('taches_hier')->nullable();
                $t->text('taches_jour')->nullable();
                $t->string('duree_restante', 20)->nullable();
                $t->text('defis')->nullable();
                $t->timestamps();
                $t->unique(['daily_scrum_id', 'user_id']);
            });
        }

        Schema::table('taches', function (Blueprint $t) {
            if (!Schema::hasColumn('taches', 'user_story_id')) {
                $t->foreignId('user_story_id')->nullable()->after('sprint_id')->constrained('user_stories')->nullOnDelete();
            }
            if (!Schema::hasColumn('taches', 'teste')) {
                $t->boolean('teste')->default(false)->after('statut');
            }
        });

        // Équipe initiale de chaque projet : son responsable et les personnes ayant des tâches dans ses sprints
        $now = now();
        foreach (DB::table('projets')->get(['id', 'responsable_projet_id', 'created_at']) as $p) {
            $membres = DB::table('taches')->where('projet_id', $p->id)->whereNotNull('sprint_id')->distinct()->pluck('user_id')
                ->mapWithKeys(fn($u) => [$u => 'developpeur']);
            if ($p->responsable_projet_id) $membres[$p->responsable_projet_id] = 'responsable';
            foreach ($membres as $userId => $role) {
                DB::table('projet_collaborateurs')->insertOrIgnore([
                    'projet_id' => $p->id, 'user_id' => $userId, 'role_projet' => $role,
                    'date_ajout' => ($p->created_at ? substr($p->created_at, 0, 10) : $now->toDateString()),
                    'actif' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('taches', function (Blueprint $t) {
            if (Schema::hasColumn('taches', 'user_story_id')) $t->dropConstrainedForeignId('user_story_id');
            if (Schema::hasColumn('taches', 'teste')) $t->dropColumn('teste');
        });
        Schema::dropIfExists('daily_scrum_entrees');
        Schema::dropIfExists('daily_scrums');
        Schema::dropIfExists('sprint_user_stories');
        Schema::dropIfExists('user_stories');
        Schema::dropIfExists('projet_collaborateurs');
    }
};
