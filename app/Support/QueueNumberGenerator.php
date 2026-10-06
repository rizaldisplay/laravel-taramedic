<?php

namespace App\Support;

use App\Models\QueueTicket;
use App\Models\Service;
use Illuminate\Support\Carbon;

class QueueNumberGenerator
{
    /**
     * Allocate the next sequence for a service/day.
     *
     * Caller must hold a lock on the service row (or equivalent) inside
     * an open database transaction so concurrent issuers serialize.
     */
    public function nextSequence(Service $service, ?Carbon $date = null): int
    {
        $queueDate = ($date ?? today())->toDateString();

        $lastSequence = QueueTicket::query()
            ->where('service_id', $service->id)
            ->whereDate('queue_date', $queueDate)
            ->max('sequence');

        return ((int) $lastSequence) + 1;
    }

    public function format(Service $service, int $sequence): string
    {
        $padding = (int) config('queue_system.ticket_number_padding', 3);

        return sprintf(
            '%s%s',
            strtoupper($service->code),
            str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT),
        );
    }
}
