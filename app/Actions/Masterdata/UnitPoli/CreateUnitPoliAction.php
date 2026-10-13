<?php

namespace App\Actions\Masterdata\UnitPoli;

use App\DTOs\Masterdata\UnitPoliData;
use App\Events\UnitPoliChanged;
use App\Models\Poli;

final class CreateUnitPoliAction
{
    public function execute(UnitPoliData $data, ?int $userId): Poli
    {
        $poli = Poli::create($data->toAttributes());

        UnitPoliChanged::dispatch($poli, 'dibuat', $userId);

        return $poli;
    }
}
