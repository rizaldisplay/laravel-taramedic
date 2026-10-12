<?php

namespace App\Listeners;

use App\Events\SdmkActivityRecorded;
use App\Models\WorkerActivity;

class RecordSdmkActivity
{
    /** Menyimpan jejak riwayat/audit. Pemanggil dilarang mengirim NIK atau data pribadi di $keterangan. */
    public function handle(SdmkActivityRecorded $event): void
    {
        WorkerActivity::create([
            'health_worker_id' => $event->healthWorkerId,
            'category' => $event->category,
            'aksi' => $event->aksi,
            'keterangan' => $event->keterangan,
            'user_id' => $event->userId,
        ]);
    }
}
