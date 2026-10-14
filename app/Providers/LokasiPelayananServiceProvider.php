<?php

namespace App\Providers;

use App\Events\LokasiPelayananChanged;
use App\Listeners\LogLokasiPelayananChange;
use App\Models\ServiceLocation;
use App\Policies\LokasiPelayananPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** Daftarkan di bootstrap/providers.php (Laravel 11+) atau config/app.php. */
class LokasiPelayananServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(ServiceLocation::class, LokasiPelayananPolicy::class);
        Event::listen(LokasiPelayananChanged::class, LogLokasiPelayananChange::class);
    }
}
