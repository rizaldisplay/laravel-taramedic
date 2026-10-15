<?php

namespace App\Enums\Masterdata\Loket;

use App\Support\HasOptions;

/** Nilai = teks yang dipakai UI (OPSI_TIPE pada loketCounterHelpers.ts). */
enum TipeLoket: string
{
    use HasOptions;

    case Pendaftaran = 'Ruang Pendaftaran';
    case Poli = 'Ruang Poli';
    case Farmasi = 'Ruang Farmasi';
    case Kasir = 'Ruang Kasir';
}
