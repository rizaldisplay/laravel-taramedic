<?php

namespace App\Actions\Queue;

use App\Enums\QueueTicketStatus;
use App\Events\QueueTicketStatusChanged;
use App\Models\QueueTicket;
use App\Support\LocksQueueTicket;
use App\Support\QueueTransitionValidator;
use Illuminate\Support\Facades\DB;

class SkipQueueTicket
{
    use LocksQueueTicket;

    public function __construct(
        private QueueTransitionValidator $transitionValidator,
    ) {}

    /**
     * Menunda antrian: kembalikan ke waiting dan pindahkan ke urutan belakang.
     */
    public function handle(QueueTicket $ticket): QueueTicket
    {
        [$updated, $previousStatus] = DB::transaction(function () use ($ticket): array {
            $locked = $this->lockTicket($ticket);
            $previousStatus = $locked->status;

            $this->transitionValidator->assertCanTransition($locked, QueueTicketStatus::Waiting);

            $locked->update([
                'status' => QueueTicketStatus::Waiting,
                'counter_id' => null,
                'called_at' => null,
                'skip_count' => $locked->skip_count + 1,
            ]);

            return [$locked->fresh(['service', 'counter']), $previousStatus];
        });

        QueueTicketStatusChanged::dispatch($updated, $previousStatus);

        return $updated;
    }
}
