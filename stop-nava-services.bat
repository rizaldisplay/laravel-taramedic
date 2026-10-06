@echo off
setlocal

echo Menghentikan proses layanan NAVA yang dijalankan oleh launcher...
taskkill /FI "WINDOWTITLE eq NAVA Web Server*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq NAVA Reverb*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq NAVA Queue Worker*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq NAVA Scheduler*" /T /F >nul 2>&1

echo Layanan NAVA telah dihentikan.
echo Tutup layar Chrome kiosk secara terpisah menggunakan Alt+F4.
timeout /t 5 /nobreak >nul

endlocal
