@echo off
title DMX Finance AR - Restart Server
echo ============================================
echo   DMX Finance AR Tools - Restarting...
echo ============================================

REM Cari PID yang pakai port 55400
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :55400 ^| findstr LISTENING') do set PID=%%a

if defined PID (
    echo [*] Menghentikan server lama (PID: %PID%)...
    taskkill /F /PID %PID% >nul 2>&1
    timeout /t 2 /nobreak >nul
    echo [OK] Server lama dihentikan.
) else (
    echo [*] Tidak ada server lama yang berjalan.
)

echo [*] Menjalankan server baru...
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
