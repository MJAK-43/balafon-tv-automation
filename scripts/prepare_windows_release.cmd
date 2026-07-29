@echo off
setlocal
powershell -ExecutionPolicy Bypass -File "%~dp0prepare_windows_release.ps1" %*
set "BALAFON_EXIT_CODE=%errorlevel%"
if not "%BALAFON_EXIT_CODE%"=="0" pause
endlocal & exit /b %BALAFON_EXIT_CODE%
