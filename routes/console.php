<?php

use Illuminate\Support\Facades\Schedule;

// Contrôle de saisie quotidien : détecte les jours manquants et notifie
Schedule::command('soumafe:controle-saisie')->weekdays()->at('08:00');

// Daily Scrum : création automatique du jour selon la programmation de chaque projet
Schedule::command('scrum:daily-auto')->dailyAt('07:00');
