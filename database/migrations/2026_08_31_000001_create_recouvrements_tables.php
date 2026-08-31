<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('recouvrements')) {
            DB::statement("CREATE TABLE recouvrements (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                recette_vehicule_id BIGINT UNSIGNED NULL,
                chauffeur_id BIGINT UNSIGNED NULL,
                chauffeur_nom VARCHAR(100) NULL,
                vehicule_id BIGINT UNSIGNED NULL,
                date_recette DATE NOT NULL,
                montant_verse INT NOT NULL DEFAULT 0,
                objectif INT NOT NULL DEFAULT 0,
                deficit INT NOT NULL DEFAULT 0,
                montant_recouvre INT NOT NULL DEFAULT 0,
                solde INT NOT NULL DEFAULT 0,
                statut VARCHAR(20) NOT NULL DEFAULT 'actif',
                motif VARCHAR(100) NULL,
                justification_valable TINYINT(1) NOT NULL DEFAULT 0,
                observations TEXT NULL,
                cree_par VARCHAR(100) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_chauffeur (chauffeur_id),
                INDEX idx_statut (statut),
                INDEX idx_date (date_recette)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        if (!Schema::hasTable('versements_recouvrement')) {
            DB::statement("CREATE TABLE versements_recouvrement (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                recouvrement_id BIGINT UNSIGNED NOT NULL,
                montant INT NOT NULL,
                date_versement DATE NOT NULL,
                mode_paiement VARCHAR(30) NULL,
                recu_par VARCHAR(100) NULL,
                observations TEXT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_recouvrement (recouvrement_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('versements_recouvrement');
        Schema::dropIfExists('recouvrements');
    }
};
