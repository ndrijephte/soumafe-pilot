# Initialisation Git & Push GitHub — SOUMAFE PILOT

## 1. Créer le dépôt sur GitHub

1. Aller sur https://github.com/new
2. Nom du dépôt : `soumafe-pilot`
3. Visibilité : **Private**
4. Ne PAS cocher "Add README" ni ".gitignore" (déjà fourni)
5. Cliquer **Create repository**

## 2. Initialiser Git dans le projet Laragon

Ouvrir le **terminal Laragon** puis :

```bash
cd C:\laragon\www\soumafe-pilot

git init
git add .
git commit -m "Sprint 0+1 : socle Laravel, auth maison, kanban tâches, admin comptes/référentiels/fériés"

git branch -M main
git remote add origin https://github.com/VOTRE_UTILISATEUR/soumafe-pilot.git
git push -u origin main
```

Remplacer `VOTRE_UTILISATEUR` par votre nom d'utilisateur GitHub.

## 3. Bonnes pratiques pour la suite

- Créer une branche par sprint : `git checkout -b sprint-2`
- Committer souvent avec des messages descriptifs
- Merger dans `main` une fois le sprint testé et validé
