@echo off
title Finance AR Management System
color 0A

echo ========================================
echo    Finance AR Management System
echo ========================================
echo.

echo [1/3] Starting Laravel Server...
start "Laravel Server" cmd /k "cd /d C:\Users\user\Desktop\Haro\DMX\Finance_ar Software && C:\xampp\php\php.exe artisan serve"

timeout /t 2 /nobreak >nul

echo [2/3] Starting Vite Dev Server...
start "Vite Dev Server" cmd /k "cd /d C:\Users\user\Desktop\Haro\DMX\Finance_ar Software && npm run dev"

timeout /t 3 /nobreak >nul

echo [3/3] Opening Browser...
start http://localhost:8000

echo.
echo ========================================
echo    Application is starting...
echo ========================================
echo.
echo    URL:      http://localhost:8000
echo    Email:    admin
echo    Password: admin
echo.
echo ========================================
echo.
echo Press any key to exit...
pause >nul
