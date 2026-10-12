<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SdmkActivityRecorded
{
    use Dispatchable;

    /** @param 'riwayat'|'audit' $category */
    public function __construct(
        public readonly ?int $healthWorkerId,
        public readonly string $category,
        public readonly string $aksi,
        public readonly ?string $keterangan = null,
        public readonly ?int $userId = null,
    ) {
    }
}
