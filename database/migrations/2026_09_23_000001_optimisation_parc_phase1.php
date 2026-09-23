<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════
        //  1. ENRICHISSEMENT TABLE vehicules
        // ═══════════════════════════════════════════════════════
        if (Schema::hasTable('vehicules')) {
            if (!Schema::hasColumn('vehicules', 'numero_chassis')) {
                DB::statement("ALTER TABLE vehicules ADD COLUMN numero_chassis VARCHAR(30) NULL AFTER modele");
            }
            if (!Schema::hasColumn('vehicules', 'nombre_places')) {
                DB::statement("ALTER TABLE vehicules ADD COLUMN nombre_places TINYINT UNSIGNED NULL AFTER numero_chassis");
            }
            if (!Schema::hasColumn('vehicules', 'proprietaire')) {
                DB::statement("ALTER TABLE vehicules ADD COLUMN proprietaire VARCHAR(100) NULL AFTER nombre_places");
            }
            if (!Schema::hasColumn('vehicules', 'photo')) {
                DB::statement("ALTER TABLE vehicules ADD COLUMN photo VARCHAR(255) NULL AFTER proprietaire");
            }
        }

        // ═══════════════════════════════════════════════════════
        //  2. ENRICHISSEMENT TABLE chauffeurs
        // ═══════════════════════════════════════════════════════
        if (Schema::hasTable('chauffeurs')) {
            if (!Schema::hasColumn('chauffeurs', 'date_naissance')) {
                DB::statement("ALTER TABLE chauffeurs ADD COLUMN date_naissance DATE NULL AFTER prenom");
            }
            if (!Schema::hasColumn('chauffeurs', 'adresse')) {
                DB::statement("ALTER TABLE chauffeurs ADD COLUMN adresse VARCHAR(200) NULL AFTER date_naissance");
            }
            if (!Schema::hasColumn('chauffeurs', 'numero_cni')) {
                DB::statement("ALTER TABLE chauffeurs ADD COLUMN numero_cni VARCHAR(30) NULL AFTER adresse");
            }
            if (!Schema::hasColumn('chauffeurs', 'date_embauche')) {
                DB::statement("ALTER TABLE chauffeurs ADD COLUMN date_embauche DATE NULL AFTER numero_cni");
            }
            if (!Schema::hasColumn('chauffeurs', 'photo')) {
                DB::statement("ALTER TABLE chauffeurs ADD COLUMN photo VARCHAR(255) NULL AFTER date_embauche");
            }
            if (!Schema::hasColumn('chauffeurs', 'personne_contact')) {
                DB::statement("ALTER TABLE chauffeurs ADD COLUMN personne_contact VARCHAR(150) NULL AFTER photo");
            }
        }

        // ═══════════════════════════════════════════════════════
        //  3. ENRICHISSEMENT TABLE depenses
        // ═══════════════════════════════════════════════════════
        if (Schema::hasTable('depenses')) {
            if (!Schema::hasColumn('depenses', 'fournisseur')) {
                DB::statement("ALTER TABLE depenses ADD COLUMN fournisseur VARCHAR(150) NULL AFTER type_depense");
            }
            if (!Schema::hasColumn('depenses', 'moyen_paiement')) {
                DB::statement("ALTER TABLE depenses ADD COLUMN moyen_paiement VARCHAR(30) NULL AFTER montant");
            }
            if (!Schema::hasColumn('depenses', 'justificatif')) {
                DB::statement("ALTER TABLE depenses ADD COLUMN justificatif VARCHAR(255) NULL AFTER moyen_paiement");
            }
            if (!Schema::hasColumn('depenses', 'caisse_source')) {
                DB::statement("ALTER TABLE depenses ADD COLUMN caisse_source VARCHAR(30) NULL DEFAULT 'generale' AFTER justificatif");
            }
        }

        // ═══════════════════════════════════════════════════════
        //  4. ENRICHISSEMENT TABLE emprunts
        // ═══════════════════════════════════════════════════════
        if (Schema::hasTable('emprunts')) {
            if (!Schema::hasColumn('emprunts', 'reference')) {
                DB::statement("ALTER TABLE emprunts ADD COLUMN reference VARCHAR(30) NULL AFTER id");
            }
            if (!Schema::hasColumn('emprunts', 'date_echeance')) {
                DB::statement("ALTER TABLE emprunts ADD COLUMN date_echeance DATE NULL AFTER date_emprunt");
            }
            if (!Schema::hasColumn('emprunts', 'modalites')) {
                DB::statement("ALTER TABLE emprunts ADD COLUMN modalites VARCHAR(500) NULL AFTER motif");
            }
        }

        // ═══════════════════════════════════════════════════════
        //  5. ENRICHISSEMENT TABLE pannes
        // ═══════════════════════════════════════════════════════
        if (Schema::hasTable('pannes')) {
            if (!Schema::hasColumn('pannes', 'fournisseur_pieces')) {
                DB::statement("ALTER TABLE pannes ADD COLUMN fournisseur_pieces VARCHAR(150) NULL AFTER cout_reel");
            }
        }

        // ═══════════════════════════════════════════════════════
        //  6. TABLE planning_journalier (NOUVELLE)
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('planning_journalier')) {
            DB::statement("CREATE TABLE planning_journalier (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                date_jour DATE NOT NULL,
                vehicule_id BIGINT UNSIGNED NOT NULL,
                chauffeur_id BIGINT UNSIGNED NULL,
                chauffeur_nom VARCHAR(100) NULL,
                action VARCHAR(30) NOT NULL DEFAULT 'en_service' COMMENT 'en_service, repos, indisponible, remplacement, conge, suspension, autre',
                motif VARCHAR(200) NULL,
                chauffeur_remplacant_id BIGINT UNSIGNED NULL,
                chauffeur_remplacant_nom VARCHAR(100) NULL,
                observations TEXT NULL,
                cree_par VARCHAR(100) NULL,
                modifie_par VARCHAR(100) NULL,
                modifie_le TIMESTAMP NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uk_planning_jour (date_jour, vehicule_id, chauffeur_id),
                INDEX idx_planning_date (date_jour),
                INDEX idx_planning_vehicule (vehicule_id),
                INDEX idx_planning_chauffeur (chauffeur_id),
                CONSTRAINT fk_planning_vehicule FOREIGN KEY (vehicule_id) REFERENCES vehicules(id) ON DELETE CASCADE,
                CONSTRAINT fk_planning_chauffeur FOREIGN KEY (chauffeur_id) REFERENCES chauffeurs(id) ON DELETE SET NULL,
                CONSTRAINT fk_planning_remplacant FOREIGN KEY (chauffeur_remplacant_id) REFERENCES chauffeurs(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // ═══════════════════════════════════════════════════════
        //  7. TABLE caisses (NOUVELLE)
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('caisses')) {
            DB::statement("CREATE TABLE caisses (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(30) NOT NULL UNIQUE,
                libelle VARCHAR(100) NOT NULL,
                description VARCHAR(255) NULL,
                solde_initial INT NOT NULL DEFAULT 0,
                actif TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Caisses par défaut
            $now = now();
            DB::table('caisses')->insert([
                ['code' => 'pointage', 'libelle' => 'Caisse Pointage', 'description' => 'Alimentée par les pointages des recettes', 'solde_initial' => 0, 'actif' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'generale', 'libelle' => 'Caisse Générale', 'description' => 'Caisse principale — recettes, dépenses, remboursements', 'solde_initial' => 0, 'actif' => 1, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // ═══════════════════════════════════════════════════════
        //  8. TABLE mouvements_caisse (NOUVELLE)
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('mouvements_caisse')) {
            DB::statement("CREATE TABLE mouvements_caisse (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                caisse_code VARCHAR(30) NOT NULL,
                sens ENUM('entree','sortie') NOT NULL,
                montant INT UNSIGNED NOT NULL,
                date_mouvement DATE NOT NULL,
                source_type VARCHAR(40) NOT NULL COMMENT 'recette, pointage, recouvrement, depense, pret_accorde, remboursement_pret, emprunt, remboursement_emprunt, penalite, maintenance, panne, autre_ressource',
                source_id BIGINT UNSIGNED NULL,
                libelle VARCHAR(200) NOT NULL,
                reference VARCHAR(100) NULL,
                cree_par VARCHAR(100) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_mcaisse_code (caisse_code),
                INDEX idx_mcaisse_date (date_mouvement),
                INDEX idx_mcaisse_source (source_type, source_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // ═══════════════════════════════════════════════════════
        //  9. TABLE prets_accordes (NOUVELLE)
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('prets_accordes')) {
            DB::statement("CREATE TABLE prets_accordes (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                reference VARCHAR(30) NULL,
                montant INT UNSIGNED NOT NULL,
                date_pret DATE NOT NULL,
                beneficiaire VARCHAR(150) NOT NULL COMMENT 'Chauffeur ou tiers',
                chauffeur_id BIGINT UNSIGNED NULL,
                motif VARCHAR(500) NULL,
                caisse_source VARCHAR(30) NOT NULL DEFAULT 'generale',
                montant_rembourse INT UNSIGNED NOT NULL DEFAULT 0,
                statut VARCHAR(20) NOT NULL DEFAULT 'en_cours' COMMENT 'en_cours, rembourse, irrécouvrable',
                date_echeance DATE NULL,
                modalites VARCHAR(500) NULL,
                cree_par VARCHAR(100) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_pret_chauffeur (chauffeur_id),
                INDEX idx_pret_statut (statut),
                CONSTRAINT fk_pret_chauffeur FOREIGN KEY (chauffeur_id) REFERENCES chauffeurs(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // ═══════════════════════════════════════════════════════
        //  10. TABLE remboursements_prets (NOUVELLE)
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('remboursements_prets')) {
            DB::statement("CREATE TABLE remboursements_prets (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                pret_accorde_id BIGINT UNSIGNED NOT NULL,
                montant INT UNSIGNED NOT NULL,
                date_remboursement DATE NOT NULL,
                mode_paiement VARCHAR(30) NULL,
                caisse_destination VARCHAR(30) NOT NULL DEFAULT 'generale',
                observations VARCHAR(500) NULL,
                cree_par VARCHAR(100) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_rembpret_pret FOREIGN KEY (pret_accorde_id) REFERENCES prets_accordes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // ═══════════════════════════════════════════════════════
        //  11. TABLE autres_ressources (NOUVELLE)
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('autres_ressources')) {
            DB::statement("CREATE TABLE autres_ressources (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                date_ressource DATE NOT NULL,
                type_ressource VARCHAR(40) NOT NULL COMMENT 'don, vente, remboursement_fournisseur, autre',
                montant INT UNSIGNED NOT NULL,
                source VARCHAR(150) NULL COMMENT 'Nom du donateur, acheteur, fournisseur...',
                description VARCHAR(500) NULL,
                caisse_destination VARCHAR(30) NOT NULL DEFAULT 'generale',
                vehicule_id BIGINT UNSIGNED NULL,
                cree_par VARCHAR(100) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_ar_date (date_ressource),
                INDEX idx_ar_type (type_ressource),
                CONSTRAINT fk_ar_vehicule FOREIGN KEY (vehicule_id) REFERENCES vehicules(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // ═══════════════════════════════════════════════════════
        //  12. SEED TYPES PARC — actions planning + types ressources
        // ═══════════════════════════════════════════════════════
        if (Schema::hasTable('types_parc')) {
            // Actions planning
            $actions = [
                ['en_service', 'En service', 1],
                ['repos', 'Repos', 2],
                ['indisponible', 'Indisponible', 3],
                ['remplacement', 'Remplacement', 4],
                ['conge', 'Congé', 5],
                ['suspension', 'Suspension', 6],
                ['autre', 'Autre', 99],
            ];
            foreach ($actions as [$code, $libelle, $ordre]) {
                $exists = DB::table('types_parc')->where('categorie', 'action_planning')->where('code', $code)->exists();
                if (!$exists) {
                    DB::table('types_parc')->insert([
                        'categorie' => 'action_planning', 'code' => $code, 'libelle' => $libelle,
                        'actif' => true, 'ordre' => $ordre, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            // Types autres ressources
            $typesRes = [
                ['don', 'Don', 1],
                ['vente', 'Vente', 2],
                ['remboursement_fournisseur', 'Remboursement fournisseur', 3],
                ['autre', 'Autre', 99],
            ];
            foreach ($typesRes as [$code, $libelle, $ordre]) {
                $exists = DB::table('types_parc')->where('categorie', 'autre_ressource')->where('code', $code)->exists();
                if (!$exists) {
                    DB::table('types_parc')->insert([
                        'categorie' => 'autre_ressource', 'code' => $code, 'libelle' => $libelle,
                        'actif' => true, 'ordre' => $ordre, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }

        // ═══════════════════════════════════════════════════════
        //  13. ENRICHISSEMENT recettes_vehicules — statut_motif pour workflow 2 temps
        // ═══════════════════════════════════════════════════════
        if (Schema::hasTable('recettes_vehicules')) {
            if (!Schema::hasColumn('recettes_vehicules', 'statut_motif')) {
                DB::statement("ALTER TABLE recettes_vehicules ADD COLUMN statut_motif VARCHAR(20) NULL DEFAULT NULL COMMENT 'en_attente, valide, invalide' AFTER justification_valable");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('autres_ressources');
        Schema::dropIfExists('remboursements_prets');
        Schema::dropIfExists('prets_accordes');
        Schema::dropIfExists('mouvements_caisse');
        Schema::dropIfExists('caisses');
        Schema::dropIfExists('planning_journalier');

        // Rollback enrichissements
        $drops = [
            ['vehicules', ['numero_chassis', 'nombre_places', 'proprietaire', 'photo']],
            ['chauffeurs', ['date_naissance', 'adresse', 'numero_cni', 'date_embauche', 'photo', 'personne_contact']],
            ['depenses', ['fournisseur', 'moyen_paiement', 'justificatif', 'caisse_source']],
            ['emprunts', ['reference', 'date_echeance', 'modalites']],
            ['pannes', ['fournisseur_pieces']],
            ['recettes_vehicules', ['statut_motif']],
        ];
        foreach ($drops as [$table, $cols]) {
            foreach ($cols as $col) {
                if (Schema::hasColumn($table, $col)) {
                    DB::statement("ALTER TABLE {$table} DROP COLUMN {$col}");
                }
            }
        }

        // Supprimer types_parc ajoutés
        DB::table('types_parc')->whereIn('categorie', ['action_planning', 'autre_ressource'])->delete();
    }
};
