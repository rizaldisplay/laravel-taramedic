<?php

namespace App\Events;

use App\Models\DrugPrice;
use Illuminate\Foundation\Events\Dispatchable;

class TarifObatChanged
{
    use Dispatchable;

    /** @param 'dibuat'|'diubah'|'diaktifkan'|'dinonaktifkan' $aksi */
    public function __construct(
        public readonly DrugPrice $tarif,
        public readonly string $aksi,
        public readonly ?int $userId = null,
    ) {
    }
}
