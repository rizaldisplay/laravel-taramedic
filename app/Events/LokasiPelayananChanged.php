<?php

namespace App\Events;

use App\Models\ServiceLocation;
use Illuminate\Foundation\Events\Dispatchable;

class LokasiPelayananChanged
{
    use Dispatchable;

    /** @param 'dibuat'|'diubah'|'diaktifkan'|'dinonaktifkan' $aksi */
    public function __construct(
        public readonly ServiceLocation $lokasi,
        public readonly string $aksi,
        public readonly ?int $userId = null,
    ) {
    }
}
