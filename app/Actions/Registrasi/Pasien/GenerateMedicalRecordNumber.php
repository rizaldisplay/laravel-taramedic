<?php

namespace App\Actions\Registrasi\Pasien;

use Illuminate\Support\Facades\DB;

final class GenerateMedicalRecordNumber
{
    private const SEQUENCE = 'medical_record_number';

    /**
     * Nomor RM berurutan 8 digit (00000001, 00000002, ...).
     * WAJIB dipanggil di dalam DB::transaction: baris sequence dikunci
     * sampai transaksi selesai agar dua pendaftaran serentak tidak
     * mendapat nomor yang sama.
     */
    public function execute(): string
    {
        DB::table('sequences')->insertOrIgnore(['name' => self::SEQUENCE, 'value' => 0]);

        $row = DB::table('sequences')
            ->where('name', self::SEQUENCE)
            ->lockForUpdate()
            ->first();

        $next = $row->value + 1;

        DB::table('sequences')->where('name', self::SEQUENCE)->update(['value' => $next]);

        return str_pad((string) $next, 8, '0', STR_PAD_LEFT);
    }
}
