<?php

namespace App\Providers;

use App\Events\SdmkActivityRecorded;
use App\Listeners\RecordSdmkActivity;
use App\Models\HealthWorker;
use App\Policies\HealthWorkerPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Daftarkan di bootstrap/providers.php (Laravel 11+) atau config/app.php (Laravel 10).
 */
class SdmkServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(HealthWorker::class, HealthWorkerPolicy::class);
        Event::listen(SdmkActivityRecorded::class, RecordSdmkActivity::class);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('sdmk:refresh-license-status')->dailyAt('00:10')->timezone(config('sdmk.timezone'))->onOneServer();
        });
    }
}
