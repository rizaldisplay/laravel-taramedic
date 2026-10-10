<?php

return [
    // Sumber data wilayah: https://wilayah.id (API statis, kode Kemendagri bertitik)
    'base_url' => env('WILAYAH_API_URL', 'https://wilayah.id/api'),

    // Batas waktu satu request ke Wilayah.id (detik)
    'timeout' => (int) env('WILAYAH_API_TIMEOUT', 10),

    // Lama cache segar (detik). Default 7 hari: data wilayah jarang berubah.
    // Salinan "stale" disimpan tanpa batas waktu sebagai cadangan saat API tidak bisa dihubungi.
    'cache_ttl' => (int) env('WILAYAH_CACHE_TTL', 60 * 60 * 24 * 7),
];
