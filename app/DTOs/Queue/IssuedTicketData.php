<?php

namespace App\DTOs\Queue;

use App\Models\QueueTicket;

readonly class IssuedTicketData
{
    public function __construct(
        public QueueTicket $ticket,
        public int $estimatedWaitMinutes,
        public int $waitingCount,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ticket' => $this->ticket->loadMissing('service'),
            'estimated_wait_minutes' => $this->estimatedWaitMinutes,
            'waiting_count' => $this->waitingCount,
        ];
    }
}
