<?php

namespace App\Support;

use App\Enums\QueueTicketStatus;
use App\Exceptions\Queue\InvalidQueueTransitionException;
use App\Models\QueueTicket;

class QueueTransitionValidator
{
    /** @var array<string, list<QueueTicketStatus>> */
    private const TRANSITIONS = [
        'waiting' => [
            QueueTicketStatus::Called,
        ],
        'skip' => [
            QueueTicketStatus::Called,
        ],
        'called' => [
            QueueTicketStatus::Serving,
            QueueTicketStatus::Waiting,
            QueueTicketStatus::NoShow,
        ],
        'serving' => [
            QueueTicketStatus::Completed,
        ],
    ];

    public function assertCanTransition(QueueTicket $ticket, QueueTicketStatus $to): void
    {
        $from = $ticket->status;

        if ($from === $to) {
            throw new InvalidQueueTransitionException($from, $to);
        }

        $allowed = self::TRANSITIONS[$from->value] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new InvalidQueueTransitionException($from, $to);
        }
    }
}
