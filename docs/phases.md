# Balafon Broadcast Manager - Roadmap de mise en oeuvre

## Cadrage client obligatoire

Les regles suivantes sont maintenant obligatoires et gouvernent toutes les prochaines phases:

1. Balafon Broadcast Manager est la source de verite metier.
2. vMix est uniquement le moteur d'execution temps reel.
3. Les medias sont geres dans Balafon.
4. Les playlists sont gerees dans Balafon.
5. La programmation est geree dans Balafon.
6. L'automatisation est pilotee par Balafon via Scheduler + API vMix + polling XML.

## Hors perimetre MVP

Ne pas developper dans ce cycle:

- IA
- RAG
- assistant conversationnel
- generation automatique de programmes
- mediatheque complexe
- S3
- NAS
- gestion documentaire

## Ordre de delivery MVP metier

0. Foundation + Validation API vMix + System Diagnostics
1. Auth + Roles
2. Media
3. Playlist
4. Scheduling TV
5. Detection Conflits
6. Automatisation Broadcast
7. Logs de Diffusion
8. Live
9. Notifications
10. Dashboard Temps Reel

## Phases post-MVP

11. Publicites
12. Jingles
13. Rapports
14. Assistant IA

## Logique de cet ordre

- `Foundation + Validation API vMix + System Diagnostics` reste en premier car c'est le risque technique central et le socle de demonstration.
- `Auth + Roles` verrouille les responsabilites operateur avant les ecrans metier.
- `Media` vient avant tout le reste car le MVP doit referencer les fichiers deja presents sur le stockage de la chaine.
- `Playlist` construit l'ordre editorial a partir des assets references dans Balafon.
- `Scheduling TV` transforme les playlists en grille diffusable plusieurs jours a l'avance.
- `Detection Conflits` doit proteger la grille avant toute automatisation.
- `Automatisation Broadcast` realise la chaine critique `Schedule -> Playlist -> Media -> vMix`.
- `Logs de Diffusion` rendent le systeme auditable en demonstration et en exploitation.
- `Live`, `Notifications` et `Dashboard Temps Reel` renforcent ensuite l'usage de regie.

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

### 2. Media

- domaine `Media`
- entite `MediaAsset`
- referencement de medias existants sans duplication
- ajout / modification / suppression / recherche / filtres
- previsualisation rapide
- prise en charge du `file_path`, du nom et de la duree si disponible

### 3. Playlist

- completion du domaine `Playlist`
- entite `PlaylistItem`
- creation / modification / suppression de playlists
- ajout et retrait de medias
- reordonnancement fluide
- calcul automatique de la duree totale

### 4. Scheduling TV

- domaine `Scheduling`
- entite `Schedule`
- creation / modification / suppression de programmations
- duplication jour / semaine
- validation du planning
- timeline broadcast jour / semaine

### 5. Detection Conflits

- chevauchements de programmation
- playlists vides ou assets absents
- verification de fenetres invalides
- validation pre-automation

### 6. Automatisation Broadcast

- `BroadcastAutomationService`
- `ProcessScheduledBroadcastJob`
- `PlaylistExecutionService`
- `VmixExecutionService`
- workflow `Scheduler -> Schedule -> Playlist -> PlaylistItem -> MediaAsset -> RealVmixProvider -> vMix`
- supervision par polling XML

### 7. Logs de Diffusion

- journal complet `audit_logs`
- journal complet `vmix_command_logs`
- correlation entre programmation, playlist, media et commandes vMix
- traces des erreurs de diffusion

### 8. Live

- prise en charge des lives dans la grille
- placeholders live
- suivi de depassement
- integration au centre de supervision

### 9. Notifications

- alertes email sur erreurs critiques
- alertes de depassement ou d'echec automation
- journal d'envoi

### 10. Dashboard Temps Reel

- programme en cours
- programme suivant
- etat vMix
- version vMix
- inputs actifs
- activite recente
- alertes
- lives programmes
- nombre de playlists
- nombre de medias
- nombre de programmations

## Dependances structurantes

- `Playlist` depend de `Media`
- `Scheduling TV` depend de `Playlist` et `Channel` deja disponible via la fondation broadcast
- `Detection Conflits` depend de `Scheduling TV`
- `Automatisation Broadcast` depend de `Foundation + Validation API vMix + System Diagnostics`, `Scheduling TV` et `Detection Conflits`
- `Logs de Diffusion` depend de `Automatisation Broadcast`
- `Live` depend de `Scheduling TV`, `Dashboard Temps Reel` et `Validation API vMix`
- `Notifications` depend de `Automatisation Broadcast` et `Logs de Diffusion`
- `Dashboard Temps Reel` depend de `Logs de Diffusion`, `Notifications`, `Live` et `Vmix`

## MVP reellement exploitable

Le MVP metier de demonstration est atteint a la fin de la phase 10:

1. referencement de medias existants
2. creation d'une playlist
3. creation d'une programmation
4. declenchement automatique via Scheduler
5. lecture automatique dans vMix
6. journalisation complete
7. interface premium de supervision

## Resultat metier cible

Le parcours de demonstration attendu est le suivant:

1. un operateur reference des medias deja presents sur le stockage local
2. il compose une playlist
3. il programme cette playlist dans la grille
4. Laravel Scheduler declenche l'execution
5. Balafon pilote vMix sans intervention humaine
6. l'operateur supervise la diffusion et les alertes depuis le dashboard
