<?php

namespace App\Actions\Queue;

use App\Enums\QueueTicketStatus;
use App\Events\QueueTicketStatusChanged;
use App\Models\QueueTicket;
use App\Support\LocksQueueTicket;
use App\Support\QueueTransitionValidator;
use Illuminate\Support\Facades\DB;

class MarkQueueTicketNoShow
{
    use LocksQueueTicket;

    public function __construct(
        private QueueTransitionValidator $transitionValidator,
    ) {}

    public function handle(QueueTicket $ticket): QueueTicket
    {
        [$updated, $previousStatus] = DB::transaction(function () use ($ticket): array {
            $locked = $this->lockTicket($ticket);
            $previousStatus = $locked->status;

            $this->transitionValidator->assertCanTransition($locked, QueueTicketStatus::NoShow);

            $locked->update([
                'status' => QueueTicketStatus::NoShow,
                'completed_at' => now(),
            ]);

            return [$locked->fresh(['service', 'counter']), $previousStatus];
        });

        QueueTicketStatusChanged::dispatch($updated, $previousStatus);

        return $updated;
    }
}