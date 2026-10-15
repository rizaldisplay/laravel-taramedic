<?php

namespace App\Listeners;

use App\Events\ProdukObatChanged;
use Illuminate\Support\Facades\Log;

class LogProdukObatChange
{
    public function handle(ProdukObatChanged $event): void
    {
        Log::info('Produk obat ' . $event->aksi, [
            'drug_product_id' => $event->produk->id,
            'kfa_code' => $event->produk->kfa_code,
            'user_id' => $event->userId,
        ]);
    }
}
