[CmdletBinding()]
param(
    [string]$ProjectRoot = '',
    [string]$InnoScript = ''
)

$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
    $scriptRoot = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
    $ProjectRoot = (Resolve-Path (Join-Path $scriptRoot '..')).Path
}
elseif (-not [System.IO.Path]::IsPathRooted($ProjectRoot)) {
    $ProjectRoot = (Resolve-Path $ProjectRoot).Path
}

if ([string]::IsNullOrWhiteSpace($InnoScript)) {
    $InnoScript = Join-Path $ProjectRoot 'packaging\windows\BalafonSetup.iss'
}
elseif (-not [System.IO.Path]::IsPathRooted($InnoScript)) {
    $InnoScript = Join-Path $ProjectRoot $InnoScript
}

$compilerCandidates = @(
    (Get-Command ISCC -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source -ErrorAction SilentlyContinue),
    'C:\Program Files (x86)\Inno Setup 6\ISCC.exe',
    'C:\Program Files\Inno Setup 6\ISCC.exe'
) | Where-Object { $_ }

$compilerPath = $compilerCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $compilerPath) {
    throw 'Inno Setup compiler not found. Install Inno Setup 6, then rerun this script.'
}

if (-not (Test-Path $InnoScript)) {
    throw "Missing Inno Setup script: $InnoScript"
}

Push-Location $ProjectRoot
try {
    & $compilerPath $InnoScript
    if ($LASTEXITCODE -ne 0) {
        throw 'Inno Setup compilation failed.'
    }
}
finally {
    Pop-Location
}

Write-Host 'Windows installer compiled successfully.'
