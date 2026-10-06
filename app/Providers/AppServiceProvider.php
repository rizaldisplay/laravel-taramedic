<?php

namespace App\Providers;

use App\Events\QueueTicketCalled;
use App\Events\QueueTicketIssued;
use App\Events\QueueTicketStatusChanged;
use App\Listeners\BroadcastQueueDisplayUpdate;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user): bool => $user->role->canAccessAdmin());

        Gate::define('access-operator', fn (User $user): bool => $user->role->canAccessOperator());

        Event::listen([
            QueueTicketIssued::class,
            QueueTicketCalled::class,
            QueueTicketStatusChanged::class,
        ], BroadcastQueueDisplayUpdate::class);
    }
}
