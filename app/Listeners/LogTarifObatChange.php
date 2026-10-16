<?php

namespace App\Listeners;

use App\Events\TarifObatChanged;
use Illuminate\Support\Facades\Log;

class LogTarifObatChange
{
    public function handle(TarifObatChanged $event): void
    {
        Log::info('Tarif obat ' . $event->aksi, [
            'drug_price_id' => $event->tarif->id,
            'drug_product_id' => $event->tarif->drug_product_id,
            'penjamin' => $event->tarif->penjamin->value,
            'user_id' => $event->userId,
        ]);
    }
}
