<?php
namespace App\Services;

use App\Models\{AuditLog, Commentaire, ObservationHistorique, Tache, User};
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'activité d'un collaborateur : toutes ses actions tracées, ramenées à un domaine lisible
 * (parc auto, projets & Scrum, tâches, objectifs, observations, présence, comptes rendus, administration).
 * Sources : journal d'audit, journal des tâches (commentaires), historique des observations,
 * et, pour l'historique antérieur à la traçabilité automatique, les tâches achevées (date de fin réelle).
 */
final class JournalActivite
{
    private static int $suspendu = 0;

    /** Exécute un traitement de masse sans trace ligne à ligne (l'appelant journalise une synthèse). */
    public static function sans(callable $traitement): mixed
    {
        self::$suspendu++;
        try {
            return $traitement();
        } finally {
            self::$suspendu--;
        }
    }

    /** Une action est tracée si elle est faite par une personne connectée, hors traitement de masse. */
    public static function actif(): bool
    {
        return self::$suspendu === 0 && auth()->check();
    }

    public const DOMAINES = [
        'parc'         => ['libelle' => 'Parc auto',          'couleur' => '#0f766e', 'fond' => '#ccfbf1'],
        'scrum'        => ['libelle' => 'Projets & Scrum',    'couleur' => '#1d4ed8', 'fond' => '#dbeafe'],
        'taches'       => ['libelle' => 'Tâches',             'couleur' => '#2e74b5', 'fond' => '#e0ecf8'],
        'objectifs'    => ['libelle' => 'Objectifs',          'couleur' => '#7c3aed', 'fond' => '#ede9fe'],
        'observations' => ['libelle' => 'Observations',       'couleur' => '#b45309', 'fond' => '#fef3c7'],
        'presence'     => ['libelle' => 'Présence & réunions', 'couleur' => '#15803d', 'fond' => '#dcfce7'],
        'rapports'     => ['libelle' => 'Comptes rendus',     'couleur' => '#be185d', 'fond' => '#fce7f3'],
        'admin'        => ['libelle' => 'Administration',     'couleur' => '#475569', 'fond' => '#e2e8f0'],
        'connexion'    => ['libelle' => 'Connexions',         'couleur' => '#64748b', 'fond' => '#f1f5f9'],
    ];

    private const MODULES = [
        // Parc automobile
        'parc' => 'parc', 'vehicule' => 'parc', 'chauffeur' => 'parc', 'recette' => 'parc', 'recettes' => 'parc',
        'motif_recette' => 'parc', 'versement' => 'parc', 'recouvrement' => 'parc', 'penalite' => 'parc',
        'paiement_penalite' => 'parc', 'maintenance' => 'parc', 'panne' => 'parc', 'ligne_intervention' => 'parc',
        'depense' => 'parc', 'emprunt' => 'parc', 'remboursement_emprunt' => 'parc', 'pret_accorde' => 'parc',
        'remboursement_pret' => 'parc', 'autre_ressource' => 'parc', 'document_parc' => 'parc', 'caisse' => 'parc',
        // Projets & Scrum
        'projet' => 'scrum', 'sprint' => 'scrum', 'sprints' => 'scrum', 'daily' => 'scrum', 'collaborateur' => 'scrum',
        // Tâches
        'taches' => 'taches', 'blocages' => 'taches', 'saisie' => 'taches',
        'objectifs' => 'objectifs',
        'observations' => 'observations',
        'pointage' => 'presence', 'planning' => 'presence', 'reunions' => 'presence',
        'comptes_rendus' => 'rapports',
        'utilisateur' => 'admin', 'horaires' => 'admin', 'parametres' => 'admin', 'notifications' => 'admin',
        'auth' => 'connexion',
    ];

    /** Libellés des champs suivis par la traçabilité automatique. */
    public const CHAMPS = [
        'statut' => 'Statut', 'user_id' => 'Attribuée à', 'echeance' => 'Échéance', 'date_debut' => 'Début',
        'intitule' => 'Intitulé', 'libelle' => 'Libellé', 'ponderation' => 'Pondération', 'date_echeance' => 'Échéance',
        'statut_validation' => 'Validation', 'note_pilote' => 'Note du pilote', 'valeur_actuelle' => 'Valeur atteinte',
        'valeur_cible' => 'Cible', 'valide' => 'Validé', 'date_levee' => 'Levée', 'intervenant_id' => 'Intervenant', 'motif' => 'Motif',
    ];

    private const MODULES_LIBELLES = [
        'recette' => 'Recette', 'recettes' => 'Recettes', 'motif_recette' => 'Motif de recette', 'versement' => 'Versement',
        'recouvrement' => 'Recouvrement', 'penalite' => 'Pénalité', 'paiement_penalite' => 'Paiement de pénalité',
        'maintenance' => 'Maintenance', 'panne' => 'Panne', 'ligne_intervention' => "Ligne d'intervention", 'depense' => 'Dépense',
        'emprunt' => 'Emprunt', 'remboursement_emprunt' => "Remboursement d'emprunt", 'pret_accorde' => 'Prêt accordé',
        'remboursement_pret' => 'Remboursement de prêt', 'autre_ressource' => 'Autre ressource', 'document_parc' => 'Document',
        'vehicule' => 'Véhicule', 'chauffeur' => 'Chauffeur', 'parc' => 'Parc', 'projet' => 'Projet', 'sprint' => 'Sprint',
        'sprints' => 'Sprint', 'daily' => 'Daily Scrum', 'collaborateur' => 'Équipe projet', 'taches' => 'Tâche', 'blocages' => 'Blocage',
        'saisie' => 'Saisie', 'objectifs' => 'Objectif', 'observations' => 'Observation', 'pointage' => 'Pointage',
        'planning' => 'Planning des réunions', 'comptes_rendus' => 'Compte rendu', 'utilisateur' => 'Utilisateur',
        'horaires' => 'Horaires', 'auth' => 'Session',
    ];

    private const ACTIONS = [
        'creation' => 'Création', 'modification' => 'Modification', 'suppression' => 'Suppression', 'annulation' => 'Annulation',
        'validation' => 'Validation', 'invalidation' => 'Invalidation', 'realisation' => 'Réalisation',
        'changement_statut' => 'Changement de statut', 'attribution' => 'Attribution', 'connexion' => 'Connexion',
        'deconnexion' => 'Déconnexion', 'commentaire' => 'Commentaire', 'tache_achevee' => 'Tâche achevée',
        'pointer_arrivee' => 'Arrivée pointée', 'pointer_depart' => 'Départ pointé', 'motif_retard' => 'Motif de retard',
        'cloture_oubli_depart' => 'Départ oublié clôturé', 'corriger_pointage' => 'Pointage corrigé', 'marquer_absent' => 'Absence marquée',
        'declaration' => 'Déclaration', 'declarer_observation' => 'Déclaration', 'traiter_observation' => 'Traitement',
        'statut' => 'Changement de statut', 'resolution' => 'Résolution', 'responsable' => 'Responsable désigné', 'delegation' => 'Délégation du suivi',
        'export_archive_scrum' => 'Export du classeur Scrum', 'creation_daily' => 'Daily créé', 'publication_daily' => 'Daily publié',
        'planif_user_story' => 'User story planifiée', 'puiser_backlog' => 'Stories puisées au backlog',
        'import_consolide' => 'Import du classeur Scrum', 'import_backlog' => 'Import du Product Backlog', 'import_taches' => 'Import de tâches',
        'creation_cr' => 'Compte rendu créé', 'transmission_cr' => 'Compte rendu transmis', 'confirmation_cr' => 'Compte rendu confirmé',
        'renvoi_cr' => 'Compte rendu renvoyé', 'suppression_cr' => 'Compte rendu supprimé', 'validation_review' => 'Review validée',
        'rectification_ouverte' => 'Rectification ouverte', 'rectification_fermee' => 'Rectification close',
        'rectification_taches' => 'Tâches rectifiées', 'restauration_structure' => 'Structure restaurée',
        'repartition_points' => 'Points répartis', 'nettoyage_import' => "Nettoyage d'import", 'notification' => 'Notification envoyée',
        'horaires_modifies' => 'Horaires modifiés', 'ajout_collaborateur' => 'Collaborateur ajouté',
    ];

    public static function domaine(string $module): string
    {
        return self::MODULES[$module] ?? 'admin';
    }

    public static function libelleModule(string $module): string
    {
        return self::MODULES_LIBELLES[$module] ?? ucfirst(str_replace('_', ' ', $module));
    }

    public static function libelleAction(string $action): string
    {
        return self::ACTIONS[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }

    /**
     * Toutes les actions du collaborateur sur la période, de la plus récente à la plus ancienne.
     * @return Collection<int, array{quand: Carbon, heure: bool, domaine: string, module: string, action: string, cible: ?string, details: ?string}>
     */
    public static function pour(User $u, string $debut, string $fin): Collection
    {
        $de = Carbon::parse($debut)->startOfDay();
        $a = Carbon::parse($fin)->endOfDay();
        $lignes = collect();

        $audit = Schema::hasTable('audit_logs')
            ? AuditLog::where('user_id', $u->id)->whereBetween('created_at', [$de, $a])->orderByDesc('created_at')->get()
            : collect();
        foreach ($audit as $l) {
            $lignes->push(self::ligne($l->created_at, $l->module, $l->action, $l->cible, $l->details));
        }

        // Observations : l'historique détaillé remplace les traces d'audit équivalentes
        if (Schema::hasTable('observation_historiques')) {
            $histo = ObservationHistorique::with('observation:id,code')->where('user_id', $u->id)
                ->whereBetween('created_at', [$de, $a])->get();
            if ($histo->isNotEmpty()) {
                $lignes = $lignes->reject(fn($l) => $l['module'] === 'observations'
                    && $histo->contains(fn($h) => abs($h->created_at->diffInSeconds($l['quand'])) <= 120));
                foreach ($histo as $h) {
                    $lignes->push(self::ligne($h->created_at, 'observations', $h->action, $h->observation?->code, $h->details));
                }
            }
        }

        // Journal des tâches : commentaires, justifications
        $commentaires = Commentaire::with(['tache' => fn($q) => $q->withTrashed()->select('id', 'intitule', 'projet_id')])
            ->where('user_id', $u->id)->whereBetween('created_at', [$de, $a])->get();
        foreach ($commentaires as $c) {
            $lignes->push(self::ligne($c->created_at, 'taches', 'commentaire', $c->tache ? "#{$c->tache->id} {$c->tache->intitule}" : null, strip_tags((string) $c->contenu)));
        }

        // Tâches achevées : repère daté, sauf quand le passage à « Terminé » est déjà tracé
        $tracees = $lignes->filter(fn($l) => $l['module'] === 'taches' && $l['action'] === 'changement_statut' && str_contains((string) $l['details'], '→ Terminé'))
            ->map(fn($l) => preg_match('/^#(\d+)/', (string) $l['cible'], $m) ? (int) $m[1] : null)->filter()->flip();
        $achevees = Tache::withTrashed()->with('projet:id,code')->where('user_id', $u->id)->where('statut', 'termine')
            ->whereBetween('date_fin_reelle', [$de->toDateString(), $a->toDateString()])->get(['id', 'intitule', 'projet_id', 'date_fin_reelle', 'echeance']);
        foreach ($achevees as $t) {
            if ($tracees->has($t->id)) continue;
            $retard = $t->echeance && $t->date_fin_reelle->gt($t->echeance);
            $lignes->push(self::ligne($t->date_fin_reelle->copy()->startOfDay(), 'taches', 'tache_achevee',
                "#{$t->id} {$t->intitule}" . ($t->projet ? " ({$t->projet->code})" : ''), $retard ? 'Achevée après l\'échéance du ' . $t->echeance->format('d/m/Y') : null, false));
        }

        return $lignes->sortByDesc(fn($l) => $l['quand']->timestamp * 10 + ($l['heure'] ? 1 : 0))->values();
    }

    private static function ligne(Carbon $quand, string $module, string $action, ?string $cible, ?string $details, bool $heure = true): array
    {
        return [
            'quand' => $quand, 'heure' => $heure, 'domaine' => self::domaine($module), 'module' => $module,
            'action' => $action, 'cible' => $cible, 'details' => $details !== null ? trim($details) : null,
        ];
    }
}
