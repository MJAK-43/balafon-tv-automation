# Balafon Broadcast Manager

Balafon Broadcast Manager est une application Windows de gestion et
d’automatisation de diffusion TV pilotant vMix par son API HTTP.

Le projet utilise Laravel 12 pour l’API et Vue 3 pour l’interface. Il prend en
charge la médiathèque, les playlists, la programmation, l’automatisation,
l’habillage graphique, les diagnostics et la journalisation des commandes vMix.

## État actuel

Le projet peut être utilisé dès maintenant par des collègues pour :

- installer et démarrer Balafon sur Windows ;
- découvrir et tester l’interface ;
- gérer les médias, catégories et tags ;
- créer des chaînes, playlists et programmations ;
- tester l’automatisation avec le simulateur vMix ;
- connecter une instance réelle de vMix et participer à la recette.

La version actuelle est une **release candidate** et non une version de
production certifiée. Les tests automatiques et le package Windows sont validés,
mais la matrice réelle vMix 25/29 doit encore être terminée.

| Élément | État |
| --- | --- |
| Tests Laravel | Validé — 27 tests, 202 assertions |
| Audit npm | Validé — 0 vulnérabilité connue |
| Build Vue/Vite | Validé |
| Installation SQLite neuve | Validé |
| Package Windows avec PHP intégré | Validé |
| Installation dans un chemin avec espaces et accents | Validé |
| Démarrage HTTP et scheduler | Validé |
| Simulateur vMix | Validé |
| vMix 29.0.0.48 | Audit antérieur réussi, recette de la RC encore bloquée |
| vMix 25 | Non testé — environnement séparé nécessaire |
| Installateur Windows `.exe` | Non généré — Inno Setup 6 requis |

Le rapport détaillé se trouve dans
[docs/release-validation-2026-07-29.md](docs/release-validation-2026-07-29.md).

## Installation recommandée pour un collègue testeur

Le testeur ne doit pas cloner le dépôt et ne doit pas installer Composer ou
Node.js. Il doit recevoir le package :

```text
Balafon-0.1.0-rc.1-windows-x64.zip
```

Le package validé contient PHP 8.2.18, les dépendances Laravel et les fichiers
frontend compilés.

Empreinte du package validé :

```text
SHA-256
BF0B461318F31A69650074C2EFAF4ADCA0BDF0D7156DD771317CE942BEA55F85
```

Procédure :

1. Extraire le ZIP dans un dossier local, par exemple `C:\Balafon`.
2. Exécuter `scripts\install_windows_tester.cmd`.
3. Exécuter `scripts\start_windows_local.cmd`.
4. Ouvrir `http://127.0.0.1:8080`.
5. Pour arrêter Balafon, exécuter `scripts\stop_windows_local.cmd`.

L’installation crée :

- une base SQLite locale dans `database\balafon.sqlite` ;
- le dossier média `C:\ProgramData\Balafon\Media` ;
- une clé d’application propre au PC ;
- le lien de stockage Laravel ;
- les migrations et les données initiales.

### Accès initial

- Email : `admin@balafon.local`
- Mot de passe : `password`

Ces identifiants sont réservés aux tests. Le mot de passe doit être remplacé
avant toute utilisation réelle ou exposition sur un réseau.

## Utilisation avec vMix

Balafon propose deux modes :

- `VMIX_DRIVER=mock` : simulation sans installation de vMix ;
- `VMIX_DRIVER=real` : connexion à une vraie instance vMix.

Pour vMix installé sur le même PC :

```dotenv
VMIX_DRIVER=real
VMIX_HOST=127.0.0.1
VMIX_PORT=8088
```

Dans vMix :

1. ouvrir les paramètres ;
2. activer le **Web Controller** ;
3. vérifier que le port est `8088` ;
4. autoriser le port 8088 dans le pare-feu si Balafon est installé sur un autre
   PC ;
5. vérifier l’accès à `http://127.0.0.1:8088/api`.

Si Balafon et vMix sont sur deux PC différents, les chemins locaux de médias ne
sont pas interchangeables. Les médias doivent être accessibles par vMix,
idéalement avec un partage réseau UNC.

La compatibilité vMix ne doit être déclarée qu’après exécution de la checklist :
[docs/release-checklist-vmix-25-29.md](docs/release-checklist-vmix-25-29.md).

## Installation depuis Git pour un développeur

Cette procédure est destinée aux collègues qui doivent modifier le code.

Prérequis :

- Git ;
- PHP 8.2 ou supérieur ;
- Composer ;
- Node.js 20 ou supérieur ;
- extensions PHP `openssl`, `mbstring`, `fileinfo`, `pdo_sqlite` et `sqlite3`.

Installation Windows locale :

```powershell
git clone https://github.com/MJAK-43/balafon-tv-automation.git
cd balafon-tv-automation
powershell -ExecutionPolicy Bypass -File .\scripts\install_windows_local.ps1
powershell -ExecutionPolicy Bypass -File .\scripts\start_windows_local.ps1
```

Le script utilise `composer install`, `npm ci`, SQLite et les assets frontend
compilés.

### Développement avec Docker

```powershell
Copy-Item .env.example .env
composer install
npm ci
docker compose up -d --build
php artisan migrate
php artisan db:seed
npm run dev
```

Pour atteindre vMix depuis Docker sous Windows :

```dotenv
VMIX_DRIVER=real
VMIX_HOST=host.docker.internal
VMIX_PORT=8088
```

## Tests et contrôles qualité

```powershell
composer install --no-interaction
npm ci
npm audit
php artisan test
npm run build
```

Résultat de la release candidate :

```text
Tests: 27 passed
Assertions: 202
npm audit: 0 vulnerabilities
```

Le build Vite produit encore un avertissement non bloquant : le chunk
JavaScript principal dépasse 500 kB après minification.

## Construire un package Windows portable

Sur la machine de construction :

```powershell
composer install --no-interaction
npm ci
npm run build
$phpRuntime = Split-Path -Parent (Get-Command php).Source
.\scripts\prepare_windows_release.ps1 -PhpRuntimeRoot $phpRuntime
```

Le package est créé dans :

```text
dist\windows-release\Balafon
```

Le script :

- inclut `vendor` et le build frontend ;
- peut intégrer un runtime PHP portable ;
- adapte `php.ini` pour un fonctionnement après déplacement ;
- refuse ou supprime les bases SQLite locales ;
- exclut `.env`, les logs, les caches et les médias utilisateurs.

Le dossier `dist` et le runtime PHP ne doivent jamais être commités dans Git.
Ils doivent être distribués comme fichiers de release.

## Générer l’installateur Windows `.exe`

Le fichier de configuration Inno Setup est déjà fourni :

```text
packaging\windows\BalafonSetup.iss
```

Prérequis :

1. installer **Inno Setup 6** ;
2. générer d’abord `dist\windows-release\Balafon` ;
3. lancer :

```powershell
scripts\compile_windows_installer.cmd
```

Le résultat attendu est :

```text
dist\installer\Balafon-Setup.exe
```

Si le message `Inno Setup compiler not found` apparaît, vérifier la présence de
`ISCC.exe` dans l’un de ces dossiers :

```text
C:\Program Files (x86)\Inno Setup 6\
C:\Program Files\Inno Setup 6\
```

L’absence de l’EXE ne bloque pas les tests : le ZIP portable et
`install_windows_tester.cmd` restent utilisables.

## Données à ne jamais publier

Ne jamais ajouter dans Git ou dans un package de test :

- `.env` ;
- bases `database\*.sqlite` ;
- médias utilisateurs ;
- logs et caches ;
- `vendor`, `node_modules` et `public\build` dans le dépôt Git ;
- profils de navigateur et dossiers `.tmp` ;
- runtime PHP dans le dépôt Git.

Les exemples `.env.example` et `.env.windows.local.example` peuvent être
versionnés, car ils ne contiennent pas de secret réel.

## Téléversements volumineux

La configuration Docker accepte des fichiers supérieurs à 5 Go :

- `upload_max_filesize=6144M` ;
- `post_max_size=6144M` ;
- `client_max_body_size=6G`.

Avec WAMP ou un autre serveur local, appliquer les mêmes limites dans `php.ini`
et dans la configuration du serveur web.

## Documentation complémentaire

- [Distribution Windows](docs/windows-local-distribution.md)
- [Checklist vMix 25/29](docs/release-checklist-vmix-25-29.md)
- [Rapport de validation](docs/release-validation-2026-07-29.md)
- [Audit API vMix](docs/vmix-api-audit.md)
- [Comportement de lecture vMix](docs/vmix-playback-behavior.md)
- [Architecture](docs/poc-architecture.md)
