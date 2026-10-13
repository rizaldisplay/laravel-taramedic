<?php

namespace App\Observers;

use App\Actions\Doctor\SyncDoctorsForWorkerAction;
use App\Models\WorkerPlacement;

class WorkerPlacementObserver
{
    public bool $afterCommit = true;

    public function __construct(private readonly SyncDoctorsForWorkerAction $sync)
    {
    }

    public function saved(WorkerPlacement $placement): void
    {
        $this->sync->execute($placement->healthWorker);
    }

    public function deleted(WorkerPlacement $placement): void
    {
        $this->sync->execute($placement->healthWorker);
    }
}
