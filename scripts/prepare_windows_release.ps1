[CmdletBinding()]
param(
    [string]$ProjectRoot = '',
    [string]$OutputRoot = '',
    [string]$PhpRuntimeRoot = ''
)

$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
    $scriptRoot = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
    $ProjectRoot = (Resolve-Path (Join-Path $scriptRoot '..')).Path
}
elseif (-not [System.IO.Path]::IsPathRooted($ProjectRoot)) {
    $ProjectRoot = (Resolve-Path $ProjectRoot).Path
}

if ([string]::IsNullOrWhiteSpace($OutputRoot)) {
    $OutputRoot = Join-Path $ProjectRoot 'dist\windows-release'
}
elseif (-not [System.IO.Path]::IsPathRooted($OutputRoot)) {
    $OutputRoot = Join-Path $ProjectRoot $OutputRoot
}

$releaseRoot = Join-Path $OutputRoot 'Balafon'

if (-not (Test-Path (Join-Path $ProjectRoot 'vendor\autoload.php'))) {
    throw 'Run composer install before preparing the Windows release.'
}

if (-not (Test-Path (Join-Path $ProjectRoot 'public\build\manifest.json'))) {
    throw 'Run npm run build before preparing the Windows release.'
}

if (Test-Path $releaseRoot) {
    Remove-Item $releaseRoot -Recurse -Force
}

New-Item -ItemType Directory -Path $releaseRoot -Force | Out-Null

$copyItems = @(
    '.env.windows.local.example',
    'app',
    'artisan',
    'bootstrap',
    'composer.json',
    'composer.lock',
    'config',
    'database',
    'package.json',
    'public',
    'resources',
    'routes',
    'scripts',
    'storage',
    'vendor'
)

$optionalCopyItems = @(
    'runtime'
)

foreach ($item in $copyItems) {
    $source = Join-Path $ProjectRoot $item
    if (-not (Test-Path $source)) {
        continue
    }

    $destination = Join-Path $releaseRoot $item
    Copy-Item -Path $source -Destination $destination -Recurse -Force
}

foreach ($item in $optionalCopyItems) {
    $source = Join-Path $ProjectRoot $item
    if (-not (Test-Path $source)) {
        continue
    }

    $destination = Join-Path $releaseRoot $item
    Copy-Item -Path $source -Destination $destination -Recurse -Force
}

if (-not [string]::IsNullOrWhiteSpace($PhpRuntimeRoot)) {
    if (-not [System.IO.Path]::IsPathRooted($PhpRuntimeRoot)) {
        $PhpRuntimeRoot = Join-Path $ProjectRoot $PhpRuntimeRoot
    }

    $PhpRuntimeRoot = (Resolve-Path $PhpRuntimeRoot).Path
    $runtimePhpExecutable = Join-Path $PhpRuntimeRoot 'php.exe'

    if (-not (Test-Path $runtimePhpExecutable)) {
        throw "The supplied PHP runtime does not contain php.exe: $PhpRuntimeRoot"
    }

    $releaseRuntimeRoot = Join-Path $releaseRoot 'runtime\php'
    if (Test-Path $releaseRuntimeRoot) {
        Remove-Item $releaseRuntimeRoot -Recurse -Force
    }

    New-Item -ItemType Directory -Path $releaseRuntimeRoot -Force | Out-Null
    Copy-Item -Path (Join-Path $PhpRuntimeRoot '*') -Destination $releaseRuntimeRoot -Recurse -Force

    $releasePhpIni = Join-Path $releaseRuntimeRoot 'php.ini'
    if (-not (Test-Path $releasePhpIni)) {
        $productionIni = Join-Path $releaseRuntimeRoot 'php.ini-production'
        if (-not (Test-Path $productionIni)) {
            throw 'The supplied PHP runtime contains no php.ini or php.ini-production.'
        }

        Copy-Item $productionIni $releasePhpIni -Force
    }

    $portableIniLines = Get-Content $releasePhpIni
    $portableIniLines = foreach ($line in $portableIniLines) {
        if ($line -match '^\s*extension_dir\s*=') {
            'extension_dir = "${BALAFON_PHP_EXT}"'
            continue
        }

        if ($line -match '^\s*(error_log|upload_tmp_dir|session\.save_path|soap\.wsdl_cache_dir|zend_extension|xdebug\.[^=]+)\s*=') {
            "; disabled for portable Balafon runtime: $line"
            continue
        }

        $line
    }
    Set-Content -Path $releasePhpIni -Value $portableIniLines -Encoding UTF8
}

$releaseDatabaseRoot = Join-Path $releaseRoot 'database'
if (Test-Path $releaseDatabaseRoot) {
    Get-ChildItem -Path $releaseDatabaseRoot -Filter '*.sqlite' -File -ErrorAction SilentlyContinue |
        Remove-Item -Force
}

$releaseStorageRoot = Join-Path $releaseRoot 'storage'
if (Test-Path $releaseStorageRoot) {
    Get-ChildItem -Path $releaseStorageRoot -Recurse -File -Force |
        Where-Object { $_.Name -ne '.gitignore' } |
        Remove-Item -Force

    $releaseRuntimeState = Join-Path $releaseStorageRoot 'app\windows-runtime'
    if (Test-Path $releaseRuntimeState) {
        Remove-Item $releaseRuntimeState -Recurse -Force
    }
}

$publicStorage = Join-Path $releaseRoot 'public\storage'
if (Test-Path $publicStorage) {
    Remove-Item $publicStorage -Recurse -Force
}

$hotFile = Join-Path $releaseRoot 'public\hot'
if (Test-Path $hotFile) {
    Remove-Item $hotFile -Force
}

$forbiddenFiles = @(
    (Join-Path $releaseRoot '.env'),
    (Join-Path $releaseRoot 'database\balafon.sqlite'),
    (Join-Path $releaseRoot 'database\database.sqlite'),
    (Join-Path $releaseRoot 'public\hot')
)

foreach ($forbiddenFile in $forbiddenFiles) {
    if (Test-Path $forbiddenFile) {
        throw "Unsafe release artifact detected: $forbiddenFile"
    }
}

$bundledPhp = Join-Path $releaseRoot 'runtime\php\php.exe'
if (Test-Path $bundledPhp) {
    $previousPhpExt = $env:BALAFON_PHP_EXT
    try {
        $env:BALAFON_PHP_EXT = Join-Path (Split-Path -Parent $bundledPhp) 'ext'
        $bundledModules = & $bundledPhp -m
        if ($LASTEXITCODE -ne 0) {
            throw 'The bundled PHP runtime failed to start.'
        }

        foreach ($requiredExtension in @('openssl', 'mbstring', 'fileinfo', 'pdo_sqlite', 'sqlite3')) {
            if ($bundledModules -notcontains $requiredExtension) {
                throw "The bundled PHP runtime is missing extension '$requiredExtension'."
            }
        }
    }
    finally {
        $env:BALAFON_PHP_EXT = $previousPhpExt
    }
}

$readme = @"
Balafon Windows tester package

1. Run scripts\install_windows_tester.cmd
2. Run scripts\start_windows_local.cmd
3. Open http://127.0.0.1:8080 if the browser does not open automatically

If runtime\php\php.exe is included, no separate PHP installation is required.
vMix must have its Web Controller enabled on port 8088.
"@

Set-Content -Path (Join-Path $releaseRoot 'START_HERE.txt') -Value $readme -Encoding ASCII

Write-Host "Windows release prepared at $releaseRoot"
