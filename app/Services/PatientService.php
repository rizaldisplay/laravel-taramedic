<?php

namespace App\Services;

use App\Actions\Registrasi\Pasien\CheckNik;
use App\Actions\Registrasi\Pasien\CreatePatient;
use App\Actions\Registrasi\Pasien\SearchPatients;
use App\Actions\Registrasi\Pasien\UpdatePatient;
use App\DTOs\Registrasi\PatientData;
use App\Exceptions\PatientAlreadyExistsException;
use App\Models\Patient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

final class PatientService
{
    public function __construct(
        private readonly CheckNik $checkNik,
        private readonly CreatePatient $createPatient,
        private readonly UpdatePatient $updatePatient,
        private readonly SearchPatients $searchPatients,
        private readonly RegionService $regions,
    ) {
    }

    public function search(string $term, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->searchPatients->execute($term, $page, $perPage);
    }

    public function findByNik(string $nik, ?int $exceptId = null): ?Patient
    {
        return $this->checkNik->execute($nik, $exceptId);
    }

    /** Data ringkas (untuk daftar & panel Data Pasien). */
    public function summary(Patient $patient): Patient
    {
        return $patient->loadMissing('latestVisit.poli');
    }

    /** Data lengkap tanpa penyamaran. Pembukaannya dicatat sebagai jejak audit. */
    public function detail(Patient $patient, ?int $viewedBy): Patient
    {
        Log::info('Data lengkap pasien dibuka', [
            'patient_id' => $patient->id,
            'viewed_by' => $viewedBy,
        ]);

        return $patient->loadMissing('creator');
    }

    public function register(PatientData $data, ?int $registeredBy = null): Patient
    {
        try {
            return $this->createPatient->execute($data, $this->regionNames($data), $registeredBy);
        } catch (UniqueConstraintViolationException) {
            // Validasi unique lolos tapi ada pendaftaran serentak dengan NIK/paspor sama.
            throw PatientAlreadyExistsException::forIdentity($data->jenisIdentitas);
        }
    }

    public function update(Patient $patient, PatientData $data, ?int $updatedBy = null): Patient
    {
        try {
            $updated = $this->updatePatient->execute($patient, $data, $this->regionNames($data), $updatedBy);
        } catch (UniqueConstraintViolationException) {
            throw PatientAlreadyExistsException::forIdentity($data->jenisIdentitas);
        }

        return $updated->loadMissing('creator');
    }

    /** @return array<string, string> */
    private function regionNames(PatientData $data): array
    {
        return $this->regions->resolveNames(
            $data->provinsi,
            $data->kabupatenKota,
            $data->kecamatan,
            $data->desaKelurahan,
        );
    }
}
