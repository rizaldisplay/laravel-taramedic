<?php

namespace App\Providers;

use App\Events\UnitPoliChanged;
use App\Listeners\LogUnitPoliChange;
use App\Models\Poli;
use App\Policies\UnitPoliPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** Daftarkan di bootstrap/providers.php (Laravel 11+) atau config/app.php. */
class UnitPoliServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Poli::class, UnitPoliPolicy::class);
        Event::listen(UnitPoliChanged::class, LogUnitPoliChange::class);
    }
}
