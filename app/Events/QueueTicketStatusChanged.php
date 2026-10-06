<?php

namespace App\Events;

use App\Enums\QueueTicketStatus;
use App\Models\QueueTicket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueTicketStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public QueueTicket $ticket,
        public QueueTicketStatus $previousStatus,
    ) {}
}
