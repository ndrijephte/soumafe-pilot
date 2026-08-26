<?php
use App\Http\Controllers\Auth\{ConnexionController, MotDePasseController};
use Illuminate\Support\Facades\Route;

Route::get('/login', [ConnexionController::class, 'formulaire'])->name('login');
Route::post('/login', [ConnexionController::class, 'connecter'])->middleware('throttle:10,1');
Route::post('/logout', [ConnexionController::class, 'deconnecter'])->name('logout');

// Mot de passe oublié
Route::get('/mot-de-passe-oublie', [MotDePasseController::class, 'formulaireOubli'])->name('password.request');
Route::post('/mot-de-passe-oublie', [MotDePasseController::class, 'envoyerLien'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reinitialiser-mot-de-passe', [MotDePasseController::class, 'formulaireReset'])->name('password.reset');
Route::post('/reinitialiser-mot-de-passe', [MotDePasseController::class, 'reset'])->name('password.update');
