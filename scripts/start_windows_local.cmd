@echo off
setlocal
powershell -ExecutionPolicy Bypass -File "%~dp0start_windows_local.ps1" %*
endlocal
