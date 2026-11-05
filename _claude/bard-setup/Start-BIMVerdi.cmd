@echo off
setlocal
rem Explorer runs a .cmd straight out of a ZIP by copying only that file to Temp.
if not exist "%~dp0Setup-BIMVerdi.ps1" (
  echo Pakken er ikke pakket ut.
  echo Lukk dette vinduet.
  echo Velg ZIP-filen i Utforsker og trykk Pakk ut alle.
  echo Start Start-BIMVerdi.cmd fra den nye mappen.
  pause
  exit /b 1
)
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Setup-BIMVerdi.ps1"
if errorlevel 1 (
  echo.
  echo Oppsettet stoppet. Behold meldingen over og vis den til Claude.
  pause
  exit /b 1
)
echo.
pause
