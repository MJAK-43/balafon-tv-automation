# Balafon Broadcast Manager - Roadmap de mise en oeuvre

## Ordre final recommande

0. Foundation + Validation API vMix + System Diagnostics
1. Auth + Roles
2. Bibliotheque Media
3. Chaines
4. Programmation TV
5. Detection Conflits
6. Automatisation Scheduler
7. Logs de Diffusion
8. Gestion Lives
9. Notifications
10. Dashboard Temps Reel
11. Publicites
12. Jingles
13. Rapports
14. Assistant IA

## Logique de cet ordre

- `Foundation + Validation API vMix + System Diagnostics` passe en premier car c'est le risque technique central du produit.
- cette phase prepare aussi le logiciel a etre installe sur plusieurs postes Windows sans intervention forte d'un developpeur.
- `Auth + Roles` doit preciser qui peut programmer, diffuser, corriger ou superviser.
- `Bibliotheque Media`, `Chaines` et `Programmation TV` forment le coeur metier minimal.
- `Detection Conflits` doit arriver avant l'automatisation pour eviter d'automatiser une grille invalide.
- `Automatisation Scheduler` et `Logs de Diffusion` etablissent ensuite la chaine critique exploitable.
- `Gestion Lives` s'ajoute sur une base deja stable de planification et de diffusion.
- `Notifications` et `Dashboard Temps Reel` viennent renforcer l'exploitation.
- `Publicites`, `Jingles`, `Rapports` et `Assistant IA` sont des couches de valeur ajoutee, pas le noyau du POC.

## Livrables par phase

### 0. Foundation + Validation API vMix + System Diagnostics

- matrice des commandes vMix necessaires
- specification du client HTTP Laravel
- procedure de test de connectivite
- gestion des erreurs et timeouts
- diagnostic OS, RAM, CPU, disque, reseau, PostgreSQL, Redis, Scheduler et Queue Workers
- base de compatibilite produit pour un futur installateur Windows

### 1. Auth + Roles

- login / logout / profil courant
- roles `admin`, `planner`, `operator`, `viewer`
- protection API et ecrans

### 2. Bibliotheque Media

- upload et listing des medias
- metadonnees techniques
- tags et filtres

### 3. Chaines

- CRUD des chaines
- statut actif / inactif
- parametres de diffusion

### 4. Programmation TV

- CRUD des programmes
- affectation media / live
- timeline de grille

### 5. Detection Conflits

- conflits de chevauchement
- medias absents ou invalides
- vMix non configure

### 6. Automatisation Scheduler

- scan des diffusions a lancer
- envoi des commandes vMix
- verrouillage Redis

### 7. Logs de Diffusion

- historique des commandes vMix
- journal des actions operateur et systeme
- trace des echecs

### 8. Gestion Lives

- creation d'un live
- preparation et pilotage
- integration grille / vMix

### 9. Notifications

- emails d'alerte
- journal d'envoi

### 10. Dashboard Temps Reel

- vision on-air
- prochaines diffusions
- incidents et statut systeme

### 11. Publicites

- insertion de spots dans la grille
- classification publicitaire

### 12. Jingles

- insertion d'habillage automatique
- regles avant / apres programme

### 13. Rapports

- rapports de diffusion
- rapports d'incidents

### 14. Assistant IA

- suggestions de programmation
- aide a la detection d'anomalies
- support operateur non critique

## Dependances structurantes

- `Programmation TV` depend de `Bibliotheque Media` et `Chaines`
- `Detection Conflits` depend de `Programmation TV`
- `Automatisation Scheduler` depend de `Foundation + Validation API vMix + System Diagnostics`, `Programmation TV` et `Detection Conflits`
- `Logs de Diffusion` depend de `Automatisation Scheduler`
- `Gestion Lives` depend de `Chaines`, `Programmation TV` et `Validation API vMix`
- `Dashboard Temps Reel` depend de `Logs de Diffusion`, `Notifications` et `Gestion Lives`
- `Rapports` depend de `Logs de Diffusion`
- `Assistant IA` depend idealement de `Rapports`, `Programmation TV` et `Logs de Diffusion`

## MVP reellement exploitable

Le premier MVP exploitable en conditions quasi reelles est atteint a la fin de la phase 7:

1. API vMix validee
2. Auth et securite minimales
3. Medias et chaines gerables
4. Grille programmable
5. Conflits detectes
6. Automation active
7. Logs exploitables

Les phases suivantes augmentent surtout la qualite d'exploitation, la valeur business et l'intelligence produit.
