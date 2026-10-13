<?php

namespace App\Events;

use App\Models\Poli;
use Illuminate\Foundation\Events\Dispatchable;

class UnitPoliChanged
{
    use Dispatchable;

    /** @param 'dibuat'|'diubah'|'diaktifkan'|'dinonaktifkan' $aksi */
    public function __construct(
        public readonly Poli $poli,
        public readonly string $aksi,
        public readonly ?int $userId = null,
    ) {
    }
}
