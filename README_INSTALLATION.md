# KIT DE DÉMARRAGE SPRINT 0 — SOUMAFE PILOT
Code prêt à poser sur un projet Laravel 11 neuf. Couvre les tâches J1-J3 du Sprint 0.

## Installation
```bash
composer create-project laravel/laravel soumafe-pilot
cd soumafe-pilot
# AUCUN paquet supplémentaire requis : le kit est autonome
# (auth maison, rôles par colonne — compatible Laravel 12 et 13).
# Copier TOUT le contenu de ce kit dans le projet (mêmes chemins), en écrasant
# Configurer .env : DB_DATABASE=soumafe_pilot, APP_LOCALE=fr, APP_TIMEZONE=Africa/Abidjan
php artisan migrate --seed
```

## Contenu
| Dossier | Contenu |
|---|---|
| database/migrations | 3 migrations groupées : référentiels, cœur (projets/sprints/tâches), suivi (saisie/objectifs/registres) |
| database/seeders | Rôles Spatie + profils, jours fériés CI 2026, référentiels, comptes de l'équipe |
| app/Models | 14 modèles Eloquent avec relations |
| resources/views/layouts | Gabarit app.blade.php conforme à la maquette (barre latérale dégradée réductible) |
| routes/web.php | Squelette des routes par module (M1-M8) |

## Règles métier déjà encodées
- Tâche T1 : `sprint_id` obligatoire (contrainte + validation à ajouter dans le FormRequest).
- `jours_saisie` : alimenté par un job planifié à 18 h (à créer au Sprint 2 — US-13).
- Performance : jamais stockée, calculée à la volée (principe « une seule saisie »).

---
## SPRINT 1 — livraison 1 (US-06, US-07, US-08 partiel, US-19)
Nouveaux fichiers :
| Fichier | Rôle |
|---|---|
| config/soumafe.php | Seuils paramétrables : anticipation, tolérance, bonus effort volontaire, pondérations 50/30/20 et 60/40 |
| database/migrations/...000004_sprint1_cloture.php | Ajoute qualification, effort_volontaire, justification_requise sur taches |
| app/Services/ClotureService.php | Qualification de clôture (à temps / en retard / anticipée / non planifiée) en jours ouvrés CI + détection week-end/férié |
| app/Http/Requests/TacheRequest.php | Validation — règle d'or : T1 sans sprint refusée |
| app/Http/Controllers/TacheController.php | Kanban/liste, création rapide, changement de statut, duplication de la veille |
| app/Console/Commands/ImportDonneesExcel.php | `php artisan soumafe:import personnel.xlsx --projets=listes.xlsx` avec rapport d'anomalies |
| resources/views/taches/index.blade.php | Écran « Mes activités » : kanban glisser-déposer, formulaire 3 clics, messages de clôture bienveillants |

Après copie : `php artisan migrate` (nouvelle migration) puis tester la saisie.
Reste au Sprint 1 : US-01/02/03 (écrans admin comptes-rôles), US-04/05 (référentiels + fériés).
