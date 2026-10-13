<?php

namespace App\Actions\Doctor;

use App\Models\Doctor;
use App\Models\HealthWorker;
use Illuminate\Support\Facades\DB;

final class SyncDoctorsForWorkerAction
{
    /**
     * Samakan baris `doctors` milik satu SDMK dengan keadaan SDMK saat ini:
     *  - satu baris per poli tempat ia punya penempatan aktif (dibuat bila belum ada);
     *  - baris di poli lain dinonaktifkan (TIDAK dihapus: kunjungan lama merujuk ke sana);
     *  - bila SDMK tidak aktif / izin habis / bukan jenis dokter: semua barisnya dinonaktifkan.
     *
     * Aman dipanggil berulang (idempoten). @return int jumlah baris yang berubah.
     */
    public function execute(HealthWorker $worker): int
    {
        return DB::transaction(function () use ($worker) {
            $changed = 0;
            $today = now(config('sdmk.timezone', 'Asia/Jakarta'))->toDateString();

            $eligible = in_array($worker->jenis_sdmk, config('doctors.eligible_jenis_sdmk'), true)
                && $worker->status->value === 'Aktif'
                && $worker->license_status->value !== 'Tidak Aktif';

            $poliIds = $eligible
                ? $worker->placements()
                    ->where('status', 'Aktif')
                    ->whereDate('tanggal_mulai', '<=', $today)
                    ->where(fn ($q) => $q->whereNull('tanggal_berakhir')->orWhereDate('tanggal_berakhir', '>=', $today))
                    ->pluck('poli_id')->unique()->all()
                : [];

            foreach ($poliIds as $poliId) {
                $doctor = Doctor::firstOrNew(['health_worker_id' => $worker->id, 'poli_id' => $poliId]);
                $doctor->fill(['name' => $worker->displayName(), 'is_active' => true]);

                if ($doctor->isDirty()) {
                    $doctor->save();
                    $changed++;
                }
            }

            $changed += Doctor::where('health_worker_id', $worker->id)
                ->whereNotIn('poli_id', $poliIds ?: [0])
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return $changed;
        });
    }
}
