<?php

namespace App\Listeners;

use App\Events\PatientRegistered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Menautkan pasien baru ke tiket antrean yang sedang dilayani.
 *
 * ASUMSI: tabel `tickets` punya kolom `patient_id`
 * (lihat migration add_patient_id_to_tickets_table). Sesuaikan nama
 * tabel/kolom dengan skema antrean Anda.
 */
class LinkPatientToQueueTicket
{
    public function handle(PatientRegistered $event): void
    {
        if ($event->ticketId === null) {
            return;
        }

        try {
            DB::table('tickets')
                ->where('id', $event->ticketId)
                ->whereNull('patient_id')
                ->update(['patient_id' => $event->patient->id, 'updated_at' => now()]);
        } catch (Throwable $e) {
            // Pasien sudah tersimpan; kegagalan menautkan tiket tidak boleh
            // membuat pendaftaran terlihat gagal bagi petugas.
            Log::warning('Gagal menautkan pasien ke tiket antrean', [
                'patient_id' => $event->patient->id,
                'ticket_id' => $event->ticketId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
