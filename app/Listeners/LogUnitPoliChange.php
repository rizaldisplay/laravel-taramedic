<?php

namespace App\Listeners;

use App\Events\UnitPoliChanged;
use Illuminate\Support\Facades\Log;

class LogUnitPoliChange
{
    public function handle(UnitPoliChanged $event): void
    {
        Log::info('Unit & Poli ' . $event->aksi, [
            'poli_id' => $event->poli->id,
            'kode' => $event->poli->kode,
            'user_id' => $event->userId,
        ]);
    }
}
