<?php

namespace App\Events;

use App\Models\Patient;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Patient $patient,
        public readonly ?string $ticketId = null,
        public readonly ?int $registeredBy = null,
    ) {
    }
}
