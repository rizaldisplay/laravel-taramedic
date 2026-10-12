<?php

namespace App\Actions\Masterdata\Sdmk;

use App\DTOs\Masterdata\HealthWorkerData;
use App\Events\SdmkActivityRecorded;
use App\Models\HealthWorker;

final class UpdateHealthWorkerIdentityAction
{
    /** @param array<string, string> $regionNames */
    public function execute(HealthWorker $worker, HealthWorkerData $data, array $regionNames, ?int $userId): HealthWorker
    {
        $worker->update([...$data->toAttributes(), ...$regionNames, 'updated_by' => $userId]);

        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'Identitas diubah', $worker->sdmk_code, $userId);

        return $worker;
    }
}
