<?php

namespace App\Services\Sdmk;

use App\Actions\Masterdata\Sdmk\VerifyIhsAction;
use App\Events\SdmkActivityRecorded;
use App\Models\HealthWorker;

final class SdmkIntegrationService
{
    public function __construct(private readonly VerifyIhsAction $verifyIhs)
    {
    }

    /** Hanya mapping lokal. Status IHS & IHS Number tidak bisa diubah dari form. */
    public function saveMapping(HealthWorker $worker, ?string $specialty, ?string $practitionerType, ?int $userId): void
    {
        $worker->update([
            'mapping_specialty' => $specialty,
            'mapping_practitioner_type' => $practitionerType,
            'updated_by' => $userId,
        ]);

        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'Mapping integrasi diubah', null, $userId);
    }

    public function verify(HealthWorker $worker, ?int $userId): HealthWorker
    {
        return $this->verifyIhs->execute($worker, $userId);
    }
}
