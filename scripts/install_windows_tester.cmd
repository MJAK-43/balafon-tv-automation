@echo off
setlocal
powershell -ExecutionPolicy Bypass -File "%~dp0install_windows_tester.ps1" %*
endlocal
