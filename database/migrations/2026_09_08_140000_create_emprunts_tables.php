<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table emprunts
        DB::statement("CREATE TABLE emprunts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            montant INT UNSIGNED NOT NULL,
            date_emprunt DATE NOT NULL,
            preteur VARCHAR(150) NOT NULL COMMENT 'Nom du prêteur',
            motif VARCHAR(500) NULL,
            source_type VARCHAR(30) NOT NULL DEFAULT 'autre' COMMENT 'panne, penalite, maintenance, autre',
            source_id BIGINT UNSIGNED NULL COMMENT 'ID de la panne/pénalité/maintenance liée',
            vehicule_id BIGINT UNSIGNED NULL,
            montant_rembourse INT UNSIGNED NOT NULL DEFAULT 0,
            statut VARCHAR(20) NOT NULL DEFAULT 'en_cours' COMMENT 'en_cours, rembourse',
            cree_par VARCHAR(100) NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            CONSTRAINT fk_emprunt_vehicule FOREIGN KEY (vehicule_id) REFERENCES vehicules(id) ON DELETE SET NULL
        )");

        // Table remboursements_emprunt
        DB::statement("CREATE TABLE remboursements_emprunt (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            emprunt_id BIGINT UNSIGNED NOT NULL,
            montant INT UNSIGNED NOT NULL,
            date_remboursement DATE NOT NULL,
            mode_paiement VARCHAR(30) NULL,
            observations VARCHAR(500) NULL,
            cree_par VARCHAR(100) NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            CONSTRAINT fk_remb_emprunt FOREIGN KEY (emprunt_id) REFERENCES emprunts(id) ON DELETE CASCADE
        )");

        // Ajouter emprunt_id sur pannes, paiements_penalites, depenses
        DB::statement("ALTER TABLE pannes ADD COLUMN emprunt_id BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE pannes ADD CONSTRAINT fk_panne_emprunt FOREIGN KEY (emprunt_id) REFERENCES emprunts(id) ON DELETE SET NULL");

        DB::statement("ALTER TABLE paiements_penalites ADD COLUMN emprunt_id BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE paiements_penalites ADD CONSTRAINT fk_paie_penal_emprunt FOREIGN KEY (emprunt_id) REFERENCES emprunts(id) ON DELETE SET NULL");

        DB::statement("ALTER TABLE depenses ADD COLUMN emprunt_id BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE depenses ADD CONSTRAINT fk_depense_emprunt FOREIGN KEY (emprunt_id) REFERENCES emprunts(id) ON DELETE SET NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE depenses DROP FOREIGN KEY fk_depense_emprunt");
        DB::statement("ALTER TABLE depenses DROP COLUMN emprunt_id");
        DB::statement("ALTER TABLE paiements_penalites DROP FOREIGN KEY fk_paie_penal_emprunt");
        DB::statement("ALTER TABLE paiements_penalites DROP COLUMN emprunt_id");
        DB::statement("ALTER TABLE pannes DROP FOREIGN KEY fk_panne_emprunt");
        DB::statement("ALTER TABLE pannes DROP COLUMN emprunt_id");
        Schema::dropIfExists('remboursements_emprunt');
        Schema::dropIfExists('emprunts');
    }
};
