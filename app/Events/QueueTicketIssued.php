<?php

namespace App\Events;

use App\Models\QueueTicket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueTicketIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public QueueTicket $ticket,
    ) {}
}
