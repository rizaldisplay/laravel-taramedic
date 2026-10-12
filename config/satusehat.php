<?php

// Isi lewat .env. Selama client_id/client_secret kosong, verifikasi IHS
// mengembalikan 503 "belum dikonfigurasi" dan tidak ada permintaan keluar.
return [
    'auth_url' => env('SATUSEHAT_AUTH_URL', 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1'),
    'base_url' => env('SATUSEHAT_BASE_URL', 'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1'),
    'client_id' => env('SATUSEHAT_CLIENT_ID'),
    'client_secret' => env('SATUSEHAT_CLIENT_SECRET'),
    'organization_id' => env('SATUSEHAT_ORGANIZATION_ID'),
    'timeout' => 15,
];
