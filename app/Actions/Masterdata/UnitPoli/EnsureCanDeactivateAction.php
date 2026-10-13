<?php

namespace App\Actions\Masterdata\UnitPoli;

use App\Exceptions\UnitPoliConflictException;
use App\Models\Poli;

final class EnsureCanDeactivateAction
{
    /** Unit/poli tidak boleh dinonaktifkan selama masih punya dokter aktif. */
    public function execute(Poli $poli): void
    {
        $count = $poli->doctors()->where('is_active', true)->count();

        if ($count > 0) {
            throw UnitPoliConflictException::inUse($count);
        }
    }
}
