# GUIDE DE TEST — SPRINT 1, LIVRAISON 1
Application SOUMAFE PILOT · Windows · ~30 minutes

## 1. Préparer l'environnement (une seule fois)

Le plus simple sur Windows : **Laragon** (PHP + MySQL + serveur en un installeur).

1. Télécharger Laragon Full : https://laragon.org → installer → Démarrer tout.
2. Installer Composer si absent : https://getcomposer.org (Laragon Full l'inclut déjà).
3. Ouvrir le terminal Laragon (bouton « Terminal ») et vérifier :
   ```bash
   php -v        # attendu : PHP 8.2 ou 8.3
   composer -V
   ```

## 2. Créer le projet et poser le kit

```bash
cd C:\laragon\www
composer create-project laravel/laravel soumafe-pilot
cd soumafe-pilot
composer require laravel/breeze spatie/laravel-permission maatwebsite/excel
php artisan breeze:install blade      # répondre : blade, no dark, PHPUnit
```

Copier le contenu de `KIT_SPRINT0\` dans `C:\laragon\www\soumafe-pilot\` en **écrasant** les fichiers existants (`app`, `database`, `resources`, `routes`, `config`, `public`).

Éditer `.env` :
```
APP_LOCALE=fr
APP_TIMEZONE=Africa/Abidjan
DB_CONNECTION=mysql
DB_DATABASE=soumafe_pilot
DB_USERNAME=root
DB_PASSWORD=
```

Créer la base (menu Laragon > MySQL > créer `soumafe_pilot`, ou via terminal), puis :
```bash
php artisan migrate --seed
php artisan serve
```

Ouvrir http://localhost:8000 — se connecter avec :
**ndrijephtekoffi@gmail.com** / **ChangezMoi!2026**

## 3. Scénarios de test (cocher au fur et à mesure)

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| T-01 | Connexion | Login avec votre compte | Arrivée sur « Mes activités », barre latérale bleue avec logo |
| T-02 | Création T2 en 3 clics | + Nouvelle tâche → intitulé → Enregistrer | Tâche dans « En cours » (défauts intelligents appliqués) |
| T-03 | Règle d'or T1 | + Nouvelle tâche → type T1 → laisser Sprint vide → Enregistrer | **Refus** avec message « aucune tâche projet hors sprint » |
| T-04 | Kanban | Glisser une tâche de « À faire » vers « En cours » | Toast « Statut mis à jour », colonne changée |
| T-05 | Clôture à temps | Créer une tâche échéance aujourd'hui → glisser vers « Terminé » | Toast « Terminée à temps — bravo », aucune justification |
| T-06 | Clôture en retard | Créer une tâche échéance d'hier (modifier en base ou créer hier) → Terminé | Toast demandant une **justification en commentaire** |
| T-07 | Clôture anticipée | Créer une tâche échéance dans 5 jours → Terminé aujourd'hui | Toast « Terminée en avance : un mot sur la raison ? » |
| T-08 | Effort volontaire | Changer la date système au samedi OU insérer une tâche avec date_fin_reelle un samedi → Terminé | Message « Effort week-end/férié comptabilisé », champ effort_volontaire = 1 en base |
| T-09 | Reprendre la veille | Créer une tâche datée d'hier non terminée (en base) → bouton « Reprendre la veille » | Copie créée à aujourd'hui |
| T-10 | Filtres | Filtrer par projet puis par type | Kanban filtré |
| T-11 | Import Excel | `php artisan soumafe:import "D:\BUREAU_SOUMAFE\02_PILOTAGE_INDICATEURS\OUTILS_KNJ\peronnel_soumafe2.xlsx"` | Comptes créés + rapport d'anomalies lisible |
| T-12 | Fériés en base | Table `jours_feries` : 14 lignes 2026 (dont 07/08 Indépendance) | OK |

## 4. Consigner les retours

Noter chaque anomalie ou ajustement souhaité (libellé, écran, comportement attendu) —
transmettez-moi la liste brute, je corrige à la prochaine itération avant d'attaquer
les écrans d'administration (US-01→05).

*Astuce test T-06/T-08 sans changer la date système : dans phpMyAdmin (Laragon),
modifier directement `echeance` ou `date_fin_reelle` de la tâche puis re-glisser.*
