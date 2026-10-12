<?php

return [
    // Disk penyimpanan berkas SDMK (foto, dokumen SIP, dokumen kompetensi). Harus disk PRIVAT.
    'disk' => env('SDMK_DISK', 'local'),
    'max_file_kb' => 2048,

    // Izin dianggap "Akan Berakhir" bila habis dalam sekian hari.
    'expiring_days' => 30,

    'kewarganegaraan' => [
        ['value' => 'WNI', 'label' => 'WNI (Warga Negara Indonesia)'],
        ['value' => 'WNA', 'label' => 'WNA (Warga Negara Asing)'],
    ],

    'hubungan' => ['Suami', 'Istri', 'Ayah', 'Ibu', 'Anak', 'Saudara Kandung', 'Kerabat', 'Teman'],

    'durasi_layanan' => [5, 10, 15, 20, 30, 45, 60],

    // Awalan nomor SDMK: SDMK-000001
    'code_prefix' => 'SDMK-',

    // Zona waktu untuk tampilan waktu (terakhir sinkron, riwayat).
    'timezone' => env('SDMK_TIMEZONE', 'Asia/Jakarta'),
];
