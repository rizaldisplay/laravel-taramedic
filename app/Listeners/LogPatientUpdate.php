<?php

namespace App\Listeners;

use App\Events\PatientUpdated;
use Illuminate\Support\Facades\Log;

class LogPatientUpdate
{
    /** Jejak audit: hanya NAMA kolom yang berubah, tidak mencatat nilai data pribadi. */
    public function handle(PatientUpdated $event): void
    {
        Log::info('Data pasien diubah', [
            'patient_id' => $event->patient->id,
            'nomor_rm' => $event->patient->nomor_rm,
            'changed_fields' => $event->changedFields,
            'updated_by' => $event->updatedBy,
        ]);
    }
}
