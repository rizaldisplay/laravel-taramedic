<?php

namespace App\Providers;

use App\Events\LoketChanged;
use App\Listeners\LogLoketChange;
use App\Models\Counter;
use App\Policies\LoketPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** Daftarkan di bootstrap/providers.php (Laravel 11+) atau config/app.php. */
class LoketServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Counter::class, LoketPolicy::class);
        Event::listen(LoketChanged::class, LogLoketChange::class);
    }
}
