@echo off
setlocal
cd /d "%~dp0"
echo Starting Balafon local on http://127.0.0.1:8081
powershell -ExecutionPolicy Bypass -File ".\scripts\start_windows_local.ps1" -Port 8081
if errorlevel 1 (
    echo.
    echo Balafon failed to start.
    echo Check the message above, then press any key to close this window.
    pause >nul
)
endlocal
