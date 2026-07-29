@echo off
setlocal
powershell -ExecutionPolicy Bypass -File "%~dp0stop_windows_local.ps1" %*
endlocal
