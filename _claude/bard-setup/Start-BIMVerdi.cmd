@echo off
setlocal
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Setup-BIMVerdi.ps1"
if errorlevel 1 (
  echo.
  echo Oppsettet stoppet. Behold meldingen over og vis den til Claude.
  pause
  exit /b 1
)
echo.
pause
