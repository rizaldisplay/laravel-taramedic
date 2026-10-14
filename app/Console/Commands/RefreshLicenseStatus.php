<?php

namespace App\Console\Commands;

use App\Actions\Masterdata\Sdmk\RecomputeLicenseStatusAction;
use App\Models\HealthWorker;
use Illuminate\Console\Command;

class RefreshLicenseStatus extends Command
{
    protected $signature = 'sdmk:refresh-license-status';

    protected $description = 'Hitung ulang status izin (STR+SIP) semua SDMK karena masa berlaku berubah seiring waktu.';

    public function handle(RecomputeLicenseStatusAction $recompute): int
    {
        $changed = 0;

        HealthWorker::query()->with('sips')->chunkById(200, function ($workers) use ($recompute, &$changed) {
            foreach ($workers as $worker) {
                $changed += (int) $recompute->execute($worker);
            }
        });

        $this->info("Selesai. {$changed} SDMK berubah status izinnya.");

        return self::SUCCESS;
    }
}
