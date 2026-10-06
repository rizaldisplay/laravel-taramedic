<?php

namespace App\Exceptions\Queue;

use App\Enums\QueueTicketStatus;

class InvalidQueueTransitionException extends QueueDomainException
{
    public function __construct(QueueTicketStatus $from, QueueTicketStatus $to)
    {
        parent::__construct(
            sprintf(
                'Transisi status antrian tidak valid: %s → %s.',
                $from->value,
                $to->value,
            ),
        );
    }
}
