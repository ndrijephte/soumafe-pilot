-- ============================================================
-- NETTOYAGE SOUMAFE PILOT — Données opérationnelles uniquement
-- Conserve : users, projets, poles, domaines, services, rôles,
--            profils_mesure, types_blocage, types_evenement,
--            jours_feries, paramètres admin
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Tâches et dépendances
TRUNCATE TABLE confirmations_taches;
TRUNCATE TABLE blocages;
TRUNCATE TABLE commentaires;
TRUNCATE TABLE taches;

-- Sprints
TRUNCATE TABLE sprints;

-- Comptes rendus
TRUNCATE TABLE comptes_rendus;

-- Suivi quotidien
TRUNCATE TABLE jours_saisie;

-- Arbitrages
TRUNCATE TABLE arbitrages_t3;

-- Événements
TRUNCATE TABLE evenements;

-- Absences
TRUNCATE TABLE absences;

-- Notifications
TRUNCATE TABLE notifications_app;

-- Objectifs & registres
TRUNCATE TABLE registre_entrees;
TRUNCATE TABLE registres;
TRUNCATE TABLE objectifs;

-- Liaison projet-user (responsables projet)
TRUNCATE TABLE projet_user;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- RÉSULTAT : base propre, utilisateurs et config admin intacts
-- ============================================================
