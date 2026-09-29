<?php
use App\Http\Controllers\{TacheController, BlocageController, CommentaireController, SprintController, DashboardController, ArbitrageController, SaisieController, NotificationController, ProfilController, ParcController, PointageController, ObservationController, PlanningReunionController};
use App\Http\Controllers\Admin\{UtilisateurController, PoleController, ProjetController, JourFerieController, DomaineController, ServiceController, RoleController, ProfilMesureController, TypeEvenementController, TypeBlocageController, ParcParametreController, TitrePosteController};
use App\Http\Controllers\{CompteRenduController, ExportController, ObjectifController, ReportingController, ReportingParcController};
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {
    // M1 — Tableau de bord (US-16/17/18 — aiguillage par rôle)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // M2 — Activités (US-06, US-07, US-08)
    Route::get('/taches', [TacheController::class, 'index'])->name('taches.index');
    Route::get('/taches/pilote', [TacheController::class, 'pilote'])->name('taches.pilote');
    Route::post('/taches', [TacheController::class, 'store'])->name('taches.store');
    Route::get('/taches/{tache}', [TacheController::class, 'show'])->name('taches.show');
    Route::put('/taches/{tache}', [TacheController::class, 'update'])->name('taches.update');
    Route::patch('/taches/{tache}/statut', [TacheController::class, 'statut'])->name('taches.statut');
    Route::delete('/taches/{tache}', [TacheController::class, 'destroy'])->name('taches.destroy');
    Route::post('/taches/{tache}/valider', [TacheController::class, 'valider'])->name('taches.valider');
    Route::post('/taches/dupliquer-veille', [TacheController::class, 'dupliquerVeille'])->name('taches.dupliquer-veille');

    // US-09 : Blocages
    Route::post('/taches/{tache}/blocages', [BlocageController::class, 'store'])->name('blocages.store');
    Route::patch('/blocages/{blocage}/lever', [BlocageController::class, 'lever'])->name('blocages.lever');

    // US-10 : Commentaires
    Route::post('/taches/{tache}/commentaires', [CommentaireController::class, 'store'])->name('commentaires.store');

    // US-11 : Sprints
    Route::get('/sprints', [SprintController::class, 'index'])->name('sprints.index');
    Route::post('/sprints', [SprintController::class, 'store'])->name('sprints.store');
    Route::get('/sprints/{sprint}', [SprintController::class, 'show'])->name('sprints.show');
    Route::put('/sprints/{sprint}', [SprintController::class, 'update'])->name('sprints.update');
    Route::post('/sprints/{sprint}/cloturer', [SprintController::class, 'cloturer'])->name('sprints.cloturer');
    Route::post('/sprints/{sprint}/demarrer', [SprintController::class, 'demarrer'])->name('sprints.demarrer');
    Route::post('/sprints/{sprint}/arreter', [SprintController::class, 'arreter'])->name('sprints.arreter');
    Route::get('/api/projets/{projet}/sprints', [SprintController::class, 'parProjet'])->name('api.sprints');
    Route::post('/sprints/{sprint}/taches', [SprintController::class, 'ajouterTache'])->name('sprints.ajouter-tache');
    Route::put('/sprints/{sprint}/taches/{tache}', [SprintController::class, 'modifierTache'])->name('sprints.modifier-tache');
    Route::delete('/sprints/{sprint}/taches/{tache}', [SprintController::class, 'supprimerTache'])->name('sprints.supprimer-tache');
    Route::delete('/sprints/{sprint}', [SprintController::class, 'destroy'])->name('sprints.destroy');
    Route::post('/sprints/importer', [SprintController::class, 'importerTaches'])->name('sprints.importer');
    Route::post('/sprints/{sprint}/toggle-visibilite', [SprintController::class, 'toggleVisibilite'])->name('sprints.toggle-visibilite');
    Route::post('/sprints/{sprint}/update-exclusions', [SprintController::class, 'updateExclusions'])->name('sprints.update-exclusions');

    // US-12 : Arbitrages T3
    Route::post('/taches/{tache}/arbitrage', [ArbitrageController::class, 'demander'])->name('arbitrages.demander');
    Route::get('/arbitrages', [ArbitrageController::class, 'index'])->name('arbitrages.index');
    Route::patch('/arbitrages/{arbitrage}', [ArbitrageController::class, 'decider'])->name('arbitrages.decider');
    Route::post('/arbitrages/evenement/{evenement}/recommander', [ArbitrageController::class, 'recommander'])->name('arbitrages.recommander');

    // US-13/14 : Saisie quotidienne + confirmation + événements
    Route::get('/saisie', [SaisieController::class, 'index'])->name('saisie.index');
    Route::post('/saisie/{tache}/confirmer', [SaisieController::class, 'confirmer'])->name('saisie.confirmer');
    Route::post('/saisie/evenement', [SaisieController::class, 'evenement'])->name('saisie.evenement');
    Route::patch('/saisie/{jour}/justifier', [SaisieController::class, 'justifier'])->name('saisie.justifier');
    Route::post('/saisie/rappeler', [SaisieController::class, 'rappeler'])->name('saisie.rappeler');
    Route::post('/saisie/notifier', [SaisieController::class, 'notifierCollaborateurs'])->name('saisie.notifier');
    Route::post('/saisie/absence', [SaisieController::class, 'declarerAbsence'])->name('saisie.absence');
    Route::delete('/saisie/absence/{absence}', [SaisieController::class, 'annulerAbsence'])->name('saisie.absence.annuler');

    // US-21 : Notifications in-app (JSON + page)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/api/notifications', [NotificationController::class, 'nonLues'])->name('api.notifications');
    Route::patch('/api/notifications/{notification}/lire', [NotificationController::class, 'lire'])->name('api.notifications.lire');
    Route::post('/api/notifications/tout-lire', [NotificationController::class, 'toutLire'])->name('api.notifications.tout-lire');
    Route::post('/notifications/envoyer', [NotificationController::class, 'envoyer'])->name('notifications.envoyer');

    // Recherche globale
    Route::get('/api/recherche', [DashboardController::class, 'recherche'])->name('api.recherche');

    // Profil & changement mot de passe
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
    Route::patch('/profil/mot-de-passe', [ProfilController::class, 'updateMotDePasse'])->name('profil.password');
    Route::post('/admin/utilisateurs/{utilisateur}/reset-mdp', [ProfilController::class, 'resetMotDePasse'])->name('admin.utilisateurs.reset-mdp');

    // Objectifs individuels
    Route::get('/objectifs', [ObjectifController::class, 'index'])->name('objectifs.index');
    Route::post('/objectifs', [ObjectifController::class, 'store'])->name('objectifs.store');
    Route::put('/objectifs/{objectif}', [ObjectifController::class, 'update'])->name('objectifs.update');
    Route::post('/objectifs/{objectif}/valider', [ObjectifController::class, 'valider'])->name('objectifs.valider');
    Route::delete('/objectifs/{objectif}', [ObjectifController::class, 'destroy'])->name('objectifs.destroy');
    Route::post('/objectifs/evaluer', [ObjectifController::class, 'evaluer'])->name('objectifs.evaluer');

    // Résultats clés (KR)
    Route::post('/objectifs/{objectif}/kr', [ObjectifController::class, 'storeKR'])->name('objectifs.kr.store');
    Route::put('/kr/{kr}', [ObjectifController::class, 'updateKR'])->name('objectifs.kr.update');
    Route::delete('/kr/{kr}', [ObjectifController::class, 'destroyKR'])->name('objectifs.kr.destroy');

    // Liaison tâches ↔ objectifs
    Route::post('/objectifs/{objectif}/taches', [ObjectifController::class, 'lierTache'])->name('objectifs.taches.lier');
    Route::delete('/objectifs/{objectif}/taches/{tache}', [ObjectifController::class, 'delierTache'])->name('objectifs.taches.delier');

    // Auto-évaluation / évaluation pilote
    Route::post('/objectifs/{objectif}/auto-evaluer', [ObjectifController::class, 'autoEvaluer'])->name('objectifs.autoEvaluer');
    Route::post('/objectifs/{objectif}/evaluer-pilote', [ObjectifController::class, 'evaluerPilote'])->name('objectifs.evaluerPilote');
    Route::put('/kr/{kr}/valider', [ObjectifController::class, 'validerIndicateur'])->name('objectifs.kr.valider');
    Route::put('/kr/{kr}/reset-validation', [ObjectifController::class, 'resetIndicateur'])->name('objectifs.kr.resetValidation');

    // Reporting (pilotes)
    Route::get('/reporting', [ReportingController::class, 'index'])->name('reporting.index');
    Route::get('/reporting/imprimer', [ReportingController::class, 'imprimer'])->name('reporting.imprimer');

    // Comptes rendus
    Route::get('/comptes-rendus', [CompteRenduController::class, 'index'])->name('comptes-rendus.index');
    Route::get('/comptes-rendus/creer', [CompteRenduController::class, 'create'])->name('comptes-rendus.create');
    Route::post('/comptes-rendus/designer', [CompteRenduController::class, 'designer'])->name('comptes-rendus.designer');
    Route::get('/comptes-rendus/{cr}', [CompteRenduController::class, 'show'])->name('comptes-rendus.show');
    Route::get('/comptes-rendus/{cr}/editer', [CompteRenduController::class, 'edit'])->name('comptes-rendus.edit');
    Route::put('/comptes-rendus/{cr}', [CompteRenduController::class, 'update'])->name('comptes-rendus.update');
    Route::post('/comptes-rendus/{cr}/confirmer', [CompteRenduController::class, 'confirmer'])->name('comptes-rendus.confirmer');
    Route::get('/comptes-rendus/{cr}/telecharger', [CompteRenduController::class, 'telecharger'])->name('comptes-rendus.telecharger');
    Route::get('/comptes-rendus/{cr}/imprimer', [CompteRenduController::class, 'imprimer'])->name('comptes-rendus.imprimer');
    Route::delete('/comptes-rendus/{cr}', [CompteRenduController::class, 'destroy'])->name('comptes-rendus.destroy');

    // Exports xlsx (pilotes : direction, charge_operations, admin)
    Route::get('/exports', [ExportController::class, 'index'])->name('export.index');
    Route::get('/exports/collaborateurs', [ExportController::class, 'collaborateurs'])->name('export.collaborateurs');
    Route::get('/exports/taches', [ExportController::class, 'taches'])->name('export.taches');
    Route::get('/exports/sprint/{sprint}', [ExportController::class, 'sprint'])->name('export.sprint');
    Route::get('/exports/evenements', [ExportController::class, 'evenements'])->name('export.evenements');
    Route::get('/exports/vehicules', [ExportController::class, 'vehicules'])->name('export.vehicules');
    Route::get('/exports/recettes', [ExportController::class, 'recettes'])->name('export.recettes');
    Route::get('/exports/pannes', [ExportController::class, 'pannes'])->name('export.pannes');
    Route::get('/exports/chauffeurs', [ExportController::class, 'chauffeurs'])->name('export.chauffeurs');

    // Parc automobile
    Route::get('/parc', [ParcController::class, 'index'])->name('parc.index');
    Route::post('/parc/vehicules', [ParcController::class, 'storeVehicule'])->name('parc.vehicules.store');
    Route::put('/parc/vehicules/{vehicule}', [ParcController::class, 'updateVehicule'])->name('parc.vehicules.update');
    Route::delete('/parc/vehicules/{vehicule}', [ParcController::class, 'destroyVehicule'])->name('parc.vehicules.destroy');
    Route::post('/parc/pannes', [ParcController::class, 'storePanne'])->name('parc.pannes.store');
    Route::put('/parc/pannes/{panne}', [ParcController::class, 'updatePanne'])->name('parc.pannes.update');
    Route::delete('/parc/pannes/{panne}', [ParcController::class, 'destroyPanne'])->name('parc.pannes.destroy');
    Route::post('/parc/pannes/{panne}/lignes', [ParcController::class, 'storeLigneIntervention'])->name('parc.pannes.lignes.store');
    Route::put('/parc/pannes/lignes/{ligne}', [ParcController::class, 'updateLigneIntervention'])->name('parc.pannes.lignes.update');
    Route::delete('/parc/pannes/lignes/{ligne}', [ParcController::class, 'destroyLigneIntervention'])->name('parc.pannes.lignes.destroy');
    Route::post('/parc/recettes', [ParcController::class, 'storeRecette'])->name('parc.recettes.store');
    Route::put('/parc/recettes/{recette}', [ParcController::class, 'updateRecette'])->name('parc.recettes.update');
    Route::delete('/parc/recettes/{recette}', [ParcController::class, 'destroyRecette'])->name('parc.recettes.destroy');
    Route::post('/parc/recettes/valider-journee', [ParcController::class, 'validerJournee'])->name('parc.recettes.validerJournee');
    Route::post('/parc/maintenances', [ParcController::class, 'storeMaintenance'])->name('parc.maintenances.store');
    Route::put('/parc/maintenances/{maintenance}', [ParcController::class, 'updateMaintenance'])->name('parc.maintenances.update');
    Route::put('/parc/maintenances/{maintenance}/realiser', [ParcController::class, 'realiserMaintenance'])->name('parc.maintenances.realiser');
    Route::delete('/parc/maintenances/{maintenance}', [ParcController::class, 'destroyMaintenance'])->name('parc.maintenances.destroy');
    Route::post('/parc/penalites', [ParcController::class, 'storePenalite'])->name('parc.penalites.store');
    Route::put('/parc/penalites/{penalite}', [ParcController::class, 'updatePenalite'])->name('parc.penalites.update');
    Route::delete('/parc/penalites/{penalite}', [ParcController::class, 'destroyPenalite'])->name('parc.penalites.destroy');
    Route::post('/parc/penalites/{penalite}/paiement', [ParcController::class, 'storePaiementPenalite'])->name('parc.penalites.paiement');
    // Recouvrements
    Route::post('/parc/recouvrements/{recouvrement}/versement', [ParcController::class, 'storeVersement'])->name('parc.recouvrements.versement');
    Route::patch('/parc/recouvrements/{recouvrement}/annuler', [ParcController::class, 'annulerRecouvrement'])->name('parc.recouvrements.annuler');
    Route::delete('/parc/recouvrements/{recouvrement}', [ParcController::class, 'destroyRecouvrement'])->name('parc.recouvrements.destroy');
    Route::post('/parc/chauffeurs', [ParcController::class, 'storeChauffeur'])->name('parc.chauffeurs.store');
    Route::put('/parc/chauffeurs/{chauffeur}', [ParcController::class, 'updateChauffeur'])->name('parc.chauffeurs.update');
    Route::delete('/parc/chauffeurs/{chauffeur}', [ParcController::class, 'destroyChauffeur'])->name('parc.chauffeurs.destroy');
    Route::post('/parc/chauffeurs/affecter', [ParcController::class, 'affecterChauffeur'])->name('parc.chauffeurs.affecter');
    Route::delete('/parc/vehicules/{vehicule}/chauffeurs/{chauffeur}', [ParcController::class, 'detacherChauffeur'])->name('parc.chauffeurs.detacher');
    Route::get('/parc/vehicules/{vehicule}/fiche', [ParcController::class, 'ficheVehicule'])->name('parc.vehicules.fiche');
    Route::get('/parc/chauffeurs/{chauffeur}/fiche', [ParcController::class, 'ficheChauffeur'])->name('parc.chauffeurs.fiche');
    // Dépenses
    Route::post('/parc/depenses', [ParcController::class, 'storeDepense'])->name('parc.depenses.store');
    Route::put('/parc/depenses/{depense}', [ParcController::class, 'updateDepense'])->name('parc.depenses.update');
    Route::delete('/parc/depenses/{depense}', [ParcController::class, 'destroyDepense'])->name('parc.depenses.destroy');
    Route::post('/parc/emprunts', [ParcController::class, 'storeEmprunt'])->name('parc.emprunts.store');
    Route::post('/parc/emprunts/{emprunt}/remboursement', [ParcController::class, 'storeRemboursement'])->name('parc.emprunts.remboursement');
    Route::delete('/parc/emprunts/{emprunt}', [ParcController::class, 'destroyEmprunt'])->name('parc.emprunts.destroy');

    // Prêts accordés
    Route::post('/parc/prets', [ParcController::class, 'storePret'])->name('parc.prets.store');
    Route::post('/parc/prets/{pret}/remboursement', [ParcController::class, 'storeRemboursementPret'])->name('parc.prets.remboursement');
    Route::delete('/parc/prets/{pret}', [ParcController::class, 'destroyPret'])->name('parc.prets.destroy');

    // Autres ressources
    Route::post('/parc/ressources', [ParcController::class, 'storeAutreRessource'])->name('parc.ressources.store');
    Route::put('/parc/ressources/{ressource}', [ParcController::class, 'updateAutreRessource'])->name('parc.ressources.update');
    Route::delete('/parc/ressources/{ressource}', [ParcController::class, 'destroyAutreRessource'])->name('parc.ressources.destroy');

    // Workflow motif recettes
    Route::patch('/parc/recettes/{recette}/valider-motif', [ParcController::class, 'validerMotif'])->name('parc.recettes.validerMotif');
    Route::patch('/parc/recettes/{recette}/invalider-motif', [ParcController::class, 'invaliderMotif'])->name('parc.recettes.invaliderMotif');

    Route::get('/parc/reporting', [ReportingParcController::class, 'index'])->name('reporting.parc');

    // Pointage & Présence
    Route::get('/observations', [ObservationController::class, 'index'])->name('observations.index');
    Route::post('/observations', [ObservationController::class, 'store'])->name('observations.store');
    Route::put('/observations/{observation}', [ObservationController::class, 'update'])->name('observations.update');
    Route::delete('/observations/{observation}', [ObservationController::class, 'destroy'])->name('observations.destroy');
    Route::post('/observations/{observation}/deleguer-suivi', [ObservationController::class, 'deleguerSuivi'])->name('observations.deleguer-suivi');
    Route::get('/observations/stats-collaborateur', [ObservationController::class, 'statsCollaborateur'])->name('observations.stats-collaborateur');
    Route::get('/observations/fiche-collaborateur', [ObservationController::class, 'ficheCollaborateur'])->name('observations.fiche-collaborateur');

    // Planning Réunions
    Route::get('/planning', [PlanningReunionController::class, 'index'])->name('planning.index');
    Route::post('/planning/generer', [PlanningReunionController::class, 'generer'])->name('planning.generer');
    Route::post('/planning/participant', [PlanningReunionController::class, 'ajouterParticipant'])->name('planning.ajouter');
    Route::post('/planning/participant/{participant}/retirer', [PlanningReunionController::class, 'retirerParticipant'])->name('planning.retirer');
    Route::post('/planning/type', [PlanningReunionController::class, 'storeType'])->name('planning.type.store');
    Route::post('/planning/role', [PlanningReunionController::class, 'storeRole'])->name('planning.role.store');
    Route::post('/planning/role/{role}/supprimer', [PlanningReunionController::class, 'destroyRole'])->name('planning.role.destroy');
    Route::post('/planning/{affectation}/absence', [PlanningReunionController::class, 'signalerAbsence'])->name('planning.absence');
    Route::put('/planning/type/{type}', [PlanningReunionController::class, 'updateType'])->name('planning.type.update');
    Route::put('/planning/affectation/{affectation}', [PlanningReunionController::class, 'modifierAffectation'])->name('planning.affectation.update');
    Route::delete('/planning/instance', [PlanningReunionController::class, 'supprimerInstance'])->name('planning.instance.destroy');

    Route::get('/pointage', [PointageController::class, 'index'])->name('pointage.index');
    Route::post('/pointage/arrivee', [PointageController::class, 'pointerArrivee'])->name('pointage.arrivee');
    Route::post('/pointage/depart', [PointageController::class, 'pointerDepart'])->name('pointage.depart');
    Route::post('/pointage/{pointage}/motif', [PointageController::class, 'sauverMotif'])->name('pointage.motif');
    Route::post('/pointage/absent', [PointageController::class, 'marquerAbsent'])->name('pointage.absent');
    Route::post('/pointage/pointer-pour', [PointageController::class, 'pointerPour'])->name('pointage.pointer-pour');
    Route::put('/pointage/{pointage}/corriger', [PointageController::class, 'corriger'])->name('pointage.corriger');
    Route::post('/pointage/jour-special', [PointageController::class, 'ajouterJourSpecial'])->name('pointage.jour-special.ajouter');
    Route::post('/pointage/jour-special/{jour}/retirer', [PointageController::class, 'retirerJourSpecial'])->name('pointage.jour-special.retirer');

    // M8 — Administration (US-01→05)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/utilisateurs', [UtilisateurController::class, 'index'])->name('utilisateurs.index');
        Route::get('/utilisateurs/creer', [UtilisateurController::class, 'create'])->name('utilisateurs.create');
        Route::post('/utilisateurs', [UtilisateurController::class, 'store'])->name('utilisateurs.store');
        Route::get('/utilisateurs/{utilisateur}/modifier', [UtilisateurController::class, 'edit'])->name('utilisateurs.edit');
        Route::put('/utilisateurs/{utilisateur}', [UtilisateurController::class, 'update'])->name('utilisateurs.update');
        Route::patch('/utilisateurs/{utilisateur}/toggle', [UtilisateurController::class, 'toggleActif'])->name('utilisateurs.toggle');

        Route::get('/poles', [PoleController::class, 'index'])->name('poles.index');
        Route::post('/poles', [PoleController::class, 'store'])->name('poles.store');
        Route::put('/poles/{pole}', [PoleController::class, 'update'])->name('poles.update');
        Route::delete('/poles/{pole}', [PoleController::class, 'destroy'])->name('poles.destroy');

        Route::get('/projets', [ProjetController::class, 'index'])->name('projets.index');
        Route::post('/projets', [ProjetController::class, 'store'])->name('projets.store');
        Route::put('/projets/{projet}', [ProjetController::class, 'update'])->name('projets.update');

        Route::get('/feries', [JourFerieController::class, 'index'])->name('feries.index');
        Route::post('/feries', [JourFerieController::class, 'store'])->name('feries.store');
        Route::put('/feries/{ferie}', [JourFerieController::class, 'update'])->name('feries.update');
        Route::delete('/feries/{ferie}', [JourFerieController::class, 'destroy'])->name('feries.destroy');

        Route::get('/domaines', [DomaineController::class, 'index'])->name('domaines.index');
        Route::post('/domaines', [DomaineController::class, 'store'])->name('domaines.store');
        Route::put('/domaines/{domaine}', [DomaineController::class, 'update'])->name('domaines.update');
        Route::delete('/domaines/{domaine}', [DomaineController::class, 'destroy'])->name('domaines.destroy');

        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('/profils', [ProfilMesureController::class, 'index'])->name('profils.index');
        Route::post('/profils', [ProfilMesureController::class, 'store'])->name('profils.store');
        Route::put('/profils/{profil}', [ProfilMesureController::class, 'update'])->name('profils.update');
        Route::delete('/profils/{profil}', [ProfilMesureController::class, 'destroy'])->name('profils.destroy');

        Route::get('/types-evenement', [TypeEvenementController::class, 'index'])->name('types-evenement.index');
        Route::post('/types-evenement', [TypeEvenementController::class, 'store'])->name('types-evenement.store');
        Route::put('/types-evenement/{type}', [TypeEvenementController::class, 'update'])->name('types-evenement.update');
        Route::delete('/types-evenement/{type}', [TypeEvenementController::class, 'destroy'])->name('types-evenement.destroy');

        Route::get('/types-blocage', [TypeBlocageController::class, 'index'])->name('types-blocage.index');
        Route::post('/types-blocage', [TypeBlocageController::class, 'store'])->name('types-blocage.store');
        Route::put('/types-blocage/{type}', [TypeBlocageController::class, 'update'])->name('types-blocage.update');
        Route::delete('/types-blocage/{type}', [TypeBlocageController::class, 'destroy'])->name('types-blocage.destroy');

        Route::get('/titres-poste', [TitrePosteController::class, 'index'])->name('titres-poste.index');
        Route::post('/titres-poste', [TitrePosteController::class, 'store'])->name('titres-poste.store');
        Route::put('/titres-poste/{titre}', [TitrePosteController::class, 'update'])->name('titres-poste.update');
        Route::delete('/titres-poste/{titre}', [TitrePosteController::class, 'destroy'])->name('titres-poste.destroy');

        Route::get('/parc-parametres', [ParcParametreController::class, 'index'])->name('parc-parametres.index');
        Route::post('/parc-parametres', [ParcParametreController::class, 'update'])->name('parc-parametres.update');
        Route::post('/parc-parametres/types', [ParcParametreController::class, 'storeType'])->name('parc-parametres.types.store');
        Route::put('/parc-parametres/types/{type}', [ParcParametreController::class, 'toggleType'])->name('parc-parametres.types.toggle');
        Route::delete('/parc-parametres/types/{type}', [ParcParametreController::class, 'destroyType'])->name('parc-parametres.types.destroy');

        Route::get('/audit', function (\Illuminate\Http\Request $r) {
            abort_unless($r->user()->estAdmin(), 403);
            if (!\Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
                $logs = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
                $modules = collect();
                $actions = collect();
                return view('admin.audit', compact('logs', 'modules', 'actions'));
            }
            $query = \App\Models\AuditLog::query()->orderByDesc('created_at');
            if ($r->filled('module')) $query->where('module', $r->module);
            if ($r->filled('action')) $query->where('action', $r->action);
            if ($r->filled('user_name')) $query->where('user_name', 'like', '%'.$r->user_name.'%');
            if ($r->filled('date_debut')) $query->whereDate('created_at', '>=', $r->date_debut);
            if ($r->filled('date_fin'))   $query->whereDate('created_at', '<=', $r->date_fin);
            $logs = $query->paginate(50)->appends($r->query());
            $modules = \App\Models\AuditLog::select('module')->distinct()->orderBy('module')->pluck('module');
            $actions = \App\Models\AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');
            return view('admin.audit', compact('logs', 'modules', 'actions'));
        })->name('audit.index');

    });
});

require __DIR__.'/auth.php';
