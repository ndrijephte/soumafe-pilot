<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('recouvrements')) return;

        // 1. Supprimer les doublons par chauffeur_nom + vehicule_id + date_recette
        //    Garder le plus ancien (id le plus petit), supprimer les autres SEULEMENT si aucun versement
        $doublons = DB::select("
            SELECT r.id
            FROM recouvrements r
            INNER JOIN (
                SELECT chauffeur_nom, vehicule_id, date_recette, MIN(id) as min_id
                FROM recouvrements
                WHERE penalite_id IS NULL
                GROUP BY chauffeur_nom, vehicule_id, date_recette
                HAVING COUNT(*) > 1
            ) dup ON r.chauffeur_nom = dup.chauffeur_nom
                 AND r.vehicule_id = dup.vehicule_id
                 AND r.date_recette = dup.date_recette
                 AND r.id != dup.min_id
                 AND r.penalite_id IS NULL
        ");

        $idsToDelete = [];
        foreach ($doublons as $d) {
            // Ne supprimer que si aucun versement lié
            $hasVersements = DB::table('versements_recouvrement')
                ->where('recouvrement_id', $d->id)
                ->exists();
            if (!$hasVersements) {
                $idsToDelete[] = $d->id;
            }
        }

        if (!empty($idsToDelete)) {
            DB::table('recouvrements')->whereIn('id', $idsToDelete)->delete();
        }

        // 2. Supprimer les doublons par penalite_id (garder le plus ancien)
        if (Schema::hasColumn('recouvrements', 'penalite_id')) {
            $doublonsPenal = DB::select("
                SELECT r.id
                FROM recouvrements r
                INNER JOIN (
                    SELECT penalite_id, MIN(id) as min_id
                    FROM recouvrements
                    WHERE penalite_id IS NOT NULL
                    GROUP BY penalite_id
                    HAVING COUNT(*) > 1
                ) dup ON r.penalite_id = dup.penalite_id
                     AND r.id != dup.min_id
            ");

            $idsPenalToDelete = [];
            foreach ($doublonsPenal as $d) {
                $hasVersements = DB::table('versements_recouvrement')
                    ->where('recouvrement_id', $d->id)
                    ->exists();
                if (!$hasVersements) {
                    $idsPenalToDelete[] = $d->id;
                }
            }

            if (!empty($idsPenalToDelete)) {
                DB::table('recouvrements')->whereIn('id', $idsPenalToDelete)->delete();
            }
        }
    }

    public function down(): void
    {
        // Pas de rollback pour une purge de doublons
    }
};
