<?php

namespace App\Exceptions\Queue;

class CounterClaimedException extends QueueDomainException
{
    public function __construct(string $claimerName = 'petugas lain')
    {
        parent::__construct("Loket sedang diklaim oleh {$claimerName}.");
    }
}
