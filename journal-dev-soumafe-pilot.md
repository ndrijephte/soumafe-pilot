# Journal de développement — soumafe-pilot

## Session 30 sept 2026 — Corrections parc auto

### 1. Recouvrement pris en compte dans objectif recette (fiche véhicule)
- `ParcController::ficheVehicule()` — ajout query `$recouvMois` + `$recetteMoisAvecRecouv` = recette + recouvrement
- `taux_recette` utilise désormais `$recetteMoisAvecRecouv` au lieu de `$recetteMois`
- Le reporting incluait déjà le recouvrement — seule la fiche véhicule manquait

### 2. Fiche véhicule : retrait onglet Dépenses bureau
- Suppression query `$depensesMois` et collection `$depenses` dans `ficheVehicule()`
- Retrait du JS "Panel Dépenses"
- Onglets fiche véhicule : recettes, chauffeurs, pannes, pénalités, maintenance, recouvrements

### 3. Retrait fiche collaborateur du reporting
- Suppression du lien dans `reporting/parc.blade.php` et `reporting/index.blade.php`

### 4. Pannes : bandeau coût total
- Ajout `$coutTotalPannes` + bandeau rouge résumé dans l'onglet Pannes

### 5. Maintenance : bandeau coût total + dernière réalisation
- Ajout `$coutTotalMaint` + bandeau jaune résumé
- Nouvelle colonne "Dernière réal." dans le tableau maintenance
- Fiche véhicule JS enrichi avec colonnes Dernière réalisation, Prochaine échéance, Coût, Statut

### 6. Retrait champ véhicule du formulaire autre dépense (bureau)
- Suppression du select `vehicule_id` dans le formulaire dépense bureau

### 7. Fix KPI blocs parc auto — recette incorrecte
- `calculerKpi()` : ajout recouvrement perçu dans `taux_recette`
- Vue Blade KPI : nouveau bloc "Recette mois" avec détail recouvrement et objectif
- `ficheChauffeur()` : recette_totale = recette + recouvrement

### 8. Fix duplication recouvrements
- Anti-doublon ajouté dans `storeRecetteIndividuelle()` et `storePenalite()`
- Migration `2026_09_30_200000_fix_duplicate_recouvrements.php` : purge doublons existants

### 9. Éditeur bloc WYSIWYG pour CR Demo
- Format `blocs_v1` : modules > sections > blocs (texte, tableau_taches, score, actions, reference)
- Éditeur DOM avec toolbar riche, sérialisation JSON
- 10 types de référence avec icônes SVG 2D
- Affichage hiérarchique + backward compat ancien format
- Export DOCX : dispatch `blocs_v1` vs legacy
- `blocAnnote()` étendu avec 5 nouveaux types

### Fichiers modifiés (sessions 30 sept)
- `app/Http/Controllers/ParcController.php`
- `resources/views/parc/index.blade.php`
- `resources/views/reporting/parc.blade.php`
- `resources/views/reporting/index.blade.php`
- `database/migrations/2026_09_30_200000_fix_duplicate_recouvrements.php`
- `resources/views/comptes-rendus/edit.blade.php`
- `resources/views/comptes-rendus/show.blade.php`
- `app/Http/Controllers/CompteRenduController.php`

---

## Session 27 oct 2026 — Module Scrum (approche validée)

### 10. Analyse fichier Scrum LEVRY (REVIEW_SPRINT_LEVRY_GCA.xlsx)
- 14 feuilles analysées : Product Backlog (40 rows, 7 modules), Sprint 1 Backlog (15 rows, 6 US), Sprint Planning 1 (54 rows, statuts D/T/TT/E/ER), Daily 1-9 (9 feuilles, 4 rôles tournants, 9 membres), Review Sprint (54 rows), Retrospective (8 rows, 3 thèmes)

### Principe cyclique
Chaque projet vit par sprints successifs. À chaque sprint :
Product Backlog (màj) → Sprint Backlog → Sprint Planning → Daily 1..N → Review → Retrospective → Sprint suivant.
Les actions retro alimentent le backlog du sprint suivant.

### Tables nouvelles (validé)
| Table | Colonnes clés |
|-------|--------------|
| `user_stories` | projet_id, numero, module, intitule, exigences, taches_techniques, priorite, statut, effort_estime |
| `sprint_user_stories` (pivot) | sprint_id, user_story_id, statut_sprint (a_faire/en_cours/termine) |
| `daily_scrums` | sprint_id, numero_jour, date_reunion, commentaire_general, observation |
| `daily_scrum_entrees` | daily_scrum_id, user_id, taches_hier, taches_jour, duree_restante, defis |
| `projet_collaborateurs` | projet_id, user_id, role_projet, date_ajout, actif |

### Enrichissements existants
- `Tache.statut` : D (Démarré), T (Terminé), TT (Terminé Testé), E (En cours), ER (En retard)
- Nouveaux types CompteRendu : `review_sprint`, `retrospective` avec JSON dédié

### Auto-fill Daily Scrum
- taches_hier = taches_jour du Daily J-1 pour chaque membre
- taches_jour = tâches sprint planning non terminées attribuées au membre
- durée restante = durée planifiée − jours écoulés
- rôles = rotation via AffectationReunion existant

### Export Excel archive
- Génération xlsx 14 onglets reproduisant la structure LEVRY, par sprint ou projet entier
- Bouton "Exporter archive Scrum" depuis fiche projet/sprint

### Ajout collaborateur dynamique
- Table `projet_collaborateurs` : ajout/retrait à tout moment sur n'importe quel projet
- Le Daily Scrum liste automatiquement les collaborateurs actifs du projet

### Phases d'implémentation
1. Migrations + Models
2. Gestion collaborateurs projet (ajout/retrait dynamique)
3. CRUD Sprint Planning
4. Daily Scrum + auto-fill
5. Review + Retrospective
6. Export Excel archive 14 onglets
