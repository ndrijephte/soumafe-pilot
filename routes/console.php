<?php

use Illuminate\Support\Facades\Schedule;

// Contrôle de saisie quotidien : détecte les jours manquants et notifie
// Tous les jours : la commande ignore elle-même les jours non travaillés (horaires paramétrables)
Schedule::command('soumafe:controle-saisie')->dailyAt('08:00');

// Daily Scrum : création automatique du jour selon la programmation de chaque projet
Schedule::command('scrum:daily-auto')->dailyAt('07:00');
