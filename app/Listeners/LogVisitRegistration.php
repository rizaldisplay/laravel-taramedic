<?php

namespace App\Listeners;

use App\Events\VisitRegistered;
use Illuminate\Support\Facades\Log;

class LogVisitRegistration
{
    public function handle(VisitRegistered $event): void
    {
        Log::info('Kunjungan didaftarkan', [
            'visit_id' => $event->visit->id,
            'nomor_kunjungan' => $event->visit->nomor_kunjungan,
            'patient_id' => $event->visit->patient_id,
            'ticket_id' => $event->visit->ticket_id,
            'registered_by' => $event->registeredBy,
        ]);
    }
}
