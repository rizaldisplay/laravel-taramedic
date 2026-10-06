<?php

namespace App\Exceptions\Queue;

class CounterHasActiveTicketException extends QueueDomainException
{
    public function __construct()
    {
        parent::__construct(
            'Loket masih memiliki antrian aktif. Selesaikan atau tandai tidak hadir terlebih dahulu.',
        );
    }
}
