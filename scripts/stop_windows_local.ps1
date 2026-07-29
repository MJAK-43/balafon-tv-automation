[CmdletBinding()]
param(
    [string]$ProjectRoot = ''
)

$ErrorActionPreference = 'Stop'

. (Join-Path (Split-Path -Parent $MyInvocation.MyCommand.Path) 'windows_runtime_helpers.ps1')

if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
    $scriptRoot = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
    $ProjectRoot = (Resolve-Path (Join-Path $scriptRoot '..')).Path
}
elseif (-not [System.IO.Path]::IsPathRooted($ProjectRoot)) {
    $ProjectRoot = (Resolve-Path $ProjectRoot).Path
}

$runtimeDir = Join-Path $ProjectRoot 'storage\app\windows-runtime'
$serverPidFile = Join-Path $runtimeDir 'server.pid'
$schedulerPidFile = Join-Path $runtimeDir 'scheduler.pid'
$pidFiles = @($serverPidFile, $schedulerPidFile)

foreach ($pidFile in $pidFiles) {
    if (-not (Test-Path $pidFile)) {
        continue
    }

    $processId = (Get-Content $pidFile -Raw).Trim()
    if ($processId) {
        $process = Get-Process -Id $processId -ErrorAction SilentlyContinue
        if ($process) {
            Stop-BalafonProcessTree -ProcessId ([int] $processId)
            Write-Host "Stopped process $processId"
        }
    }

    if (Test-Path $pidFile) {
        Remove-Item $pidFile -Force
    }
}

Write-Host 'Balafon local services are stopped.'
