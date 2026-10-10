<?php

namespace App\Listeners;

use App\Events\PatientRegistered;
use Illuminate\Support\Facades\Log;

class LogPatientRegistration
{
    /** Jejak audit. Sengaja tidak mencatat NIK atau data pribadi lain. */
    public function handle(PatientRegistered $event): void
    {
        Log::info('Pasien baru didaftarkan', [
            'patient_id' => $event->patient->id,
            'nomor_rm' => $event->patient->nomor_rm,
            'ticket_id' => $event->ticketId,
            'registered_by' => $event->registeredBy,
        ]);
    }
}
