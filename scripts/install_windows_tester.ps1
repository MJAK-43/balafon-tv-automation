[CmdletBinding()]
param(
    [string]$ProjectRoot = '',
    [string]$MediaRoot = 'C:\ProgramData\Balafon\Media',
    [string]$AppUrl = 'http://127.0.0.1:8080',
    [switch]$ForceEnv
)

$ErrorActionPreference = 'Stop'

. (Join-Path (Split-Path -Parent $MyInvocation.MyCommand.Path) 'windows_runtime_helpers.ps1')

if (-not $PSScriptRoot) {
    $PSScriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
}

$ProjectRoot = Resolve-ProjectRoot -ProjectRoot $ProjectRoot -InvocationPath $MyInvocation.MyCommand.Path -ScriptRoot $PSScriptRoot
$phpCommand = Get-BalafonPhpCommand -ProjectRoot $ProjectRoot

Write-Host 'Checking packaged Balafon prerequisites...'
Assert-BalafonPhpExtension -PhpCommand $phpCommand -Name 'openssl'
Assert-BalafonPhpExtension -PhpCommand $phpCommand -Name 'mbstring'
Assert-BalafonPhpExtension -PhpCommand $phpCommand -Name 'fileinfo'
Assert-BalafonPhpExtension -PhpCommand $phpCommand -Name 'pdo_sqlite'
Assert-BalafonPhpExtension -PhpCommand $phpCommand -Name 'sqlite3'

$envFile = Join-Path $ProjectRoot '.env'
$templateFile = Join-Path $ProjectRoot '.env.windows.local.example'
$sqlitePath = Join-Path $ProjectRoot 'database\balafon.sqlite'

if (-not (Test-Path $templateFile)) {
    throw "Missing template file: $templateFile"
}

if (-not (Test-Path (Join-Path $ProjectRoot 'vendor\autoload.php'))) {
    throw 'Missing vendor dependencies. Prepare the release package from a machine that already ran composer install.'
}

if (-not (Test-Path (Join-Path $ProjectRoot 'public\build\manifest.json'))) {
    throw 'Missing compiled frontend assets. Prepare the release package from a machine that already ran npm run build.'
}

if (-not (Test-Path (Split-Path $sqlitePath -Parent))) {
    New-Item -ItemType Directory -Path (Split-Path $sqlitePath -Parent) -Force | Out-Null
}

if (-not (Test-Path $sqlitePath)) {
    New-Item -ItemType File -Path $sqlitePath | Out-Null
}

if (-not (Test-Path $MediaRoot)) {
    New-Item -ItemType Directory -Path $MediaRoot -Force | Out-Null
}

if ($ForceEnv -or -not (Test-Path $envFile)) {
    $envContent = Get-Content $templateFile -Raw
    $envContent += "`r`nDB_DATABASE='$sqlitePath'"
    $envContent += "`r`nBALAFON_MEDIA_BROWSER_ROOTS='$MediaRoot'"
    $envContent += "`r`nBALAFON_MEDIA_HOST_BROWSER_ROOTS='$MediaRoot'"
    $envContent += "`r`nAPP_URL='$AppUrl'"
    Set-Content -Path $envFile -Value $envContent -Encoding UTF8
}

$hotFile = Join-Path $ProjectRoot 'public\hot'
if (Test-Path $hotFile) {
    Remove-Item $hotFile -Force
}

Push-Location $ProjectRoot
try {
    Write-Host 'Generating application key...'
    Invoke-BalafonPhp -PhpCommand $phpCommand -Arguments @('artisan', 'key:generate', '--force') -ErrorMessage 'php artisan key:generate failed.'

    Write-Host 'Running database migrations...'
    Invoke-BalafonPhp -PhpCommand $phpCommand -Arguments @('artisan', 'migrate', '--force') -ErrorMessage 'php artisan migrate failed.'

    Write-Host 'Seeding baseline data...'
    Invoke-BalafonPhp -PhpCommand $phpCommand -Arguments @('artisan', 'db:seed', '--force') -ErrorMessage 'php artisan db:seed failed.'

    Write-Host 'Linking public storage...'
    Invoke-BalafonPhp -PhpCommand $phpCommand -Arguments @('artisan', 'storage:link', '--force') -ErrorMessage 'php artisan storage:link failed.'

    Write-Host 'Clearing stale caches...'
    Invoke-BalafonPhp -PhpCommand $phpCommand -Arguments @('artisan', 'optimize:clear') -ErrorMessage 'php artisan optimize:clear failed.'
}
finally {
    Pop-Location
}

Write-Host ''
Write-Host 'Balafon tester setup is complete.'
Write-Host "PHP runtime: $phpCommand"
Write-Host "Media root: $MediaRoot"
Write-Host "SQLite database: $sqlitePath"
Write-Host 'Run scripts\start_windows_local.ps1 to launch the application.'
