<?php

namespace App\Observers;

use App\Actions\Doctor\SyncDoctorsForWorkerAction;
use App\Models\HealthWorker;

class HealthWorkerObserver
{
    public bool $afterCommit = true;

    private const WATCHED = ['status', 'jenis_sdmk', 'nama_lengkap', 'gelar_depan', 'gelar_belakang', 'license_status'];

    public function __construct(private readonly SyncDoctorsForWorkerAction $sync)
    {
    }

    public function saved(HealthWorker $worker): void
    {
        if ($worker->wasRecentlyCreated || $worker->wasChanged(self::WATCHED)) {
            $this->sync->execute($worker);
        }
    }
}
