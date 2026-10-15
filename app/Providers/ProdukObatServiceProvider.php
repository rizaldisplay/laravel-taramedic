<?php

namespace App\Providers;

use App\Events\ProdukObatChanged;
use App\Listeners\LogProdukObatChange;
use App\Models\DrugProduct;
use App\Policies\ProdukObatPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** Daftarkan di bootstrap/providers.php (Laravel 11+) atau config/app.php. */
class ProdukObatServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(DrugProduct::class, ProdukObatPolicy::class);
        Event::listen(ProdukObatChanged::class, LogProdukObatChange::class);
    }
}
