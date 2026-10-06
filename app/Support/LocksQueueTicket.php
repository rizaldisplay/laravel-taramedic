<?php

namespace App\Support;

use App\Models\QueueTicket;

trait LocksQueueTicket
{
    protected function lockTicket(int|QueueTicket $ticket): QueueTicket
    {
        $id = $ticket instanceof QueueTicket ? $ticket->id : $ticket;

        return QueueTicket::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
