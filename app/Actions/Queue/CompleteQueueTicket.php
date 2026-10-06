<?php

namespace App\Actions\Queue;

use App\Enums\QueueTicketStatus;
use App\Events\QueueTicketStatusChanged;
use App\Models\QueueTicket;
use App\Models\User;
use App\Support\LocksQueueTicket;
use App\Support\QueueTransitionValidator;
use Illuminate\Support\Facades\DB;

class CompleteQueueTicket
{
    use LocksQueueTicket;

    public function __construct(
        private QueueTransitionValidator $transitionValidator,
    ) {}

    public function handle(QueueTicket $ticket, ?User $operator = null): QueueTicket
    {
        [$updated, $previousStatus] = DB::transaction(function () use ($ticket, $operator): array {
            $locked = $this->lockTicket($ticket);
            $previousStatus = $locked->status;

            $this->transitionValidator->assertCanTransition($locked, QueueTicketStatus::Completed);

            $payload = [
                'status' => QueueTicketStatus::Completed,
                'completed_at' => now(),
                'handled_by_user_id' => $operator?->id ?? $locked->handled_by_user_id,
            ];

            if ($locked->serving_at === null) {
                $payload['serving_at'] = now();
            }

            $locked->update($payload);

            return [$locked->fresh(['service', 'counter']), $previousStatus];
        });

        QueueTicketStatusChanged::dispatch($updated, $previousStatus);

        return $updated;
    }
}