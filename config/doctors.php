<?php

return [
    // Jenis SDMK (nilai sdmk_references 'jenis_sdmk') yang otomatis dibuatkan baris `doctors`.
    'eligible_jenis_sdmk' => ['tenaga_medis'],

    // Selama migrasi: dokter lama yang belum dipetakan ke SDMK (health_worker_id NULL) tetap bisa dipilih.
    // Setelah semua terpetakan, set false agar hanya SDMK berizin & bertugas yang muncul.
    'allow_unlinked' => env('DOCTORS_ALLOW_UNLINKED', true),
];
