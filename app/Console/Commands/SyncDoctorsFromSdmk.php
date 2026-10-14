<?php

namespace App\Console\Commands;

use App\Actions\Doctor\SyncDoctorsForWorkerAction;
use App\Models\HealthWorker;
use Illuminate\Console\Command;

class SyncDoctorsFromSdmk extends Command
{
    protected $signature = 'doctors:sync';

    protected $description = 'Samakan seluruh baris doctors dengan SDMK (jenis, status, izin, penempatan). Idempoten.';

    public function handle(SyncDoctorsForWorkerAction $sync): int
    {
        $changed = 0;

        HealthWorker::query()->chunkById(200, function ($workers) use ($sync, &$changed) {
            foreach ($workers as $worker) {
                $changed += $sync->execute($worker);
            }
        });

        $this->info("Selesai. {$changed} baris doctors berubah.");

        return self::SUCCESS;
    }
}