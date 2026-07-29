function Resolve-ProjectRoot {
    param(
        [string]$ProjectRoot,
        [string]$InvocationPath,
        [string]$ScriptRoot
    )

    if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
        $resolvedScriptRoot = if ($ScriptRoot) { $ScriptRoot } else { Split-Path -Parent $InvocationPath }
        return (Resolve-Path (Join-Path $resolvedScriptRoot '..')).Path
    }

    if (-not [System.IO.Path]::IsPathRooted($ProjectRoot)) {
        return (Resolve-Path $ProjectRoot).Path
    }

    return $ProjectRoot
}

function Get-BalafonPhpCommand {
    param([string]$ProjectRoot)

    $bundledPhp = Join-Path $ProjectRoot 'runtime\php\php.exe'
    if (Test-Path $bundledPhp) {
        $resolvedBundledPhp = (Resolve-Path $bundledPhp).Path
        $env:BALAFON_PHP_EXT = Join-Path (Split-Path -Parent $resolvedBundledPhp) 'ext'

        return $resolvedBundledPhp
    }

    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCommand) {
        return $phpCommand.Source
    }

    throw 'PHP runtime not found. Bundle runtime\php\php.exe or install PHP 8.2+ on PATH.'
}

function Assert-BalafonPhpExtension {
    param(
        [string]$PhpCommand,
        [string]$Name
    )

    $previousXdebugMode = $env:XDEBUG_MODE
    $env:XDEBUG_MODE = 'off'

    try {
        $extensions = & $PhpCommand -m
        if ($LASTEXITCODE -ne 0 -or ($extensions -notcontains $Name)) {
            throw "PHP extension '$Name' is required."
        }
    }
    finally {
        $env:XDEBUG_MODE = $previousXdebugMode
    }
}

function Invoke-BalafonPhp {
    param(
        [string]$PhpCommand,
        [string[]]$Arguments,
        [string]$ErrorMessage
    )

    $previousXdebugMode = $env:XDEBUG_MODE
    $env:XDEBUG_MODE = 'off'

    try {
        & $PhpCommand @Arguments
        if ($LASTEXITCODE -ne 0) {
            throw $ErrorMessage
        }
    }
    finally {
        $env:XDEBUG_MODE = $previousXdebugMode
    }
}

function Stop-BalafonProcessTree {
    param([int]$ProcessId)

    $children = Get-CimInstance Win32_Process -Filter "ParentProcessId = $ProcessId" -ErrorAction SilentlyContinue
    foreach ($child in $children) {
        Stop-BalafonProcessTree -ProcessId ([int] $child.ProcessId)
    }

    $process = Get-Process -Id $ProcessId -ErrorAction SilentlyContinue
    if ($process) {
        Stop-Process -Id $ProcessId -Force
    }
}
