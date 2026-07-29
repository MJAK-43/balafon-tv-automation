@echo off
setlocal
powershell -ExecutionPolicy Bypass -File "%~dp0prepare_windows_release.ps1" %*
endlocal
