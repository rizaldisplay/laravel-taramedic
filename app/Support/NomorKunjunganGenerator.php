<?php

namespace App\Support;

use App\Models\Kunjungan;
use Carbon\CarbonInterface;

/**
 * Format: KJ-YYYYMMDD-0001 (urutan reset per hari).
 *
 * PENTING: harus dipanggil di dalam lock (lihat RegistrasiKunjunganAction)
 * agar dua request bersamaan tidak mendapat nomor yang sama.
 */
class NomorKunjunganGenerator
{
    public function next(CarbonInterface $tanggal): string
    {
        $prefix = 'KJ-'.$tanggal->format('Ymd').'-';

        $terakhir = Kunjungan::query()
            ->where('nomor_kunjungan', 'like', $prefix.'%')
            ->orderByDesc('nomor_kunjungan')
            ->value('nomor_kunjungan');

        $urut = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1;

        return $prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }
}
