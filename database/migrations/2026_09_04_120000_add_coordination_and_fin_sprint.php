<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('types_reunion')) return;

        // 1. Ajouter type "Coordination" (hebdomadaire) si absent
        if (!DB::table('types_reunion')->where('code', 'coordination')->exists()) {
            DB::table('types_reunion')->insert([
                'code' => 'coordination', 'libelle' => 'Coordination hebdomadaire',
                'recurrence' => 'hebdomadaire', 'actif' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // 2. Passer retro/sprint_review/planification en fin_sprint
        DB::table('types_reunion')
            ->whereIn('code', ['retrospective', 'sprint_review', 'planification'])
            ->update(['recurrence' => 'fin_sprint']);

        // 3. Ajouter les mêmes rôles que daily_scrum pour coordination
        $coordId = DB::table('types_reunion')->where('code', 'coordination')->value('id');
        if ($coordId && DB::table('roles_reunion')->where('type_reunion_id', $coordId)->count() === 0) {
            DB::table('roles_reunion')->insert([
                ['type_reunion_id' => $coordId, 'code' => 'facilitateur', 'libelle' => 'Facilitateur', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['type_reunion_id' => $coordId, 'code' => 'scribe', 'libelle' => 'Scribe', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['type_reunion_id' => $coordId, 'code' => 'gardien_temps', 'libelle' => 'Gardien du temps', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['type_reunion_id' => $coordId, 'code' => 'observateur', 'libelle' => 'Observateur', 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        DB::table('types_reunion')
            ->whereIn('code', ['retrospective', 'sprint_review', 'planification'])
            ->update(['recurrence' => 'hebdomadaire']);
        $coordId = DB::table('types_reunion')->where('code', 'coordination')->value('id');
        if ($coordId) {
            DB::table('roles_reunion')->where('type_reunion_id', $coordId)->delete();
            DB::table('types_reunion')->where('id', $coordId)->delete();
        }
    }
};
