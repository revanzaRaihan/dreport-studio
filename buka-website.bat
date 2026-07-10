@echo off
title Launch Report Studio
echo ==========================================
echo        REPORT STUDIO AUTO LAUNCHER
echo ==========================================
echo.
echo Menjalankan server lokal (jangan tutup jendela ini)...
echo Membuka browser ke http://127.0.0.1:8000...
echo.

:: Start a background task to wait 2 seconds (giving PHP server time to start) then open browser
start /b cmd /c "timeout /t 2 >nul && start http://127.0.0.1:8000"

:: Start the Laravel local development server
php artisan serve
