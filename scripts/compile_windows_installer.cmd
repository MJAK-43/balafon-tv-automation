@echo off
setlocal
powershell -ExecutionPolicy Bypass -File "%~dp0compile_windows_installer.ps1" %*
endlocal
