<?php

use App\Services\CalendrierTravail;
use Illuminate\Support\Facades\Schedule;

// Heures des traitements automatiques : paramétrées dans Administration › Horaires de travail (règle en vigueur).
// Base indisponible (installation, migration) : heures par défaut.
$heure = function (string $methode, string $defaut): string {
    try { return CalendrierTravail::$methode(); } catch (\Throwable) { return $defaut; }
};

// Contrôle de saisie quotidien : détecte les jours manquants et notifie
// Tous les jours : la commande ignore elle-même les jours non travaillés (horaires paramétrables)
Schedule::command('soumafe:controle-saisie')->dailyAt($heure('heureControleSaisie', '08:00'));

// Daily Scrum : création automatique du jour selon la programmation de chaque projet
Schedule::command('scrum:daily-auto')->dailyAt($heure('heureDailyAuto', '07:00'));
