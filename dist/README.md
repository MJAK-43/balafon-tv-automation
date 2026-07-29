# Installation Windows

Ce dossier contient les artefacts prêts à être utilisés par les testeurs.

## Installation recommandée

Télécharger puis exécuter :

```text
installer\Balafon-Setup.exe
```

L’installateur contient PHP 8.2.18, les dépendances Laravel et le frontend
compilé. PHP, Composer et Node.js ne sont pas nécessaires sur le PC du testeur.

```text
Taille : 45 161 881 octets
SHA-256 : 95218D5D3785186CF86941F53804F3A9685FCD27EC4F9A0621EC3E6821C72530
```

L’EXE n’est pas signé avec un certificat commercial. Windows SmartScreen peut
afficher un avertissement. Vérifier l’empreinte avant de l’autoriser.

## Installation portable

Télécharger et extraire :

```text
Balafon-0.1.0-rc.1-windows-x64.zip
```

Dans le dossier `Balafon` extrait :

1. exécuter `scripts\install_windows_tester.cmd` ;
2. exécuter `scripts\start_windows_local.cmd` ;
3. ouvrir `http://127.0.0.1:8080`.

```text
Taille : 62 927 301 octets
SHA-256 : 346787A205959EFC1E8B1AF3E8549908DFC2E54DD1CCABACEF6D741CA2696E31
```

Ne pas exécuter `install_windows_tester.cmd` depuis la racine des sources
clonées : ce script nécessite le contenu du ZIP ou de l’EXE.
