<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        // 1. Table paiements_penalites (historique échelonnement)
        if (!Schema::hasTable('paiements_penalites')) {
            DB::statement("CREATE TABLE paiements_penalites (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                penalite_id BIGINT UNSIGNED NOT NULL,
                montant INT UNSIGNED NOT NULL,
                date_paiement DATE NOT NULL,
                mode_paiement VARCHAR(30) NULL,
                recu_par VARCHAR(100) NULL,
                observations VARCHAR(500) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                FOREIGN KEY (penalite_id) REFERENCES penalites(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // 2. Ajouter montant_paye à penalites (suivi cumul)
        if (!Schema::hasColumn('penalites', 'montant_paye')) {
            DB::statement("ALTER TABLE penalites ADD COLUMN montant_paye INT UNSIGNED NOT NULL DEFAULT 0 AFTER montant");
        }

        // 3. Ajouter statut 'en_cours' — la colonne existe déjà, on change juste les valeurs possibles
        // Pas de contrainte ENUM à modifier, c'est du VARCHAR validé côté PHP

        // 4. Ajouter penalite_id et type_recouvrement à recouvrements
        if (Schema::hasTable('recouvrements')) {
            if (!Schema::hasColumn('recouvrements', 'penalite_id')) {
                DB::statement("ALTER TABLE recouvrements ADD COLUMN penalite_id BIGINT UNSIGNED NULL AFTER recette_vehicule_id");
                DB::statement("ALTER TABLE recouvrements ADD FOREIGN KEY (penalite_id) REFERENCES penalites(id) ON DELETE SET NULL");
            }
            if (!Schema::hasColumn('recouvrements', 'type_recouvrement')) {
                DB::statement("ALTER TABLE recouvrements ADD COLUMN type_recouvrement VARCHAR(20) NOT NULL DEFAULT 'recette' AFTER statut");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_penalites');
        if (Schema::hasColumn('penalites', 'montant_paye')) {
            DB::statement("ALTER TABLE penalites DROP COLUMN montant_paye");
        }
    }
};
