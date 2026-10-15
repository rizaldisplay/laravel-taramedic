<?php

namespace App\Actions\Masterdata\Loket;

use App\DTOs\Masterdata\LoketData;
use App\Events\LoketChanged;
use App\Models\Counter;

final class CreateLoketAction
{
    public function execute(LoketData $data, ?int $userId): Counter
    {
        $loket = Counter::create([
            'code' => $data->code,
            'name' => $data->nama,
            'tipe' => $data->tipe,
            'deskripsi' => $data->deskripsi,
            'is_active' => $data->status->isActive(),
        ]);

        LoketChanged::dispatch($loket, 'dibuat', $userId);

        return $loket;
    }
}
