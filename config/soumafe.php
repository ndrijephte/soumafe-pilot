<?php
return [
    // Qualification de clôture (§6 cadrage v1.5)
    'seuil_anticipation_jours' => env('SOUMAFE_SEUIL_ANTICIPATION', 1), // justification si avance > N jours ouvrés
    'tolerance_a_temps_jours'  => env('SOUMAFE_TOLERANCE_A_TEMPS', 0),

    // Effort volontaire week-end / férié (§6 et §10)
    'bonus_effort_volontaire_max' => env('SOUMAFE_BONUS_EFFORT_MAX', 5), // points de % max ajoutés à la tenue
    'bonus_par_tache_volontaire'  => 1,

    // Pondérations de performance (§10)
    'performance' => [
        'developpeur' => ['sprint' => 50, 'objectifs' => 30, 'tenue' => 20],
        'operationnel' => ['objectifs' => 60, 'tenue' => 40],
        'support' => ['objectifs' => 60, 'tenue' => 40],
    ],
];
