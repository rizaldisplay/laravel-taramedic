<?php

namespace App\Actions\Masterdata\Loket;

use App\DTOs\Masterdata\LoketData;
use App\Events\LoketChanged;
use App\Models\Counter;
use Illuminate\Support\Facades\DB;

final class UpdateLoketAction
{
    public function __construct(private readonly EnsureRegistrationCounterRemainsAction $ensureRegistration)
    {
    }

    public function execute(Counter $loket, LoketData $data, ?int $userId): Counter
    {
        DB::transaction(function () use ($loket, $data) {
            $this->ensureRegistration->execute($loket, $data->tipe, $data->status);

            $loket->update([
                'code' => $data->code,
                'name' => $data->nama,
                'tipe' => $data->tipe,
                'deskripsi' => $data->deskripsi,
                'is_active' => $data->status->isActive(),
            ]);
        });

        LoketChanged::dispatch($loket, 'diubah', $userId);

        return $loket;
    }
}
