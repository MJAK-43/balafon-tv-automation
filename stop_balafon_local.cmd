@echo off
setlocal
cd /d "%~dp0"
powershell -ExecutionPolicy Bypass -File ".\scripts\stop_windows_local.ps1"
if errorlevel 1 (
    echo.
    echo Balafon failed to stop cleanly.
    echo Press any key to close this window.
    pause >nul
)
endlocal
