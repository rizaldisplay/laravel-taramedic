<?php

namespace App\Providers;

use App\Events\TarifObatChanged;
use App\Listeners\LogTarifObatChange;
use App\Models\DrugPrice;
use App\Policies\TarifObatPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** Daftarkan di bootstrap/providers.php (Laravel 11+) atau config/app.php. */
class TarifObatServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(DrugPrice::class, TarifObatPolicy::class);
        Event::listen(TarifObatChanged::class, LogTarifObatChange::class);
    }
}
