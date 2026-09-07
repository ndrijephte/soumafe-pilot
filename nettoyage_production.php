<?php
/**
 * SOUMAFE PILOT — Nettoyage base de données pour mise en production (CLI)
 *
 * CONSERVE : users, titres_poste, types_reunion, roles_reunion, motifs_recette,
 *            jours_feries, jours_ouvrables_speciaux, types_penalite, types_maintenance,
 *            types_depense, vehicules, chauffeurs, chauffeur_vehicule, migrations, password_resets
 *
 * PURGE : toutes les données opérationnelles
 *
 * USAGE : cd /home/user/public_html && php nettoyage_production.php
 *         puis supprimer le fichier : rm nettoyage_production.php
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\{DB, Schema};

echo "\n========================================\n";
echo " SOUMAFE PILOT — Nettoyage production\n";
echo "========================================\n\n";

// Confirmation CLI
echo "ATTENTION : Cette action est irréversible.\n";
echo "Tapez 'oui' pour confirmer : ";
$handle = fopen("php://stdin", "r");
$input = trim(fgets($handle));
fclose($handle);

if ($input !== 'oui') {
    echo "\nAnnulé.\n";
    exit(1);
}

echo "\n--- Désactivation FK checks ---\n";
DB::statement('SET FOREIGN_KEY_CHECKS = 0');

$tablesPurge = [
    'versements_recouvrement',
    'recouvrements',
    'paiements_penalites',
    'versements_penalite',
    'resultats_cles',
    'historique_avancement',
    'objectif_tache',
    'objectifs',
    'affectations_reunion',
    'participants_reunion',
    'alertes_parc',
    'audit_logs',
    'notifications_app',
    'observations',
    'pointages',
    'autres_depenses',
    'maintenances',
    'penalites',
    'recettes',
    'absences',
    'commentaires',
    'decisions',
    'comptes_rendus',
    'saisies',
    'evenements',
    'actions_engagees',
    'types_evenement',
    'types_blocage',
    'intervenants',
    'taches',
    'sprints',
    'projets',
];

$purged = 0;
$skipped = 0;

echo "\n--- Purge des données opérationnelles ---\n";
foreach ($tablesPurge as $table) {
    if (Schema::hasTable($table)) {
        $count = DB::table($table)->count();
        DB::table($table)->truncate();
        echo "  [OK] $table — $count lignes supprimées\n";
        $purged++;
    } else {
        echo "  [--] $table — inexistante\n";
        $skipped++;
    }
}

DB::statement('SET FOREIGN_KEY_CHECKS = 1');
echo "\n--- FK checks réactivés ---\n";

echo "\n--- Vérification données conservées ---\n";
$tablesConservees = [
    'users', 'titres_poste', 'types_reunion', 'roles_reunion',
    'types_penalite', 'types_maintenance', 'types_depense',
    'vehicules', 'chauffeurs', 'chauffeur_vehicule',
    'jours_feries', 'jours_ouvrables_speciaux', 'motifs_recette',
];

foreach ($tablesConservees as $table) {
    if (Schema::hasTable($table)) {
        $count = DB::table($table)->count();
        echo "  [OK] $table — $count lignes conservées\n";
    } else {
        echo "  [--] $table — non créée\n";
    }
}

echo "\n========================================\n";
echo " Terminé : $purged tables purgées, $skipped ignorées\n";
echo " Supprimer ce fichier : rm nettoyage_production.php\n";
echo "========================================\n\n";
