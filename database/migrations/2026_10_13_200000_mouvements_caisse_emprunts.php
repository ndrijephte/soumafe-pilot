<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    /** Emprunts antérieurs : l'argent reçu entre en caisse générale, chaque remboursement en sort. */
    public function up(): void
    {
        if (!Schema::hasTable('mouvements_caisse') || !Schema::hasTable('emprunts')) return;
        $now = now();

        foreach (DB::table('emprunts')->get() as $e) {
            if (DB::table('mouvements_caisse')->where('source_type', 'emprunt')->where('source_id', $e->id)->exists()) continue;
            DB::table('mouvements_caisse')->insert([
                'caisse_code' => 'generale', 'sens' => 'entree', 'montant' => $e->montant, 'date_mouvement' => $e->date_emprunt,
                'source_type' => 'emprunt', 'source_id' => $e->id, 'libelle' => "Emprunt reçu — {$e->preteur}",
                'reference' => $e->reference, 'cree_par' => 'reprise des données', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        if (!Schema::hasTable('remboursements_emprunt')) return;
        foreach (DB::table('remboursements_emprunt')->join('emprunts', 'emprunts.id', '=', 'remboursements_emprunt.emprunt_id')
                     ->select('remboursements_emprunt.*', 'emprunts.preteur', 'emprunts.reference')->get() as $rb) {
            if (DB::table('mouvements_caisse')->where('source_type', 'remboursement_emprunt')->where('source_id', $rb->id)->exists()) continue;
            DB::table('mouvements_caisse')->insert([
                'caisse_code' => 'generale', 'sens' => 'sortie', 'montant' => $rb->montant, 'date_mouvement' => $rb->date_remboursement,
                'source_type' => 'remboursement_emprunt', 'source_id' => $rb->id, 'libelle' => "Remboursement emprunt — {$rb->preteur}",
                'reference' => $rb->reference, 'cree_par' => 'reprise des données', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('mouvements_caisse')->where('cree_par', 'reprise des données')
            ->whereIn('source_type', ['emprunt', 'remboursement_emprunt'])->delete();
    }
};
