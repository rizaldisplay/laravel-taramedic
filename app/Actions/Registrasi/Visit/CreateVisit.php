<?php

namespace App\Actions\Registrasi\Visit;

use App\DTOs\Registrasi\VisitData;
use App\Events\VisitRegistered;
use App\Models\Visit;
use App\Support\Sequence;
use Illuminate\Support\Facades\DB;

final class CreateVisit
{
    public function execute(VisitData $data, ?int $registeredBy = null): Visit
    {
        $visit = DB::transaction(function () use ($data, $registeredBy) {
            $now = now();
            $urutan = Sequence::next('visit:' . $now->format('Ymd'));

            return Visit::create([
                'nomor_kunjungan' => sprintf('KJ%s-%04d', $now->format('Ymd'), $urutan),
                'patient_id' => $data->patientId,
                'poli_id' => $data->poliId,
                'doctor_id' => $data->doctorId,
                'service_location_id' => $data->locationId,
                'jenis_layanan' => $data->jenisLayanan,
                'cara_masuk' => $data->caraMasuk,
                'penjamin' => $data->penjamin,
                'jenis_pembayaran' => $data->jenisPembayaran,
                'prioritas' => $data->prioritas,
                'counter_number' => $data->counterNumber,
                'ticket_id' => $data->ticketId,
                'status' => 'Terdaftar',
                'registered_by' => $registeredBy,
                'registered_at' => $now,
                'visit_date' => $now->toDateString(),
            ]);
        });

        VisitRegistered::dispatch($visit, $registeredBy);

        return $visit;
    }
}
