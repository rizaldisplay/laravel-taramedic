<?php

namespace App\Actions\Masterdata\UnitPoli;

use App\DTOs\Masterdata\UnitPoliData;
use App\Events\UnitPoliChanged;
use App\Models\Poli;

final class UpdateUnitPoliAction
{
    public function __construct(private readonly EnsureCanDeactivateAction $ensureCanDeactivate)
    {
    }

    public function execute(Poli $poli, UnitPoliData $data, ?int $userId): Poli
    {
        $wasActive = $poli->is_active;

        if ($wasActive && ! $data->status->isActive()) {
            $this->ensureCanDeactivate->execute($poli);
        }

        $poli->update($data->toAttributes());

        UnitPoliChanged::dispatch($poli, 'diubah', $userId);

        return $poli;
    }
}
