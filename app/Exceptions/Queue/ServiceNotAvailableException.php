<?php

namespace App\Exceptions\Queue;

class ServiceNotAvailableException extends QueueDomainException
{
    public function __construct()
    {
        parent::__construct('Layanan tidak tersedia atau tidak aktif.');
    }
}
