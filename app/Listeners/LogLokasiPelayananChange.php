<?php

namespace App\Listeners;

use App\Events\LokasiPelayananChanged;
use Illuminate\Support\Facades\Log;

class LogLokasiPelayananChange
{
    public function handle(LokasiPelayananChanged $event): void
    {
        Log::info('Lokasi pelayanan ' . $event->aksi, [
            'service_location_id' => $event->lokasi->id,
            'kode' => $event->lokasi->kode,
            'user_id' => $event->userId,
        ]);
    }
}
