<?php

namespace App\Listeners;

use App\Events\LoketChanged;
use Illuminate\Support\Facades\Log;

class LogLoketChange
{
    public function handle(LoketChanged $event): void
    {
        Log::info('Loket ' . $event->aksi, [
            'counter_id' => $event->loket->id,
            'code' => $event->loket->code,
            'user_id' => $event->userId,
        ]);
    }
}
