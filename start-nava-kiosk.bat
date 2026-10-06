@echo off
setlocal EnableExtensions

rem ================================================================
rem NAVA - Launcher kiosk satu klik untuk Windows (demo / pilot LAN)
rem Ubah bagian KONFIGURASI sesuai susunan monitor perangkat.
rem ================================================================

cd /d "%~dp0"

rem -------------------------- KONFIGURASI --------------------------
set "APP_PORT=8000"
set "REVERB_PORT=8081"

rem Monitor utama / layar kiosk cetak antrean.
set "KIOSK_X=0"
set "KIOSK_Y=0"
set "KIOSK_WIDTH=1920"
set "KIOSK_HEIGHT=1080"

rem Display TV. Nilai 1920,0 berarti TV berada di kanan monitor utama.
set "DISPLAY_X=1920"
set "DISPLAY_Y=0"
set "DISPLAY_WIDTH=1920"
set "DISPLAY_HEIGHT=1080"
rem -----------------------------------------------------------------

set "LOCAL_URL=http://127.0.0.1:%APP_PORT%"
set "KIOSK_PROFILE=%CD%\storage\app\browser-profiles\ticket-kiosk"
set "DISPLAY_PROFILE=%CD%\storage\app\browser-profiles\waiting-room-display"

if not exist "artisan" (
    echo [GAGAL] File artisan tidak ditemukan.
    echo Jalankan launcher ini dari folder utama proyek NAVA.
    pause
    exit /b 1
)

if not exist "vendor\autoload.php" (
    echo [GAGAL] Dependency PHP belum terpasang. Jalankan composer install.
    pause
    exit /b 1
)

if not exist "public\build\manifest.json" (
    echo [GAGAL] Build frontend belum tersedia. Jalankan npm ci lalu npm run build.
    pause
    exit /b 1
)

set "CHROME_EXE="
if exist "%ProgramFiles%\Google\Chrome\Application\chrome.exe" set "CHROME_EXE=%ProgramFiles%\Google\Chrome\Application\chrome.exe"
if exist "%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe" set "CHROME_EXE=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"
if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" set "CHROME_EXE=%LocalAppData%\Google\Chrome\Application\chrome.exe"

if not defined CHROME_EXE (
    echo [GAGAL] Google Chrome tidak ditemukan.
    echo Instal Chrome atau sesuaikan variabel CHROME_EXE pada file ini.
    pause
    exit /b 1
)

if /I "%NAVA_VALIDATE_ONLY%"=="1" (
    echo [BERHASIL] Struktur launcher dan dependency utama valid.
    exit /b 0
)

echo [NAVA] Memeriksa proses backend...

powershell.exe -NoProfile -Command "try { $r = Invoke-WebRequest -UseBasicParsing -Uri '%LOCAL_URL%/up' -TimeoutSec 2; if ($r.StatusCode -eq 200) { exit 0 } } catch {}; exit 1" >nul 2>&1
if errorlevel 1 (
    start "NAVA Web Server" /min cmd.exe /k "cd /d ""%CD%"" && php artisan serve --host=0.0.0.0 --port=%APP_PORT%"
) else (
    echo [NAVA] Web server sudah aktif.
)

powershell.exe -NoProfile -Command "if (Get-NetTCPConnection -State Listen -LocalPort %REVERB_PORT% -ErrorAction SilentlyContinue) { exit 0 }; exit 1" >nul 2>&1
if errorlevel 1 (
    start "NAVA Reverb" /min cmd.exe /k "cd /d ""%CD%"" && php artisan reverb:start --host=0.0.0.0 --port=%REVERB_PORT%"
) else (
    echo [NAVA] Reverb sudah aktif.
)

tasklist /FI "WINDOWTITLE eq NAVA Queue Worker*" /FO CSV /NH | findstr /I /C:"cmd.exe" >nul 2>&1
if errorlevel 1 (
    start "NAVA Queue Worker" /min cmd.exe /k "cd /d ""%CD%"" && php artisan queue:work --sleep=1 --tries=3 --timeout=60"
) else (
    echo [NAVA] Queue worker sudah aktif.
)

tasklist /FI "WINDOWTITLE eq NAVA Scheduler*" /FO CSV /NH | findstr /I /C:"cmd.exe" >nul 2>&1
if errorlevel 1 (
    start "NAVA Scheduler" /min cmd.exe /k "cd /d ""%CD%"" && php artisan schedule:work"
) else (
    echo [NAVA] Scheduler sudah aktif.
)

echo [NAVA] Menunggu aplikasi siap...
powershell.exe -NoProfile -Command "$deadline = (Get-Date).AddSeconds(45); do { try { $r = Invoke-WebRequest -UseBasicParsing -Uri '%LOCAL_URL%/up' -TimeoutSec 2; if ($r.StatusCode -eq 200) { exit 0 } } catch {}; Start-Sleep -Seconds 1 } while ((Get-Date) -lt $deadline); exit 1"

if errorlevel 1 (
    echo [GAGAL] Aplikasi tidak siap dalam 45 detik.
    echo Periksa jendela NAVA Web Server dan konfigurasi database pada .env.
    pause
    exit /b 1
)

echo [NAVA] Membuka kiosk cetak antrean pada monitor utama...
start "" "%CHROME_EXE%" --user-data-dir="%KIOSK_PROFILE%" --kiosk --no-first-run --disable-session-crashed-bubble --window-position=%KIOSK_X%,%KIOSK_Y% --window-size=%KIOSK_WIDTH%,%KIOSK_HEIGHT% "%LOCAL_URL%/kiosk"

timeout /t 2 /nobreak >nul

echo [NAVA] Membuka display ruang tunggu pada TV dengan audio otomatis...
start "" "%CHROME_EXE%" --user-data-dir="%DISPLAY_PROFILE%" --kiosk --no-first-run --disable-session-crashed-bubble --autoplay-policy=no-user-gesture-required --window-position=%DISPLAY_X%,%DISPLAY_Y% --window-size=%DISPLAY_WIDTH%,%DISPLAY_HEIGHT% "%LOCAL_URL%/display?autostart=1"

echo.
echo [BERHASIL] NAVA telah dijalankan.
echo Gunakan Alt+F4 untuk menutup masing-masing layar kiosk.
echo Jangan tutup proses NAVA yang diminimalkan selama pelayanan berlangsung.
timeout /t 8 /nobreak >nul

endlocal
