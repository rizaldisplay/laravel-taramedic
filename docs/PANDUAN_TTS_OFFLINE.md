# Panduan Implementasi TTS Offline Bahasa Indonesia

Mode **TTS Offline Lokal** memakai model Piper `id_ID-news_tts-medium` dan ONNX WebAssembly. Seluruh model, phonemizer, serta runtime berada di `public/tts`, sehingga browser display tidak menghubungi API, CDN, atau layanan TTS internet. Mode **TTS Online Browser** memakai Web Speech API dan ketersediaan voice mengikuti browser serta sistem operasi perangkat.

Yang dimaksud offline adalah tidak membutuhkan koneksi internet. Perangkat display tetap harus dapat membuka aplikasi Laravel melalui server lokal/LAN atau server yang sudah terpasang pada perangkat tersebut.

## Persiapan server atau perangkat baru

1. Salin seluruh proyek. Pastikan folder `public/tts` ikut tersalin; ukurannya sekitar 92 MB dan tidak boleh dikecualikan dari paket deployment.
2. Siapkan PHP 8.3, Composer, database yang didukung Laravel, serta Node.js yang kompatibel dengan Vite 8.
3. Pasang dan bangun aplikasi:

   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   php artisan migrate --force
   php artisan optimize
   ```

4. Arahkan document root web server ke folder `public` seperti deployment Laravel biasa.
5. Pastikan web server mengirim file `.wasm` dengan MIME type `application/wasm`. Apache/Nginx modern umumnya sudah menyediakannya.

## Verifikasi file suara

Manifest ukuran dan checksum tersedia di `public/tts/manifest.json`. Di Windows, cek contoh file utama dengan:

```powershell
Get-FileHash -Algorithm SHA256 public/tts/models/id_ID-news_tts-medium/id_ID-news_tts-medium.onnx
```

Hash model yang benar:

```text
ed8f02aa593f7af6b19acbdb8142e0da0dd72f46194eb33d38e0eb10a52597e8
```

Jika menggunakan Linux, jalankan `sha256sum` pada file yang sama.

## Aktivasi pada aplikasi

1. Masuk sebagai admin dan buka **Pengaturan > Suara**.
2. Pilih **TTS Offline Lokal** untuk operasional tanpa internet atau **TTS Online Browser** untuk memakai voice browser/OS.
3. Atur kalimat, kecepatan, karakter intonasi lokal atau pitch online, volume, nada pembuka/penutup, pengulangan, dan jeda. Bahasa dikunci otomatis ke Bahasa Indonesia sehingga tidak ada kode bahasa yang perlu diisi.
4. Tekan **Uji suara A001 · Loket 1**. Pemuatan pertama dapat lebih lambat karena browser membaca model 63 MB dari server lokal.
5. Buka halaman display pada Chrome atau Edge versi terbaru. Untuk mode online, pastikan perangkat memiliki voice Bahasa Indonesia dan koneksi yang dibutuhkan voice tersebut.
6. Jika display dibuka secara normal, tekan **Aktifkan suara TV** satu kali karena kebijakan autoplay browser. Launcher Windows `start-nava-kiosk.bat` membuka Chrome dengan kebijakan autoplay kiosk dan URL `/display?autostart=1`, sehingga aktivasi serta pemanasan TTS dilakukan otomatis.
7. Lakukan satu panggilan antrean dan pastikan suara terdengar.

Mode penyusunan suara dari file huruf, angka, dan frasa sudah dihapus. Hanya `public/sounds/opening.mp3` dan `public/sounds/closing.mp3` yang masih dipakai bila nada pembuka/penutup diaktifkan. Jika kedua file itu juga akan dihapus, matikan opsi nada pembuka dan penutup terlebih dahulu.

Nomor antrean dibentuk sebagai segmen terpisah dengan tempo lebih lambat dan jeda tetap. Contohnya `A001` dibaca “a — kosong — kosong — satu”, bukan sebagai angka seribu satu. Preset **Tenang**, **Natural**, dan **Ekspresif** mengubah kestabilan, variasi fonem, serta tempo, tetapi tetap memakai identitas satu pembicara dari model lokal yang sama.

Opsi **Sebutkan angka 0** menentukan cara nomor dibaca pada kedua mode TTS. Jika aktif, `A001` dibaca “a — kosong — kosong — satu”. Jika dimatikan, nomor yang sama dibaca “a — satu”. TTS offline juga memakai preamp dan soft limiter lokal agar suara lebih lantang tanpa melewati batas aman audio digital.

Setelah pemuatan pertama, browser dapat menyimpan model dalam HTTP cache. Menghapus data situs/browser akan membuat model dimuat ulang dari server lokal.

## Pengujian benar-benar tanpa internet

1. Pastikan server Laravel dan perangkat display berada di jaringan lokal yang sama.
2. Buka halaman display dan aktifkan suara saat jaringan masih normal.
3. Putuskan akses internet, tetapi jangan putuskan koneksi LAN ke server aplikasi.
4. Panggil antrean dari operator. Suara harus tetap dibuat dan diputar.
5. Pada DevTools > Network, tidak boleh ada permintaan ke Hugging Face, jsDelivr, cdnjs, atau API TTS lain.

## Pemecahan masalah

- **TTS offline gagal lalu memakai suara browser:** fallback antarmode sedang bekerja. Periksa apakah lima file pada `public/tts/manifest.json` tersedia dan tidak rusak.
- **TTS online terdengar berbeda pada perangkat lain:** hal ini normal karena voice berasal dari browser/OS. Sistem memprioritaskan `id-ID`, kemudian Melayu, lalu voice default.
- **File WASM gagal dimuat:** periksa status HTTP dan MIME type `application/wasm` pada web server.
- **Tidak ada suara:** pada mode normal tekan kembali **Aktifkan suara TV**. Pada mode kiosk pastikan launcher memakai `--autoplay-policy=no-user-gesture-required` dan URL mengandung `?autostart=1`. Cek juga volume browser/perangkat, lalu jalankan uji suara dari pengaturan.
- **Pemuatan sangat lama:** model utama berukuran sekitar 63 MB. Gunakan LAN kabel/Wi-Fi lokal yang stabil dan aktifkan cache file statis.
- **Ada jeda setelah nada pembuka:** atur **Jeda setelah nada pembuka** ke `0`. Sintesis dimulai bersamaan saat opening diputar, sehingga audio panggilan dapat langsung dimulai setelah opening selesai.
- **Perangkat lama kehabisan memori:** gunakan Chrome/Edge terbaru pada perangkat minimal 4 GB RAM atau pilih **TTS Online Browser**.
- **Deployment cPanel gagal karena batas upload:** kirim proyek melalui SSH/Git atau arsip terpisah, lalu pastikan folder `public/tts` berada pada document root yang sama dengan aset publik aplikasi.

## Lisensi distribusi

Saat memindahkan atau menjual source code, pertahankan `public/tts/THIRD_PARTY_NOTICES.txt`. Model Indonesia, Piper WASM, dan ONNX Runtime yang disertakan menggunakan lisensi MIT; tautan sumbernya tercantum dalam file tersebut.
