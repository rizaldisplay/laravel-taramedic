<?php

namespace App\Exceptions\Queue;

class CounterServiceMismatchException extends QueueDomainException
{
    public function __construct()
    {
        parent::__construct('Loket tidak menangani layanan antrian ini.');
    }
}
