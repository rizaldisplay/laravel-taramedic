<?php

namespace App\Actions\Masterdata\Sdmk;

use App\DTOs\Masterdata\HealthWorkerData;
use App\Events\SdmkActivityRecorded;
use App\Models\HealthWorker;
use Illuminate\Support\Facades\DB;

final class CreateHealthWorkerAction
{
    public function __construct(private readonly GenerateSdmkCodeAction $generateCode)
    {
    }

    /** @param array<string, string> $regionNames */
    public function execute(HealthWorkerData $data, array $regionNames, ?int $userId): HealthWorker
    {
        $worker = DB::transaction(fn () => HealthWorker::create([
            ...$data->toAttributes(),
            ...$regionNames,
            'sdmk_code' => $this->generateCode->execute(),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]));

        SdmkActivityRecorded::dispatch($worker->id, 'riwayat', 'SDMK ditambahkan', $worker->sdmk_code, $userId);

        return $worker;
    }
}
