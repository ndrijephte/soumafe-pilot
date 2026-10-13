<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    /** Dépenses et interventions antérieures : leur montant sort de la caisse à leur date. */
    public function up(): void
    {
        if (!Schema::hasTable('mouvements_caisse')) return;
        $now = now();

        if (Schema::hasTable('depenses')) {
            foreach (DB::table('depenses')->whereNotIn('id', DB::table('mouvements_caisse')->where('source_type', 'depense')->pluck('source_id'))->get() as $d) {
                DB::table('mouvements_caisse')->insert([
                    'caisse_code' => $d->caisse_source ?: 'generale', 'sens' => 'sortie', 'montant' => $d->montant,
                    'date_mouvement' => $d->date_depense, 'source_type' => 'depense', 'source_id' => $d->id,
                    'libelle' => 'Dépense — ' . ucfirst(str_replace('_', ' ', $d->type_depense)),
                    'cree_par' => 'reprise des données', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('pannes')) {
            $pannes = DB::table('pannes')->leftJoin('vehicules', 'vehicules.id', '=', 'pannes.vehicule_id')
                ->where('pannes.cout_reel', '>', 0)
                ->whereNotIn('pannes.id', DB::table('mouvements_caisse')->where('source_type', 'panne')->pluck('source_id'))
                ->select('pannes.*', 'vehicules.immatriculation')->get();
            foreach ($pannes as $p) {
                DB::table('mouvements_caisse')->insert([
                    'caisse_code' => 'generale', 'sens' => 'sortie', 'montant' => $p->cout_reel,
                    'date_mouvement' => $p->date_cloture ?? $p->date_debut_intervention ?? $p->date_declaration,
                    'source_type' => 'panne', 'source_id' => $p->id, 'libelle' => 'Intervention panne — ' . ($p->immatriculation ?? ''),
                    'cree_par' => 'reprise des données', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('mouvements_caisse')->where('cree_par', 'reprise des données')->whereIn('source_type', ['depense', 'panne'])->delete();
    }
};
