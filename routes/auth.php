<?php
use App\Http\Controllers\Auth\ConnexionController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [ConnexionController::class, 'formulaire'])->name('login');
Route::post('/login', [ConnexionController::class, 'connecter'])->middleware('throttle:10,1');
Route::post('/logout', [ConnexionController::class, 'deconnecter'])->name('logout');
