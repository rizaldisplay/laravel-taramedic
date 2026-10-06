<?php

namespace App\Listeners;

use App\Events\QueueDisplayUpdated;
use App\Events\QueueTicketCalled;
use App\Services\QueueStatisticsService;

class BroadcastQueueDisplayUpdate
{
    public function __construct(
        private QueueStatisticsService $statisticsService,
    ) {}

    public function handle(object $event): void
    {
        $this->statisticsService->forgetCache();

        if (config('broadcasting.default') === 'null') {
            return;
        }

        try {
            $announcement = null;

            if ($event instanceof QueueTicketCalled) {
                $announcement = $event->announcement->toArray();
            }

            // Signal-only: the display refreshes its latest global board state.
            broadcast(new QueueDisplayUpdated($announcement));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
