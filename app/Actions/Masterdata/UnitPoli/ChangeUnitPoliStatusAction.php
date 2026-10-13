<?php

namespace App\Actions\Masterdata\UnitPoli;

use App\Enums\Masterdata\UnitPoli\StatusUnit;
use App\Events\UnitPoliChanged;
use App\Models\Poli;

final class ChangeUnitPoliStatusAction
{
    public function __construct(private readonly EnsureCanDeactivateAction $ensureCanDeactivate)
    {
    }

    public function execute(Poli $poli, StatusUnit $status, ?int $userId): Poli
    {
        if ($poli->is_active === $status->isActive()) {
            return $poli; // sudah dalam status itu; idempoten
        }

        if (! $status->isActive()) {
            $this->ensureCanDeactivate->execute($poli);
        }

        $poli->update(['is_active' => $status->isActive()]);

        UnitPoliChanged::dispatch($poli, $status->isActive() ? 'diaktifkan' : 'dinonaktifkan', $userId);

        return $poli;
    }
}
