<?php

namespace App\Exceptions\Queue;

class CounterNotAvailableException extends QueueDomainException
{
    public function __construct()
    {
        parent::__construct('Loket tidak tersedia atau tidak aktif.');
    }
}
