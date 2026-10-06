<?php

namespace App\DTOs\Queue;

use App\Models\Antrean;
use App\Models\Counter;
use App\Models\QueueCall;
use App\Models\QueueTicket;

readonly class CalledTicketData
{
    public function __construct(
        public QueueTicket $ticket,
        public Counter $counter,
        public QueueCall $call,
        public AnnouncementPayload $announcement,
        public Antrean $loket,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ticket' => $this->ticket->loadMissing(['service', 'counter']),
            'counter' => $this->counter,
            'call' => $this->call,
            'announcement' => $this->announcement->toArray(),
        ];
    }
}
