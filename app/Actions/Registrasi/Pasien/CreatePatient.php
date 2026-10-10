<?php

namespace App\Actions\Registrasi\Pasien;

use App\DTOs\Registrasi\PatientData;
use App\Events\PatientRegistered;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

final class CreatePatient
{
    public function __construct(
        private readonly GenerateMedicalRecordNumber $generateMedicalRecordNumber,
    ) {
    }

    public function execute(PatientData $data, array $regionNames, ?int $createdBy = null): Patient
    {
        $patient = DB::transaction(fn () => Patient::create([
            ...$data->toAttributes(),
            ...$regionNames,
            'nomor_rm' => $this->generateMedicalRecordNumber->execute(),
            'created_by' => $createdBy,
        ]));

        // Event dikirim SETELAH transaksi commit, supaya listener tidak
        // pernah melihat pasien yang akhirnya di-rollback.
        PatientRegistered::dispatch($patient, $data->ticketId, $createdBy);

        return $patient;
    }
}
