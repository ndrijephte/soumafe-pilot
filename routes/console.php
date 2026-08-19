<?php

use Illuminate\Support\Facades\Schedule;

// Contrôle de saisie quotidien : détecte les jours manquants et notifie
Schedule::command('soumafe:controle-saisie')->weekdays()->at('08:00');
