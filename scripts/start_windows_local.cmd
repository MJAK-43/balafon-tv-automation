@echo off
setlocal
powershell -ExecutionPolicy Bypass -File "%~dp0start_windows_local.ps1" %*
set "BALAFON_EXIT_CODE=%errorlevel%"
if not "%BALAFON_EXIT_CODE%"=="0" pause
endlocal & exit /b %BALAFON_EXIT_CODE%
