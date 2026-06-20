# Balafon Broadcast Manager - Deployment Strategy

## 1. Objectif

Definir une strategie de deploiement compatible avec trois cibles:

- `Development` via Docker
- `Production Windows native`
- `Future packaging Electron`

L'objectif n'est pas seulement de lancer l'application localement, mais de preparer un produit installable, parametrable et maintenable sur plusieurs postes Windows lies a vMix.

## 2. Principes directeurs

- une base applicative unique Laravel + Vue
- separation stricte entre logique metier et specifics d'environnement
- configuration par variables d'environnement et providers
- services critiques abstraits par interfaces
- mode `real` ou `mock` pour l'integration vMix
- possibilite future d'un installateur Windows assiste

## 3. Cibles de deploiement

### 3.1 Development - Docker

Usage:

- developpement quotidien
- tests fonctionnels
- integration backend/frontend
- validation API vMix depuis l'environnement conteneurise

Composants:

- `nginx`
- `php-fpm`
- `postgres`
- `redis`
- `mailpit`
- `vite`

Contraintes:

- acces a vMix via `host.docker.internal`
- volumes de code sous Windows potentiellement lents
- scheduler et queue a lancer explicitement

### 3.2 Production - Windows native

Usage:

- installation dans une chaine TV
- acces local a vMix
- exploitation stable sur poste dedie

Composants cibles:

- PHP 8.4
- PostgreSQL
- Redis
- Nginx ou Caddy ou Apache selon packaging final
- service scheduler
- service queue worker

Contraintes:

- detection reelle de vMix et des prerequis Windows
- configuration simple pour l'operateur
- tolerance forte aux erreurs locales
- audit et diagnostic exploitables sans developpeur

### 3.3 Future packaging - Electron

Usage:

- enveloppe desktop pour simplifier l'installation et l'exploitation
- UX d'administration locale plus integree
- base pour un futur `BalafonBroadcastSetup.exe`

Perimetre futur:

- shell desktop
- auto-update
- assistants d'installation
- ecrans de diagnostic machine hote
- orchestration de configuration initiale

Principe:

- Electron ne remplace pas l'API metier
- Electron agit comme hote UI/orchestrateur local autour du backend

## 4. Strategie d'abstraction

Les integrations remplaçables doivent dependre d'interfaces:

- `VmixProviderInterface`
- `NotificationProviderInterface`
- `StorageProviderInterface`

### vMix

- `RealVmixProvider`: dialogue reel avec vMix
- `MockVmixProvider`: simulation complete pour demo, tests et machines sans vMix

Decision:

- la selection du provider doit dependre d'une configuration type `VMIX_DRIVER=real|mock`

## 5. Configuration par environnement

### Development

- `APP_ENV=local`
- `VMIX_DRIVER=real` ou `mock`
- `VMIX_HOST=host.docker.internal`
- `VMIX_PORT=8088`

### Production Windows

- `APP_ENV=production`
- `VMIX_DRIVER=real`
- `VMIX_HOST=localhost`
- `VMIX_PORT=8088`

### Electron futur

- `APP_ENV=desktop`
- `VMIX_DRIVER=real|mock`
- configuration pilotee par assistant local

## 6. Strategie de packaging produit

Le produit doit etre pense en deux couches:

### Couche coeur

- Laravel API
- Vue admin
- domaines metier
- migrations
- scheduler
- queue

### Couche installation / exploitation

- script ou assistant de verification prerequis
- detection vMix
- configuration `.env`
- creation administrateur
- verification PostgreSQL / Redis
- diagnostic de sante

Cette separation permettra plus tard de construire:

- un installateur Windows
- une variante Electron
- une procedure de maintenance simplifiee

## 7. Prerequis de production Windows

Les prerequis a verifier plus tard via le domaine `System` sont:

- version Windows compatible
- vMix installe ou mode mock explicitement active
- acces API vMix
- PostgreSQL operationnel
- Redis operationnel
- espace disque minimal
- RAM minimale
- compte administrateur applicatif cree

## 8. Risques de deploiement

### Docker -> Windows host

- resolution reseau differente entre conteneur et machine hote
- latence I/O des volumes

### Windows native

- heterogeneite des postes clients
- prerequis manquants
- droits systeme insuffisants

### Electron

- complexite d'empaquetage
- gestion conjointe backend + runtime desktop
- supervision de processus locaux

## 9. Recommandation immediate

Pour la phase 0:

1. implementer le mode `Docker` comme environnement principal de dev
2. prevoir des maintenant le mode `Windows native` dans la config
3. introduire les interfaces providers et le mode `mock`
4. preparer le domaine `System` pour l'installation future
5. garder Electron au niveau strategie, sans implementation immediate
