<?php

namespace App\Providers;

use App\Models\HealthWorker;
use App\Models\WorkerPlacement;
use App\Observers\HealthWorkerObserver;
use App\Observers\WorkerPlacementObserver;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/** Daftarkan di bootstrap/providers.php (Laravel 11+) atau config/app.php. */
class DoctorLinkServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        HealthWorker::observe(HealthWorkerObserver::class);
        WorkerPlacement::observe(WorkerPlacementObserver::class);

        // Penempatan berakhir (tanggal lewat) tidak memicu event model, jadi dicek ulang tiap hari.
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('doctors:sync')->dailyAt('00:20')->timezone(config('sdmk.timezone', 'Asia/Jakarta'))->onOneServer();
        });
    }
}
