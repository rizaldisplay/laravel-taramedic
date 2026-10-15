<?php

namespace App\Events;

use App\Models\DrugProduct;
use Illuminate\Foundation\Events\Dispatchable;

class ProdukObatChanged
{
    use Dispatchable;

    /** @param 'dibuat'|'diubah'|'diaktifkan'|'dinonaktifkan' $aksi */
    public function __construct(
        public readonly DrugProduct $produk,
        public readonly string $aksi,
        public readonly ?int $userId = null,
    ) {
    }
}
