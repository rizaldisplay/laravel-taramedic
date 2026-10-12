<?php

namespace App\Services\Sdmk;

use App\Events\SdmkActivityRecorded;
use App\Models\HealthWorker;
use App\Models\WorkerPlacement;
use App\Models\WorkerScheduleSlot;
use Illuminate\Support\Facades\DB;

final class SdmkScheduleService
{
    /** @param array<string, mixed> $attrs */
    public function savePlacement(HealthWorker $worker, array $attrs, ?WorkerPlacement $placement, ?int $userId): WorkerPlacement
    {
        $placement = $placement ? tap($placement)->update($attrs) : $worker->placements()->create($attrs);

        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'Penempatan disimpan', $placement->peran, $userId);

        return $placement;
    }

    public function deletePlacement(HealthWorker $worker, WorkerPlacement $placement, ?int $userId): void
    {
        $placement->delete();
        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'Penempatan dihapus', $placement->peran, $userId);
    }

    /**
     * Simpan pengaturan jadwal + 7 baris hari sekaligus (semua-atau-tidak-sama-sekali).
     *
     * @param array<string, mixed> $setting kolom schedule_* pada health_workers
     * @param list<array<string, mixed>> $days baris worker_schedule_days (kunci: hari, ...)
     */
    public function saveSchedule(HealthWorker $worker, array $setting, array $days, ?int $userId): void
    {
        DB::transaction(function () use ($worker, $setting, $days) {
            $worker->update($setting);

            foreach ($days as $row) {
                $worker->scheduleDays()->updateOrCreate(['hari' => $row['hari']], $row);
            }
        });

        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'Jadwal praktik diubah', null, $userId);
    }

    /** @param array<string, mixed> $attrs */
    public function saveSlot(HealthWorker $worker, array $attrs, ?WorkerScheduleSlot $slot, ?int $userId): WorkerScheduleSlot
    {
        $slot = $slot ? tap($slot)->update($attrs) : $worker->scheduleSlots()->create($attrs);

        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'Slot waktu disimpan', $slot->hari, $userId);

        return $slot;
    }

    public function deleteSlot(HealthWorker $worker, WorkerScheduleSlot $slot, ?int $userId): void
    {
        $slot->delete();
        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'Slot waktu dihapus', $slot->hari, $userId);
    }
}
