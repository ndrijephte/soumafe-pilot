# Déploiement automatique sur cPanel

Le workflow `.github/workflows/deploy-cpanel.yml` teste et déploie automatiquement
l'application après chaque push sur la branche `main`. Il peut aussi être lancé
manuellement depuis **GitHub > Actions > Deploy to cPanel**.

## 1. Préparer cPanel

1. Créer un dossier pour l'application, par exemple `/home/utilisateur/soumafe-pilot`.
2. Faire pointer le domaine ou sous-domaine vers
   `/home/utilisateur/soumafe-pilot/public`.
3. Sélectionner PHP 8.3 ou une version supérieure compatible avec le projet.
4. Créer une base MySQL et un utilisateur depuis cPanel.
5. Créer manuellement le fichier `.env` dans le dossier de l'application à partir
   de `.env.example`. Utiliser `APP_ENV=production`, `APP_DEBUG=false`, l'URL du
   site et les identifiants MySQL de cPanel.
6. Donner les droits d'écriture PHP aux dossiers `storage` et `bootstrap/cache`.

Ne placez jamais le fichier `.env` dans GitHub. Le workflow le préserve pendant
les déploiements.

## 2. Configurer les secrets GitHub

Dans **GitHub > Settings > Secrets and variables > Actions**, ajouter :

| Secret | Exemple |
|---|---|
| `CPANEL_FTP_HOST` | `ftp.example.com` |
| `CPANEL_FTP_USERNAME` | le compte FTP cPanel |
| `CPANEL_FTP_PASSWORD` | le mot de passe FTP |
| `CPANEL_FTP_SERVER_DIR` | `/soumafe-pilot` ou le chemin visible depuis le compte FTP |

Le chemin FTP n'est pas toujours le chemin système `/home/...`. Vérifiez le
dossier racine affiché par le gestionnaire de fichiers ou le compte FTP cPanel.

## 3. Première mise en production

Après le premier déploiement, exécuter une seule fois depuis le Terminal cPanel :

```bash
cd /home/utilisateur/soumafe-pilot
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
```

Si cPanel ne fournit pas de terminal, générer `APP_KEY` localement et la copier
dans le `.env`, puis importer la base avec phpMyAdmin. FTP seul ne peut pas lancer
les migrations ou les commandes Artisan.

## 4. Déploiements suivants

Un push sur `main` lance les tests et le build. Le transfert FTP ne commence que
si ces étapes réussissent. Les migrations de base de données restent à lancer
manuellement avec `php artisan migrate --force`, car le compte ne dispose pas de
SSH.

Le premier transfert inclut toutes les dépendances PHP et peut être long. Les
transferts suivants sont différentiels et ne renvoient que les fichiers modifiés.

Les répertoires persistants de `storage` et le fichier `.env` sont exclus de la
synchronisation destructive afin de préserver la configuration et les données.
