[CmdletBinding()]
param(
    [string]$ProjectRoot = '',
    [int]$Port = 8080,
    [bool]$OpenBrowser = $true
)

$ErrorActionPreference = 'Stop'

. (Join-Path (Split-Path -Parent $MyInvocation.MyCommand.Path) 'windows_runtime_helpers.ps1')

if (-not $PSScriptRoot) {
    $PSScriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
}

$ProjectRoot = Resolve-ProjectRoot -ProjectRoot $ProjectRoot -InvocationPath $MyInvocation.MyCommand.Path -ScriptRoot $PSScriptRoot
$phpCommand = Get-BalafonPhpCommand -ProjectRoot $ProjectRoot

function Test-LocalTcpPort {
    param([int]$TestPort)

    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $asyncResult = $client.BeginConnect('127.0.0.1', $TestPort, $null, $null)
        $connected = $asyncResult.AsyncWaitHandle.WaitOne(1000, $false)
        if (-not $connected) {
            return $false
        }

        $client.EndConnect($asyncResult) | Out-Null
        return $true
    }
    catch {
        return $false
    }
    finally {
        $client.Dispose()
    }
}

$listener = $null
try {
    $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, $Port)
    $listener.Start()
}
catch {
    throw "Port $Port is already in use. Choose another port for Balafon."
}
finally {
    if ($listener) {
        $listener.Stop()
    }
}

$envFile = Join-Path $ProjectRoot '.env'

if (-not (Test-Path $envFile)) {
    throw "Missing .env. Run scripts\install_windows_local.ps1 first."
}

$hotFile = Join-Path $ProjectRoot 'public\hot'
if (Test-Path $hotFile) {
    Remove-Item $hotFile -Force
}

$runtimeDir = Join-Path $ProjectRoot 'storage\app\windows-runtime'
if (-not (Test-Path $runtimeDir)) {
    New-Item -ItemType Directory -Path $runtimeDir -Force | Out-Null
}

$serverPidFile = Join-Path $runtimeDir 'server.pid'
$schedulerPidFile = Join-Path $runtimeDir 'scheduler.pid'
$serverOutLog = Join-Path $runtimeDir 'server.stdout.log'
$serverErrLog = Join-Path $runtimeDir 'server.stderr.log'
$schedulerOutLog = Join-Path $runtimeDir 'scheduler.stdout.log'
$schedulerErrLog = Join-Path $runtimeDir 'scheduler.stderr.log'

foreach ($pidFile in @($serverPidFile, $schedulerPidFile)) {
    if (Test-Path $pidFile) {
        $existingPid = (Get-Content $pidFile -Raw).Trim()
        if ($existingPid) {
            $existingProcess = Get-Process -Id $existingPid -ErrorAction SilentlyContinue
            if ($existingProcess) {
                Stop-BalafonProcessTree -ProcessId ([int] $existingPid)
            }
        }

        Remove-Item $pidFile -Force
    }
}

Push-Location $ProjectRoot
try {
    Write-Host 'Applying migrations...'
    Invoke-BalafonPhp -PhpCommand $phpCommand -Arguments @('artisan', 'migrate', '--force') -ErrorMessage 'php artisan migrate failed.'

    Write-Host 'Clearing stale scheduler locks...'
    Invoke-BalafonPhp -PhpCommand $phpCommand -Arguments @('artisan', 'schedule:clear-cache') -ErrorMessage 'php artisan schedule:clear-cache failed.'

    foreach ($logFile in @($serverOutLog, $serverErrLog, $schedulerOutLog, $schedulerErrLog)) {
        if (Test-Path $logFile) {
            Remove-Item $logFile -Force
        }
    }

    $localPhpIniDir = Join-Path $ProjectRoot 'config\php'
    $previousPhpIniScanDir = [Environment]::GetEnvironmentVariable('PHP_INI_SCAN_DIR', 'Process')
    $previousOverlayBaseUrl = [Environment]::GetEnvironmentVariable('BALAFON_OVERLAY_BASE_URL', 'Process')

    try {
        $env:PHP_INI_SCAN_DIR = $localPhpIniDir
        $env:BALAFON_OVERLAY_BASE_URL = "http://127.0.0.1:$Port"

        $scheduler = Start-Process -FilePath $phpCommand `
            -ArgumentList @(
                '-d', 'xdebug.mode=off',
                '-d', 'xdebug.start_with_request=no',
                'artisan', 'schedule:work'
            ) `
            -WorkingDirectory $ProjectRoot `
            -WindowStyle Hidden `
            -RedirectStandardOutput $schedulerOutLog `
            -RedirectStandardError $schedulerErrLog `
            -PassThru

        $publicRoot = Join-Path $ProjectRoot 'public'
        $serverRouter = '..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'

        $server = Start-Process -FilePath $phpCommand `
            -ArgumentList @(
                '-d', 'xdebug.mode=off',
                '-d', 'xdebug.start_with_request=no',
                '-S', "127.0.0.1:$Port",
                '-t', '.',
                $serverRouter
            ) `
            -WorkingDirectory $publicRoot `
            -WindowStyle Hidden `
            -RedirectStandardOutput $serverOutLog `
            -RedirectStandardError $serverErrLog `
            -PassThru
    }
    finally {
        if ([string]::IsNullOrEmpty($previousPhpIniScanDir)) {
            Remove-Item Env:PHP_INI_SCAN_DIR -ErrorAction SilentlyContinue
        }
        else {
            $env:PHP_INI_SCAN_DIR = $previousPhpIniScanDir
        }

        if ([string]::IsNullOrEmpty($previousOverlayBaseUrl)) {
            Remove-Item Env:BALAFON_OVERLAY_BASE_URL -ErrorAction SilentlyContinue
        }
        else {
            $env:BALAFON_OVERLAY_BASE_URL = $previousOverlayBaseUrl
        }
    }

    Set-Content -Path $schedulerPidFile -Value $scheduler.Id -Encoding ASCII
    Set-Content -Path $serverPidFile -Value $server.Id -Encoding ASCII

    Start-Sleep -Seconds 2

    $portReady = $false
    foreach ($attempt in 1..8) {
        if ($server.HasExited) {
            throw "Balafon HTTP server failed to stay alive on port $Port."
        }

        if (Test-LocalTcpPort -TestPort $Port) {
            $portReady = $true
            break
        }

        Start-Sleep -Seconds 1
    }

    if (-not $portReady) {
        throw "Balafon HTTP server did not open port $Port. Check $serverOutLog and $serverErrLog."
    }

    if ($scheduler.HasExited) {
        throw "Balafon scheduler failed to stay alive. Check $schedulerOutLog and $schedulerErrLog."
    }

    if ($OpenBrowser) {
        Start-Process "http://127.0.0.1:$Port"
    }

    Write-Host "Balafon is running on http://127.0.0.1:$Port"
    Write-Host "PHP runtime: $phpCommand"
    Write-Host "Server PID: $($server.Id)"
    Write-Host "Scheduler PID: $($scheduler.Id)"
    Write-Host 'Run scripts\stop_windows_local.ps1 to stop the local services.'
}
finally {
    Pop-Location
}
