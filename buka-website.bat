@echo off
title Launch Report Studio
echo =====================================================================
echo                     REPORT STUDIO AUTO LAUNCHER
echo =====================================================================
echo.
echo Menjalankan server lokal pada port 80...
echo Membuka browser ke http://reportstudio.test...
echo.
echo PENTING: Pastikan file BAT ini dijalankan dengan cara:
echo          [Klik Kanan] -> [Run as Administrator]
echo.
echo (Jendela ini jangan ditutup selama Anda menggunakan website)
echo =====================================================================
echo.

:: Start a background task to wait 2 seconds then open browser
start /b cmd /c "timeout /t 2 >nul && start http://reportstudio.test"

:: Start the Laravel local development server on port 80
php artisan serve --port=80
