<?php

namespace App\Actions\Masterdata\Lokasi;

use App\Enums\Masterdata\Lokasi\StatusLokasi;
use App\Exceptions\LokasiConflictException;
use App\Models\ServiceLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EnsureStatusChangeAllowedAction
{
    /**
     * - Menonaktifkan: ditolak selama masih ada lokasi turunan (anak langsung) yang aktif.
     * - Mengaktifkan: ditolak bila induknya nonaktif.
     * Riwayat kunjungan tidak terpengaruh: lokasi nonaktif hanya hilang dari pilihan baru.
     */
    public function execute(ServiceLocation $lokasi, ?int $indukId, StatusLokasi $new): void
    {
        if (! $new->isActive()) {
            if ($lokasi->is_active) {
                $n = $lokasi->children()->where('is_active', true)->count();
                if ($n > 0) {
                    throw new LokasiConflictException('status', "Tidak dapat dinonaktifkan: masih ada {$n} lokasi turunan yang aktif. Nonaktifkan turunannya terlebih dahulu.");
                }

                // Ruang yang masih dipakai penempatan SDMK aktif (tabel dari paket SDMK; dilewati bila belum ada).
                if (Schema::hasTable('worker_placements')) {
                    $dipakai = DB::table('worker_placements')->where('service_location_id', $lokasi->id)->where('status', 'Aktif')->count();
                    if ($dipakai > 0) {
                        throw new LokasiConflictException('status', "Tidak dapat dinonaktifkan: dipakai {$dipakai} penempatan SDMK aktif. Pindahkan atau akhiri penempatannya terlebih dahulu.");
                    }
                }
            }

            return;
        }

        $induk = $indukId ? ServiceLocation::find($indukId) : null;
        if ($induk && ! $induk->is_active) {
            throw new LokasiConflictException('status', 'Tidak dapat diaktifkan: lokasi induk sedang nonaktif. Aktifkan induknya terlebih dahulu.');
        }
    }
}
