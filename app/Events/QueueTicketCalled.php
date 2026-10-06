<?php

namespace App\Events;

use App\DTOs\Queue\AnnouncementPayload;
use App\Models\QueueCall;
use App\Models\QueueTicket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueTicketCalled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public QueueTicket $ticket,
        public QueueCall $call,
        public AnnouncementPayload $announcement,
    ) {}
}
