<?php
use App\Http\Controllers\{TacheController, BlocageController, CommentaireController, SprintController, DashboardController};
use App\Http\Controllers\Admin\{UtilisateurController, PoleController, ProjetController, JourFerieController};
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {
    // M1 — Tableau de bord (US-16)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // M2 — Activités (US-06, US-07, US-08)
    Route::get('/taches', [TacheController::class, 'index'])->name('taches.index');
    Route::post('/taches', [TacheController::class, 'store'])->name('taches.store');
    Route::get('/taches/{tache}', [TacheController::class, 'show'])->name('taches.show');
    Route::put('/taches/{tache}', [TacheController::class, 'update'])->name('taches.update');
    Route::patch('/taches/{tache}/statut', [TacheController::class, 'statut'])->name('taches.statut');
    Route::post('/taches/dupliquer-veille', [TacheController::class, 'dupliquerVeille'])->name('taches.dupliquer-veille');

    // US-09 : Blocages
    Route::post('/taches/{tache}/blocages', [BlocageController::class, 'store'])->name('blocages.store');
    Route::patch('/blocages/{blocage}/lever', [BlocageController::class, 'lever'])->name('blocages.lever');

    // US-10 : Commentaires
    Route::post('/taches/{tache}/commentaires', [CommentaireController::class, 'store'])->name('commentaires.store');

    // US-11 : Sprints
    Route::get('/sprints', [SprintController::class, 'index'])->name('sprints.index');
    Route::post('/sprints', [SprintController::class, 'store'])->name('sprints.store');
    Route::put('/sprints/{sprint}', [SprintController::class, 'update'])->name('sprints.update');

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
    });
});

require __DIR__.'/auth.php';
