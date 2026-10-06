<?php

namespace App\Services;

use App\DTOs\Queue\AnnouncementPayload;
use App\Models\AnnouncementConfig;
use App\Models\Counter;
use App\Models\QueueCall;
use App\Models\QueueTicket;

class AnnouncementService
{
    public function build(QueueTicket $ticket, Counter $counter, QueueCall $call): AnnouncementPayload
    {
        $ticket->loadMissing('service');
        $config = AnnouncementConfig::query()->first();

        $template = $config?->tts_template
            ?? config('queue_system.default_tts_template');

        $message = strtr($template, [
            '{ticket_number}' => $ticket->ticket_number,
            '{service_name}' => $ticket->service->name,
            '{counter_name}' => $counter->name,
            '{counter_code}' => $counter->code,
        ]);

        return new AnnouncementPayload(
            message: $message,
            ticketNumber: $ticket->ticket_number,
            serviceName: $ticket->service->name,
            serviceCode: $ticket->service->code,
            counterName: $counter->name,
            counterCode: $counter->code,
            callId: $call->id,
            calledAt: $call->called_at->toIso8601String(),
        );
    }
}
