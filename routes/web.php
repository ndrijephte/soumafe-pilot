<?php
use App\Http\Controllers\{TacheController, BlocageController, CommentaireController, SprintController, DashboardController, ArbitrageController, SaisieController, NotificationController, ProfilController};
use App\Http\Controllers\Admin\{UtilisateurController, PoleController, ProjetController, JourFerieController, DomaineController, ServiceController, RoleController, ProfilMesureController, TypeEvenementController, TypeBlocageController};
use App\Http\Controllers\{ExportController, ObjectifController, ReportingController};
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {
    // M1 — Tableau de bord (US-16/17/18 — aiguillage par rôle)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // M2 — Activités (US-06, US-07, US-08)
    Route::get('/taches', [TacheController::class, 'index'])->name('taches.index');
    Route::post('/taches', [TacheController::class, 'store'])->name('taches.store');
    Route::get('/taches/{tache}', [TacheController::class, 'show'])->name('taches.show');
    Route::put('/taches/{tache}', [TacheController::class, 'update'])->name('taches.update');
    Route::patch('/taches/{tache}/statut', [TacheController::class, 'statut'])->name('taches.statut');
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
    Route::post('/sprints/importer', [SprintController::class, 'importerTaches'])->name('sprints.importer');

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
    Route::post('/saisie/absence', [SaisieController::class, 'declarerAbsence'])->name('saisie.absence');
    Route::delete('/saisie/absence/{absence}', [SaisieController::class, 'annulerAbsence'])->name('saisie.absence.annuler');

    // US-21 : Notifications in-app (JSON)
    Route::get('/api/notifications', [NotificationController::class, 'nonLues'])->name('api.notifications');
    Route::patch('/api/notifications/{notification}/lire', [NotificationController::class, 'lire'])->name('api.notifications.lire');
    Route::post('/api/notifications/tout-lire', [NotificationController::class, 'toutLire'])->name('api.notifications.tout-lire');

    // Profil & changement mot de passe
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
    Route::patch('/profil/mot-de-passe', [ProfilController::class, 'updateMotDePasse'])->name('profil.password');

    // Objectifs individuels
    Route::get('/objectifs', [ObjectifController::class, 'index'])->name('objectifs.index');
    Route::post('/objectifs', [ObjectifController::class, 'store'])->name('objectifs.store');
    Route::put('/objectifs/{objectif}', [ObjectifController::class, 'update'])->name('objectifs.update');
    Route::post('/objectifs/{objectif}/valider', [ObjectifController::class, 'valider'])->name('objectifs.valider');
    Route::delete('/objectifs/{objectif}', [ObjectifController::class, 'destroy'])->name('objectifs.destroy');
    Route::post('/objectifs/evaluer', [ObjectifController::class, 'evaluer'])->name('objectifs.evaluer');

    // Reporting (pilotes)
    Route::get('/reporting', [ReportingController::class, 'index'])->name('reporting.index');
    Route::get('/reporting/imprimer', [ReportingController::class, 'imprimer'])->name('reporting.imprimer');

    // Exports xlsx (pilotes : direction, charge_operations, admin)
    Route::get('/exports', [ExportController::class, 'index'])->name('export.index');
    Route::get('/exports/collaborateurs', [ExportController::class, 'collaborateurs'])->name('export.collaborateurs');
    Route::get('/exports/taches', [ExportController::class, 'taches'])->name('export.taches');
    Route::get('/exports/sprint/{sprint}', [ExportController::class, 'sprint'])->name('export.sprint');
    Route::get('/exports/evenements', [ExportController::class, 'evenements'])->name('export.evenements');

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

    });
});

require __DIR__.'/auth.php';
