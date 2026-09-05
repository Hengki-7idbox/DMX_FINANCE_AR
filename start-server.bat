@echo off
title DMX Finance AR - Server
echo ============================================
echo   DMX Finance AR Tools - Starting Server...
echo   Port: 55400
echo ============================================

REM Cek apakah server sudah jalan
netstat -ano | findstr :55400 | findstr LISTENING >nul 2>&1
if %errorlevel%==0 (
    echo [!] Server sudah berjalan di port 55400
    echo     Buka browser: http://127.0.0.1:55400
    pause
    exit /b 0
)

echo [*] Menjalankan server...
cd /d "%~dp0"
start /b node build\preview\server.js > .freebuff\server.log 2>&1

REM Tunggu 3 detik lalu cek
timeout /t 3 /nobreak >nul
netstat -ano | findstr :55400 | findstr LISTENING >nul 2>&1
if %errorlevel%==0 (
    echo [OK] Server berhasil dijalankan!
    echo     URL: http://127.0.0.1:55400
    echo     Login: admin / admin
) else (
    echo [FAIL] Server gagal dijalankan. Cek log: .freebuff\server.log
)
echo.
pause
