<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class Sequence
{
    /**
     * Nomor berikutnya untuk penghitung bernama $name (mulai dari 1).
     * WAJIB dipanggil di dalam DB::transaction: baris dikunci sampai transaksi
     * selesai, sehingga permintaan serentak tidak mendapat nomor yang sama.
     */
    public static function next(string $name): int
    {
        DB::table('sequences')->insertOrIgnore(['name' => $name, 'value' => 0]);

        $row = DB::table('sequences')->where('name', $name)->lockForUpdate()->first();
        $next = $row->value + 1;

        DB::table('sequences')->where('name', $name)->update(['value' => $next]);

        return $next;
    }
}
