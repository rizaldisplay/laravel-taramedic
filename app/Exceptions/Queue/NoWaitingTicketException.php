<?php

namespace App\Exceptions\Queue;

class NoWaitingTicketException extends QueueDomainException
{
    public function __construct()
    {
        parent::__construct('Tidak ada antrian yang menunggu.');
    }
}
