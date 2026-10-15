<?php

namespace App\Events;

use App\Models\Counter;
use Illuminate\Foundation\Events\Dispatchable;

class LoketChanged
{
    use Dispatchable;

    /** @param 'dibuat'|'diubah'|'diaktifkan'|'dinonaktifkan' $aksi */
    public function __construct(
        public readonly Counter $loket,
        public readonly string $aksi,
        public readonly ?int $userId = null,
    ) {
    }
}
