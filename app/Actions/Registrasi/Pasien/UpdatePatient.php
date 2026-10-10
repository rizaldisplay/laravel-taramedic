<?php

namespace App\Actions\Registrasi\Pasien;

use App\DTOs\Registrasi\PatientData;
use App\Events\PatientUpdated;
use App\Models\Patient;

final class UpdatePatient
{
    /**
     * Nomor RM, nomor IHS, dan status SATUSEHAT tidak pernah diubah dari sini.
     *
     * @param array<string, string> $regionNames
     */
    public function execute(Patient $patient, PatientData $data, array $regionNames, ?int $updatedBy = null): Patient
    {
        $patient->fill([
            ...$data->toAttributes(),
            ...$regionNames,
            'updated_by' => $updatedBy,
        ]);

        // Nomor identitas berubah -> status verifikasi sebelumnya tidak berlaku lagi.
        if ($patient->isDirty(['nik', 'nomor_paspor'])) {
            $patient->nik_verified_at = null;
        }

        $patient->save();

        $changed = array_values(array_diff(array_keys($patient->getChanges()), ['updated_at']));
        if ($changed !== []) {
            PatientUpdated::dispatch($patient, $changed, $updatedBy);
        }

        return $patient;
    }
}
