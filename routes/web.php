<?php
use App\Http\Controllers\TacheController;
use App\Http\Controllers\Admin\{UtilisateurController, PoleController, ProjetController, JourFerieController};
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('taches.index'));

Route::middleware(['auth'])->group(function () {
    // M1 — Tableau de bord (Sprint 2 : US-16)
    Route::get('/dashboard', fn () => redirect()->route('taches.index'))->name('dashboard');

    // M2 — Activités : le cœur (US-06, US-07, US-08)
    Route::get('/taches', [TacheController::class, 'index'])->name('taches.index');
    Route::post('/taches', [TacheController::class, 'store'])->name('taches.store');
    Route::put('/taches/{tache}', [TacheController::class, 'update'])->name('taches.update');
    Route::patch('/taches/{tache}/statut', [TacheController::class, 'statut'])->name('taches.statut');
    Route::post('/taches/dupliquer-veille', [TacheController::class, 'dupliquerVeille'])->name('taches.dupliquer-veille');

    // M8 — Administration (US-01→05)
    Route::prefix('admin')->name('admin.')->group(function () {
        // US-01/02/03 : Comptes, rôles, profils
        Route::get('/utilisateurs', [UtilisateurController::class, 'index'])->name('utilisateurs.index');
        Route::get('/utilisateurs/creer', [UtilisateurController::class, 'create'])->name('utilisateurs.create');
        Route::post('/utilisateurs', [UtilisateurController::class, 'store'])->name('utilisateurs.store');
        Route::get('/utilisateurs/{utilisateur}/modifier', [UtilisateurController::class, 'edit'])->name('utilisateurs.edit');
        Route::put('/utilisateurs/{utilisateur}', [UtilisateurController::class, 'update'])->name('utilisateurs.update');
        Route::patch('/utilisateurs/{utilisateur}/toggle', [UtilisateurController::class, 'toggleActif'])->name('utilisateurs.toggle');

        // US-04 : Référentiels — Pôles
        Route::get('/poles', [PoleController::class, 'index'])->name('poles.index');
        Route::post('/poles', [PoleController::class, 'store'])->name('poles.store');
        Route::put('/poles/{pole}', [PoleController::class, 'update'])->name('poles.update');
        Route::delete('/poles/{pole}', [PoleController::class, 'destroy'])->name('poles.destroy');

        // US-04 : Référentiels — Projets
        Route::get('/projets', [ProjetController::class, 'index'])->name('projets.index');
        Route::post('/projets', [ProjetController::class, 'store'])->name('projets.store');
        Route::put('/projets/{projet}', [ProjetController::class, 'update'])->name('projets.update');

        // US-05 : Jours fériés
        Route::get('/feries', [JourFerieController::class, 'index'])->name('feries.index');
        Route::post('/feries', [JourFerieController::class, 'store'])->name('feries.store');
        Route::put('/feries/{ferie}', [JourFerieController::class, 'update'])->name('feries.update');
        Route::delete('/feries/{ferie}', [JourFerieController::class, 'destroy'])->name('feries.destroy');
    });
});

require __DIR__.'/auth.php';
