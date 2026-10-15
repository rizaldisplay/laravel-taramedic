<?php

namespace Database\Seeders;

use App\Enums\Masterdata\Loket\TipeLoket;
use App\Models\Counter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DATA CONTOH untuk pengembangan; ganti dengan loket klinik Anda.
 * Loket 1-4 memakai id 1-4 agar sama dengan config('registration.counters') / nomor loket di kunjungan lama.
 */
class LoketCounterSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $loket = Counter::firstOrNew(['code' => 'LOKET-' . $i]);
            if (! $loket->exists) {
                $loket->id = $i;
            }
            $loket->fill([
                'name' => 'Loket ' . $i,
                'tipe' => TipeLoket::Pendaftaran,
                'deskripsi' => $i === 1 ? 'Pendaftaran pasien baru dan lama' : null,
                'is_active' => true,
            ])->save();
        }

        foreach ([
            ['KASIR-1', 'Loket Kasir 1', TipeLoket::Kasir, 'Pembayaran dan penyelesaian administrasi'],
            ['FARMASI-1', 'Loket Farmasi 1', TipeLoket::Farmasi, 'Penyerahan obat'],
            ['POLI-UMUM-1', 'Loket Poli Umum', TipeLoket::Poli, null],
        ] as [$code, $nama, $tipe, $deskripsi]) {
            Counter::updateOrCreate(['code' => $code], ['name' => $nama, 'tipe' => $tipe, 'deskripsi' => $deskripsi, 'is_active' => true]);
        }

        // PostgreSQL: id eksplisit tidak memajukan sequence.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('counters', 'id'), (SELECT MAX(id) FROM counters))");
        }
    }
}
