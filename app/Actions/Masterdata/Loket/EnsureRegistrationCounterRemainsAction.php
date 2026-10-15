<?php

namespace App\Actions\Masterdata\Loket;

use App\Enums\Masterdata\Loket\StatusLoket;
use App\Enums\Masterdata\Loket\TipeLoket;
use App\Exceptions\LoketConflictException;
use App\Models\Counter;

/**
 * Pendaftaran kunjungan membutuhkan minimal satu loket aktif bertipe "Ruang Pendaftaran".
 * Perubahan (nonaktifkan / ganti tipe) yang menghilangkan loket pendaftaran aktif TERAKHIR ditolak,
 * agar petugas tidak mendapati dropdown loket kosong.
 *
 * Panggil DI DALAM DB::transaction: baris loket pendaftaran aktif dikunci supaya dua permintaan
 * serentak tidak sama-sama lolos.
 */
final class EnsureRegistrationCounterRemainsAction
{
    public function execute(Counter $loket, TipeLoket $tipeBaru, StatusLoket $statusBaru): void
    {
        $sekarangPendaftaran = $loket->is_active && $loket->tipe === TipeLoket::Pendaftaran;
        $nantiPendaftaran = $statusBaru->isActive() && $tipeBaru === TipeLoket::Pendaftaran;

        if (! $sekarangPendaftaran || $nantiPendaftaran) {
            return;
        }

        $lain = Counter::query()->forRegistration()->whereKeyNot($loket->id)->lockForUpdate()->exists();
        if ($lain) {
            return;
        }

        if (! $statusBaru->isActive()) {
            throw new LoketConflictException('status', 'Tidak dapat dinonaktifkan: ini satu-satunya loket pendaftaran yang aktif. Aktifkan loket pendaftaran lain terlebih dahulu.');
        }

        throw new LoketConflictException('tipe', 'Tipe tidak dapat diubah: ini satu-satunya loket pendaftaran yang aktif. Aktifkan loket pendaftaran lain terlebih dahulu.');
    }
}
