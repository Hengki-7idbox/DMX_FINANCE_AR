@echo off
setlocal enabledelayedexpansion
title Finance AR Management System
color 0A

set "APP_DIR=C:\Users\user\Desktop\Haro\DMX\Finance_ar Software"
set "PHP=C:\xampp\php\php.exe"
set "PORT=8000"

echo ========================================
echo    Finance AR Management System
echo ========================================
echo.

echo [1/6] Stopping old server on port %PORT%...
for /f "tokens=5" %%p in ('netstat -ano ^| findstr LISTENING ^| findstr ":%PORT% "') do (
    taskkill /f /pid %%p >nul 2>&1
)

echo [2/6] Stopping Vite dev server (static build used for LAN)...
for /f "tokens=5" %%p in ('netstat -ano ^| findstr LISTENING ^| findstr ":5173 "') do (
    taskkill /f /pid %%p >nul 2>&1
)
if exist "%APP_DIR%\public\hot" del "%APP_DIR%\public\hot"

echo [3/6] Ensuring firewall allows port %PORT%...
netsh advfirewall firewall show rule name="FinanceAR-%PORT%" >nul 2>&1
if %errorlevel% neq 0 (
    echo       Permission needed once - please click Yes on the UAC prompt...
    powershell -NoProfile -Command "Start-Process netsh -Verb RunAs -ArgumentList 'advfirewall firewall add rule name=FinanceAR-%PORT% dir=in action=allow protocol=TCP localport=%PORT%' -Wait" >nul 2>&1
    netsh advfirewall firewall show rule name="FinanceAR-%PORT%" >nul 2>&1
    if %errorlevel% neq 0 (
        echo       [!] Failed to add firewall rule.
        echo       Other computers CANNOT open this app yet.
        echo       Fix: right-click this file - Run as administrator, once.
    ) else (
        echo       Firewall rule added.
    )
) else (
    echo       Firewall rule already exists.
)

echo [4/6] Building assets for LAN access...
pushd "%APP_DIR%"
where npm >nul 2>&1
if !errorlevel!==0 (
    call npm run build --silent >nul 2>&1
    if !errorlevel!==0 (
        echo       Build OK.
    ) else (
        echo       [!] Build failed - using existing public\build.
    )
) else (
    echo       npm not found - using existing public\build.
)
popd

echo [5/6] Starting Laravel server on 0.0.0.0:%PORT%...
start "Finance AR Server" cmd /k "cd /d %APP_DIR% && %PHP% artisan serve --host=0.0.0.0 --port=%PORT%"
timeout /t 3 /nobreak >nul

echo [6/6] Done.
echo.
echo ========================================
echo    Server addresses:
echo      Local:  http://localhost:%PORT%
powershell -NoProfile -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object {$_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254.*'} | ForEach-Object { '      Other PCs: http://' + $_.IPAddress + ':%PORT%' })"
echo.
echo    Login:
echo      Email:    admin
echo      Password: admin
echo.
echo    - Open ONE of the addresses above from another computer.
echo    - Server keeps running in the "Finance AR Server" window.
echo    - After UI edits, run "npm run build" so other PCs see changes.
echo ========================================
echo.
timeout /t 15
endlocal
