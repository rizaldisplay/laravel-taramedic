# NAVA — Panduan Instalasi dan Implementasi Kiosk Antrian Klinik

NAVA (**Na-va — Navigasi Antrean Virtual Medis**) adalah sistem antrian klinik berbasis Laravel, Livewire, Reverb, MySQL, dan browser kiosk. Dokumen ini ditujukan untuk teknisi IT yang memasang NAVA pada komputer lokal, jaringan klinik, kiosk cetak antrean, display TV ruang tunggu, dan komputer operator.

## 1. Konsep solusi

Dalam konfigurasi satu jaringan, komputer kiosk berfungsi sebagai server aplikasi sekaligus layar pengambilan nomor. Display TV menjadi monitor kedua dari komputer tersebut. Komputer operator mengakses aplikasi melalui LAN.

```text
Internet (opsional; TTS offline tidak membutuhkannya)
                         |
                Router / Switch LAN
                         |
        +----------------+----------------+
        |                                 |
Komputer kiosk/server                Komputer operator
- Laravel + MySQL                    +- Browser Chrome/Edge
- Reverb WebSocket                   +- Login operator
- Queue worker                       +- Panggil/layani antrean
- Scheduler                          |
- Monitor utama: /kiosk              |
- Monitor kedua/TV: /display         |
        |
Printer thermal USB/Bluetooth
```

Alur operasional:

1. Teknisi menyalakan komputer kiosk/server.
2. Launcher `start-nava-kiosk.bat` menjalankan seluruh layanan NAVA.
3. Chrome membuka `/kiosk` fullscreen pada monitor utama.
4. Chrome membuka `/display?autostart=1` fullscreen pada TV ruang tunggu.
5. Audio display aktif otomatis melalui profil Chrome khusus kiosk.
6. Pasien mengambil nomor dan struk dicetak.
7. Operator membuka halaman operator dari komputer lain pada jaringan yang sama.
8. Panggilan dikirim secara real-time melalui Reverb dan dibacakan oleh TTS.

## 2. Mode implementasi

### Mode percobaan atau pilot

Gunakan `php artisan serve` melalui file BAT. Mode ini cepat dipasang dan sesuai untuk demo, uji perangkat, pelatihan, atau pilot pada jaringan internal.

### Mode operasional permanen

Untuk penggunaan klinik setiap hari, jalankan web server, Reverb, queue worker, dan scheduler sebagai Windows Service atau melalui process manager. `php artisan serve` bukan web server production yang hardened. Launcher BAT tetap dapat dipakai untuk membuka dua layar Chrome, tetapi proses backend sebaiknya otomatis berjalan sebagai service.

## 3. Kebutuhan perangkat

### Komputer kiosk/server

Rekomendasi minimum:

- Windows 10/11 64-bit.
- CPU Intel Core i3 generasi baru atau setara; Core i5 lebih disarankan.
- RAM 8 GB; minimum 4 GB hanya untuk penggunaan ringan.
- SSD dengan ruang kosong minimal 10 GB.
- Dua output display jika TV dijadikan monitor kedua.
- LAN kabel untuk koneksi paling stabil.
- Printer thermal 58 mm atau 80 mm sesuai dukungan perangkat.

### Perangkat lunak

- PHP 8.3 dengan extension Laravel dan `pdo_mysql`.
- Composer 2.
- Node.js dan npm yang kompatibel dengan Vite 8.
- MySQL atau MariaDB.
- Google Chrome versi terbaru.
- Git opsional jika source code tidak dikirim sebagai arsip.

### Komputer operator

- Terhubung ke router/switch yang sama.
- Chrome atau Edge versi terbaru.
- Tidak perlu memasang PHP, Composer, Node.js, atau source code.

## 4. Instalasi lokal untuk percobaan

### 4.1 Salin source code

Ekstrak atau clone proyek, misalnya ke:

```text
C:\NAVA
```

Jangan menghapus folder `public\tts`. Folder tersebut berisi model TTS Bahasa Indonesia dan runtime WebAssembly sekitar 92 MB.

### 4.2 Instal dependency

Buka PowerShell atau Command Prompt dari folder proyek:

```powershell
composer install
npm ci
```

Salin environment:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

### 4.3 Siapkan database

Buat database MySQL:

```sql
CREATE DATABASE antrian_self_service
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Sesuaikan `.env`:

```env
APP_NAME=NAVA
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=antrian_self_service
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb
```

Untuk akun demo lokal, tambahkan password yang hanya dipakai saat instalasi:

```env
QUEUE_DEMO_PASSWORD=Ganti-Dengan-Password-Kuat
```

Jalankan migration dan seeder:

```powershell
php artisan migrate --seed
```

Akun demo lokal yang dibuat:

- `admin@antrian.test`
- `operator1@antrian.test`
- `operator2@antrian.test`
- `operator3@antrian.test`

Semua memakai nilai `QUEUE_DEMO_PASSWORD`. Setelah masuk, buat akun petugas sebenarnya dari menu **Tim & Akses** dan jangan memakai akun demo untuk operasional production.

### 4.4 Konfigurasi Reverb lokal

Buat nilai acak untuk key dan secret:

```powershell
php -r "echo bin2hex(random_bytes(16)), PHP_EOL;"
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Masukkan ke `.env`:

```env
REVERB_APP_ID=nava-local
REVERB_APP_KEY=hasil_random_pertama
REVERB_APP_SECRET=hasil_random_kedua

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8081
REVERB_HOST=127.0.0.1
REVERB_PORT=8081
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 4.5 Build aplikasi

Nilai `VITE_REVERB_*` disisipkan saat build. Karena itu, jalankan build setelah konfigurasi Reverb selesai:

```powershell
npm run build
php artisan optimize
```

### 4.6 Menjalankan secara manual

Untuk memahami proses yang digunakan, buka empat terminal:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

```powershell
php artisan reverb:start --host=0.0.0.0 --port=8081
```

```powershell
php artisan queue:work --sleep=1 --tries=3 --timeout=60
```

```powershell
php artisan schedule:work
```

Buka:

- Admin: `http://127.0.0.1:8000/admin`
- Operator: `http://127.0.0.1:8000/operator`
- Kiosk: `http://127.0.0.1:8000/kiosk`
- Display TV: `http://127.0.0.1:8000/display`
- Health check: `http://127.0.0.1:8000/up`

## 5. Konfigurasi satu jaringan klinik

### 5.1 Tetapkan IP server

Gunakan DHCP reservation pada router atau IP statis untuk komputer kiosk/server. Contoh:

```text
IP server : 192.168.1.10
Gateway   : 192.168.1.1
Subnet    : 255.255.255.0
```

Jangan mengandalkan IP dinamis karena alamat operator akan berubah ketika DHCP memberikan IP baru.

### 5.2 Ubah environment untuk LAN

Contoh `.env` menggunakan IP server `192.168.1.10`:

```env
APP_NAME=NAVA
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.10:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=antrian_self_service
DB_USERNAME=nava_app
DB_PASSWORD=password_database_yang_kuat

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=nava-lan
REVERB_APP_KEY=key_acak
REVERB_APP_SECRET=secret_acak
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8081
REVERB_HOST=192.168.1.10
REVERB_PORT=8081
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

LOG_LEVEL=warning
SESSION_SECURE_COOKIE=false
```

Setelah mengubah host Reverb:

```powershell
npm run build
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
php artisan reverb:restart
```

Jika nanti aplikasi memakai HTTPS, ubah scheme menjadi `https`, port publik menjadi `443`, dan `SESSION_SECURE_COOKIE=true`.

### 5.3 Buka Windows Firewall

Jalankan PowerShell sebagai Administrator pada komputer server:

```powershell
New-NetFirewallRule -DisplayName "NAVA Laravel LAN" -Direction Inbound -Protocol TCP -LocalPort 8000 -Action Allow -Profile Private
New-NetFirewallRule -DisplayName "NAVA Reverb LAN" -Direction Inbound -Protocol TCP -LocalPort 8081 -Action Allow -Profile Private
```

Pastikan profil jaringan Windows adalah **Private**, bukan Public. Batasi firewall ke subnet klinik jika kebijakan jaringan mengharuskannya.

### 5.4 Akses dari komputer operator

Pada komputer operator buka:

```text
http://192.168.1.10:8000/operator
```

Operator harus login dengan akun masing-masing. Jangan membagikan satu akun untuk semua operator karena klaim loket dan histori tindakan terkait dengan pengguna.

## 6. Launcher kiosk satu klik

File berikut sudah disediakan di root proyek:

- `start-nava-kiosk.bat`
- `stop-nava-services.bat`

Launcher menjalankan:

1. Laravel web server pada `0.0.0.0:8000`.
2. Reverb pada `0.0.0.0:8081`.
3. Queue worker.
4. Laravel scheduler.
5. Chrome `/kiosk` pada monitor utama.
6. Chrome `/display?autostart=1` pada TV ruang tunggu.

Vite development server tidak dijalankan karena launcher memakai hasil production build di `public/build`.

### 6.1 Atur posisi monitor

Buka `start-nava-kiosk.bat` menggunakan Notepad. Konfigurasi default:

```bat
set "KIOSK_X=0"
set "KIOSK_Y=0"
set "KIOSK_WIDTH=1920"
set "KIOSK_HEIGHT=1080"

set "DISPLAY_X=1920"
set "DISPLAY_Y=0"
set "DISPLAY_WIDTH=1920"
set "DISPLAY_HEIGHT=1080"
```

Konfigurasi tersebut berarti:

- Monitor utama 1920×1080 berada di posisi `0,0`.
- TV 1920×1080 berada tepat di sebelah kanan monitor utama.

Jika TV berada di kiri, nilai X biasanya negatif, misalnya `DISPLAY_X=-1920`. Periksa susunan melalui **Windows Settings → System → Display → Identify**. Gunakan mode **Extend these displays**, bukan Duplicate.

### 6.2 Jalankan

Klik dua kali:

```text
start-nava-kiosk.bat
```

Launcher memeriksa dependency, build frontend, Chrome, dan endpoint `/up`. Jika server sudah berjalan, launcher tidak membuat salinan proses backend baru dan hanya membuka kembali layar kiosk.

Keluar dari Chrome kiosk menggunakan `Alt+F4`. Untuk menghentikan proses backend yang dibuat launcher, jalankan:

```text
stop-nava-services.bat
```

Script stop hanya menargetkan jendela proses dengan nama NAVA. Chrome ditutup terpisah agar tidak menghentikan sesi Chrome pribadi pengguna.

## 7. Fullscreen dan suara otomatis

Display dibuka dengan kombinasi:

```text
--kiosk
--autoplay-policy=no-user-gesture-required
/display?autostart=1
```

Fungsinya:

- `--kiosk` menghilangkan address bar dan membuat halaman fullscreen.
- Flag autoplay mengizinkan profil Chrome kiosk memutar audio tanpa klik.
- `autostart=1` meminta aplikasi mengaktifkan audio, menyiapkan opening/closing cue, dan memanaskan TTS offline secara otomatis.
- Model TTS mulai dimuat sebelum panggilan pertama sehingga jeda panggilan pertama berkurang.

Mode autostart hanya digunakan oleh launcher. Jika `/display` dibuka biasa tanpa parameter tersebut, tombol **Aktifkan suara TV** tetap tersedia sebagai fallback.

### Output audio Windows

Atur TV HDMI sebagai output audio default:

1. Buka **Settings → System → Sound**.
2. Pilih TV/HDMI sebagai output.
3. Atur volume Windows dan TV.
4. Pastikan tab Chrome tidak di-mute melalui Volume Mixer.
5. Jalankan **Pengaturan → Suara Panggilan → Uji suara**.

Browser tidak dapat membuat volume melebihi kemampuan fisik speaker. Gunakan speaker aktif atau amplifier jika volume TV kurang kuat.

## 8. TTS offline dan online

### TTS Offline Lokal

- Menggunakan Piper `id_ID-news_tts-medium` dan ONNX WebAssembly.
- Semua file berada di `public/tts`.
- Tidak memakai API atau CDN.
- Tetap membutuhkan LAN agar perangkat dapat mengakses server NAVA.
- Mendukung kecepatan, karakter intonasi, volume, pengulangan, dan penyebutan angka nol.

### TTS Online Browser

- Menggunakan Web Speech API dan voice yang tersedia pada browser/OS.
- Kualitas dapat berbeda antarperangkat.
- Voice neural tertentu dapat membutuhkan internet.

Untuk operasional tanpa internet, pilih **TTS Offline Lokal** pada **Admin → Pengaturan → Suara Panggilan**.

### Verifikasi TTS offline

1. Pilih mode TTS Offline Lokal.
2. Tekan **Uji suara A001 · Loket 1**.
3. Pastikan status berubah menjadi **TTS offline Bahasa Indonesia siap**.
4. Buka display melalui launcher.
5. Putuskan internet tanpa memutus LAN.
6. Panggil antrean dari operator.
7. Pastikan suara tetap terdengar.

## 9. Printer kiosk

Kiosk dibuka menggunakan profil Chrome khusus di:

```text
storage\app\browser-profiles\ticket-kiosk
```

Profil khusus mempertahankan pengaturan dan izin perangkat kiosk tanpa mencampurkannya dengan profil Chrome pribadi.

Untuk printer yang memerlukan Web Bluetooth atau Web Serial, pemasangan pertama tetap membutuhkan tindakan teknisi karena browser tidak mengizinkan website memilih perangkat fisik secara diam-diam:

1. Jalankan kiosk.
2. Tekan tombol status/koneksi printer.
3. Pilih printer yang benar pada dialog Chrome.
4. Cetak satu tiket percobaan.
5. Jangan menghapus folder profil Chrome kiosk setelah printer berhasil dipasangkan.

Untuk instalasi komersial, printer USB/Ethernet dengan driver stabil lebih disarankan dibanding koneksi Bluetooth yang mudah terganggu interferensi.

## 10. Menjalankan otomatis saat Windows menyala

Untuk pilot, buat shortcut `start-nava-kiosk.bat`, lalu letakkan pada:

```text
shell:startup
```

Untuk instalasi yang lebih stabil, gunakan **Task Scheduler**:

1. Buka Task Scheduler sebagai Administrator.
2. Pilih **Create Task**.
3. Trigger: **At startup** atau **At log on** akun kiosk.
4. Tambahkan delay 30–60 detik agar MySQL dan jaringan siap.
5. Action: jalankan `start-nava-kiosk.bat`.
6. Set **Start in** ke folder proyek, misalnya `C:\NAVA`.
7. Gunakan akun Windows khusus kiosk dengan auto-login jika kebijakan klinik mengizinkan.

Jangan menggunakan akun Windows administrator untuk penggunaan kiosk harian.

## 11. Konfigurasi service untuk operasional permanen

Backend minimal yang harus terus hidup:

- Web server PHP/Laravel.
- MySQL/MariaDB.
- `php artisan reverb:start`.
- `php artisan queue:work --sleep=1 --tries=3 --timeout=60`.
- `php artisan schedule:work` atau Windows Task Scheduler yang menjalankan `php artisan schedule:run` setiap menit.

Gunakan NSSM, WinSW, IIS/FastCGI, Apache, Nginx, atau process manager perusahaan. Service harus:

- berjalan otomatis setelah boot;
- restart otomatis jika proses mati;
- menulis log ke lokasi yang dimonitor;
- berjalan menggunakan akun Windows dengan hak minimum;
- mempunyai working directory proyek yang benar.

Setelah update source code:

```powershell
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan reverb:restart
```

## 12. Checklist konfigurasi admin

Sebelum serah terima:

- Isi nama, logo, alamat, telepon, dan tagline klinik.
- Buat akun admin dan operator sebenarnya.
- Hapus/nonaktifkan akun demo yang tidak digunakan.
- Buat layanan dan kode antreannya.
- Buat loket dan hubungkan dengan layanan.
- Atur konten display dan video.
- Pilih TTS offline atau online.
- Atur volume, kecepatan, intonasi, opening, closing, dan pengulangan.
- Tentukan apakah angka `0` harus disebutkan.
- Uji `A001`, `B010`, dan nomor tanpa angka nol.
- Atur isi dan ukuran struk.
- Hubungkan printer dan cetak tiket percobaan.

## 13. Pengujian penerimaan

Lakukan skenario berikut bersama pengguna klinik:

1. Restart komputer kiosk/server.
2. Jalankan launcher atau tunggu Task Scheduler.
3. Pastikan kiosk dan display muncul pada monitor yang benar.
4. Pastikan display fullscreen dan tidak meminta klik aktivasi suara.
5. Ambil minimal tiga nomor dari layanan berbeda.
6. Pastikan nomor unik dan struk tercetak.
7. Login dari dua komputer/operator berbeda.
8. Klaim loket masing-masing.
9. Panggil antrean dan periksa perubahan display real-time.
10. Pastikan suara opening langsung dilanjutkan penyebutan nomor.
11. Uji recall, mulai pelayanan, selesai, lewati, dan tidak hadir.
12. Cabut internet tetapi pertahankan LAN; ulangi panggilan TTS offline.
13. Restart router/switch dan pastikan sistem pulih setelah LAN kembali.
14. Periksa `/up`, log Laravel, queue gagal, dan koneksi Reverb.

## 14. Operasional harian

### Membuka layanan

1. Nyalakan router/switch, TV, printer, dan komputer server.
2. Jalankan `start-nava-kiosk.bat` jika belum otomatis.
3. Pastikan kiosk dan display berada di monitor yang benar.
4. Cetak satu tiket percobaan.
5. Jalankan uji suara.
6. Operator login dan mengklaim loket.

### Menutup layanan

1. Selesaikan atau tandai status antrean tersisa.
2. Tutup Chrome kiosk menggunakan `Alt+F4`.
3. Jalankan `stop-nava-services.bat` jika backend memakai launcher BAT.
4. Tutup MySQL hanya jika memang dijalankan manual.
5. Lakukan backup database sesuai jadwal.

## 15. Backup dan pemulihan

Backup minimum:

- Dump database MySQL.
- File `.env` melalui penyimpanan rahasia teknisi, bukan repository publik.
- Folder `storage/app/public` untuk logo dan video upload.
- Source code sesuai commit/tag rilis.
- Folder `public/tts` beserta checksum manifest.

Contoh backup database:

```powershell
mysqldump -u root -p antrian_self_service > nava-backup.sql
```

Uji restore pada komputer lain secara berkala. Backup yang belum pernah diuji restore belum dapat dianggap aman.

## 16. Keamanan minimum

- Gunakan `APP_ENV=production` dan `APP_DEBUG=false` untuk operasional.
- Gunakan password database, admin, dan operator yang berbeda.
- Jangan membuka port 8000/8081 ke internet publik.
- Batasi akses hanya pada LAN/VLAN klinik.
- Gunakan akun operator individual.
- Jangan commit `.env`.
- Perbarui dependency setelah diuji di staging.
- Batasi origin Reverb ke alamat deployment pada instalasi permanen.
- Gunakan HTTPS jika aplikasi melintasi jaringan yang tidak sepenuhnya dipercaya.
- Pasang UPS pada komputer server, router, dan switch jika antrean harus tetap berjalan saat listrik tidak stabil.

## 17. Troubleshooting

### Launcher berhenti pada “aplikasi tidak siap”

- Periksa `.env` dan koneksi MySQL.
- Jalankan `php artisan migrate:status`.
- Pastikan port 8000 tidak digunakan aplikasi lain.
- Periksa jendela **NAVA Web Server**.

### Operator dapat membuka halaman tetapi realtime tidak berjalan

- Pastikan `REVERB_HOST` dan `VITE_REVERB_HOST` memakai IP server, bukan `127.0.0.1`.
- Jalankan ulang `npm run build` setelah perubahan Vite.
- Pastikan port 8081 dibuka pada Windows Firewall.
- Periksa proses **NAVA Reverb**.
- Jalankan `php artisan reverb:restart`.

### Display muncul pada monitor yang salah

- Pastikan Windows memakai mode Extend.
- Tutup kedua Chrome kiosk dengan `Alt+F4`.
- Ubah `KIOSK_X`, `KIOSK_Y`, `DISPLAY_X`, dan `DISPLAY_Y` pada launcher.
- Jalankan kembali launcher.

### Display fullscreen tetapi tidak bersuara

- Pastikan URL display mengandung `?autostart=1`.
- Pastikan launcher menggunakan `--autoplay-policy=no-user-gesture-required`.
- Atur TV HDMI sebagai output default Windows.
- Periksa Volume Mixer, volume TV, dan pengaturan volume NAVA.
- Jalankan uji suara dari admin.

### TTS offline lambat pada panggilan pertama

- Model utama sekitar 63 MB dan perlu dimuat pada cold start.
- Gunakan SSD dan LAN stabil.
- Jangan menghapus profil/cache Chrome display.
- Tunggu status TTS siap sebelum pelayanan dimulai.

### File WASM gagal dimuat

- Pastikan seluruh `public/tts` tersalin.
- Pastikan web server mengirim `.wasm` sebagai `application/wasm`.
- Verifikasi checksum melalui `public/tts/manifest.json`.

### Printer tidak ditemukan

- Pastikan printer menyala dan sudah dipasangkan pada Windows.
- Sambungkan ulang melalui tombol status printer pada kiosk.
- Jangan memakai mode Incognito.
- Jangan menghapus `storage/app/browser-profiles/ticket-kiosk`.

### Queue worker berhenti

```powershell
php artisan queue:restart
php artisan queue:work --sleep=1 --tries=3 --timeout=60
```

Periksa daftar job gagal:

```powershell
php artisan queue:failed
```

## 18. Verifikasi teknis sebelum rilis

```powershell
composer validate --strict
composer audit
npm audit
php artisan test --compact
npm run test:js
npm run build
php artisan optimize
php artisan migrate:status
```

Pastikan:

- Seluruh test lulus.
- Tidak ada dependency advisory yang belum dievaluasi.
- `public/build/manifest.json` tersedia.
- Seluruh migration berstatus Ran.
- Folder `public/tts` lengkap.
- `/up` mengembalikan HTTP 200.
- Browser display menunjukkan koneksi Reverb `connected`.
- Tidak ada failed job atau error JavaScript baru.

## 19. Batasan yang perlu dijelaskan kepada pembeli

- TTS offline tidak membutuhkan internet, tetapi perangkat tetap membutuhkan koneksi LAN ke server NAVA.
- Kualitas TTS online mengikuti voice browser/OS pada perangkat.
- Browser kiosk dapat mengaktifkan audio otomatis karena dijalankan dengan profil dan flag khusus.
- Pemilihan printer Bluetooth/Serial pertama kali tetap membutuhkan izin manual teknisi.
- Posisi window multi-monitor berbeda pada setiap instalasi dan perlu dikalibrasi sekali.
- Launcher BAT adalah solusi demo/pilot. Instalasi permanen membutuhkan process manager atau Windows Service.

## 20. Dokumen terkait

Panduan teknis TTS yang lebih rinci tersedia di:

```text
docs/PANDUAN_TTS_OFFLINE.md
```

Pertahankan `public/tts/THIRD_PARTY_NOTICES.txt` ketika source code didistribusikan atau dijual.
#   l a r a v e l - t a r a m e d i c  
 