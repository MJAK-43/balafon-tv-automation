# Windows Local Distribution

This document describes the simplest path to hand Balafon to a tester as a Windows-native application candidate without requiring Docker.

## Goal

Provide a local test package that:

- runs directly on Windows
- uses SQLite instead of PostgreSQL
- uses `file` cache/session and `sync` queue to remove Redis dependency
- creates a default media root automatically
- can use a bundled PHP runtime from `runtime\php\php.exe`
- starts through PowerShell scripts today, with a Windows installer later

## Current scripts

- `scripts/install_windows_local.ps1`
- `scripts/install_windows_tester.ps1`
- `scripts/start_windows_local.ps1`
- `scripts/stop_windows_local.ps1`
- `scripts/prepare_windows_release.ps1`
- `start_balafon_local.cmd`
- `stop_balafon_local.cmd`
- `.env.windows.local.example`

## What the install script does

`install_windows_local.ps1`:

1. checks for `php`, `composer`, and `npm`
2. creates a local SQLite database at `database\balafon.sqlite`
3. creates a default media root at `C:\ProgramData\Balafon\Media`
4. writes a Windows-local `.env`
5. runs `composer install`
6. runs `npm install`
7. generates `APP_KEY`
8. runs `php artisan migrate`
9. runs `php artisan db:seed`
10. builds frontend assets with `npm run build`

## What the start script does

`start_windows_local.ps1` launches:

- `php artisan serve --host=127.0.0.1 --port=8080`

This launches:

- the Laravel HTTP server
- `schedule:work` for automation and monitoring jobs

This intentionally uses compiled assets from `npm run build` so the tester does not need Vite dev mode.

## Simplest local launch

For a local operator on the same machine, use the root launchers:

```bat
start_balafon_local.cmd
stop_balafon_local.cmd
```

`start_balafon_local.cmd` starts Balafon on `http://127.0.0.1:8081`.

## Tester package flow

Prepare the release on the build machine:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\prepare_windows_release.ps1
```

or simply:

```bat
scripts\prepare_windows_release.cmd
```

This creates:

- `dist\windows-release\Balafon`

The generated folder already includes:

- `vendor`
- compiled frontend assets
- installer/start/stop scripts
- bundled `runtime\php` if you provide it before building the release

The tester then runs:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\install_windows_tester.ps1
powershell -ExecutionPolicy Bypass -File .\scripts\start_windows_local.ps1
```

or uses the `.cmd` launchers:

```bat
scripts\install_windows_tester.cmd
scripts\start_windows_local.cmd
```

So the tester no longer needs `composer install` or `npm install`.
If the release also contains `runtime\php\php.exe`, the tester does not need a separate PHP installation either.

## Optional installer packaging

An Inno Setup definition is included at:

- `packaging/windows/BalafonSetup.iss`

After generating `dist\windows-release\Balafon`, compile that `.iss` file with Inno Setup to produce a Windows installer executable.

Helper script:

```bat
scripts\compile_windows_installer.cmd
```

## Developer workflow from a Git clone

This workflow is intended for colleagues who need to modify the source code.

1. Install PHP 8.2+, Composer, and Node.js 20+.
2. Extract the Balafon project folder.
3. Run:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\install_windows_local.ps1
```

4. Then run:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\start_windows_local.ps1
```

5. Open `http://127.0.0.1:8080`.

## Non-technical tester workflow

Testers should receive the generated Windows release or installer rather than
cloning the Git repository. The generated package contains `vendor` and the
compiled frontend assets, so Composer and Node.js are not needed on the tester
machine. If `runtime\php\php.exe` is bundled, PHP is not needed either.

Generated releases must never contain:

- a project `.env`
- a SQLite database from the build machine
- application logs or cached runtime files
- user-uploaded media

## Remaining external prerequisite

Today the only prerequisite still outside the repo is a Windows PHP runtime with the required extensions:

- `openssl`
- `mbstring`
- `fileinfo`
- `pdo_sqlite`
- `sqlite3`

Composer and Node.js are only needed on the build machine, not on the tester machine, if you distribute the prepared release package.

## Fully self-contained package

To avoid requiring PHP on the tester machine, pass a clean Windows PHP runtime
folder to the release preparation script:

```powershell
$phpRuntime = Split-Path -Parent (Get-Command php).Source
.\scripts\prepare_windows_release.ps1 -PhpRuntimeRoot $phpRuntime
```

The script verifies `php.exe` and all required extensions, removes
machine-specific paths from `php.ini`, and places the runtime only in the
generated package. The PHP runtime is not committed to Git.
