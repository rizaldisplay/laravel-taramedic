<?php

namespace App\Actions\Display;

use App\DTOs\Display\DisplayBoardData;
use App\DTOs\Display\NowServingItem;
use App\Enums\QueueTicketStatus;
use App\Models\QueueTicket;
use App\Services\DisplayTvSettingsService;
use App\Services\SettingsService;

class GetDisplayBoardData
{
    public function __construct(
        private DisplayTvSettingsService $displayTvSettings,
    ) {}

    public function handle(): DisplayBoardData
    {
        // 1. Antrean yang sedang dipanggil / dilayani
        $nowServingQuery = QueueTicket::query()
            ->forToday()
            ->whereIn('status', [QueueTicketStatus::Called, QueueTicketStatus::Serving])
            ->with(['service:id,name,code', 'counter:id,name,code'])
            ->withMax('calls as latest_call_id', 'id')
            ->orderByDesc('latest_call_id')
            ->latest('id');

        $nowServing = $nowServingQuery
            ->get()
            ->map(fn (QueueTicket $ticket): NowServingItem => $this->mapCallItem($ticket));

        // 2. Tiga Antrean Berikutnya (Next Queues)
        // Sesuaikan Enum 'Waiting' dengan nama status untuk antrean yang belum dipanggil di aplikasi Anda (misal: Pending/Waiting)
        $nextQueuesQuery = QueueTicket::query()
            ->forToday()
            ->where('status', QueueTicketStatus::Waiting) // <-- SESUAIKAN ENUM INI
            ->with(['service:id,name,code'])
            ->orderBy('id', 'asc') // Mengambil antrean yang paling pertama datang (FIFO)
            ->limit(3); // Ambil 3 saja

        $nextQueues = $nextQueuesQuery
            ->get()
            ->map(fn (QueueTicket $ticket): NowServingItem => $this->mapCallItem($ticket));

        $recentLimit = app(SettingsService::class)->getInt(
            'display_recent_limit',
            (int) config('queue_system.display_recent_limit', 4),
        );

        // 3. Riwayat antrean yang sudah selesai
        $recentCallsQuery = QueueTicket::query()
            ->forToday()
            ->whereNotNull('called_at')
            ->whereIn('status', [
                QueueTicketStatus::Completed,
                QueueTicketStatus::NoShow,
            ])
            ->with(['service:id,name,code', 'counter:id,name,code'])
            ->withMax('calls as latest_call_id', 'id')
            ->orderByDesc('called_at')
            ->orderByDesc('id')
            ->limit($recentLimit);

        $activeTicketNumbers = $nowServing
            ->map(fn (NowServingItem $item): string => $item->ticketNumber)
            ->all();

        $recentCalls = $recentCallsQuery
            ->get()
            ->reject(fn (QueueTicket $ticket): bool => in_array($ticket->ticket_number, $activeTicketNumbers, true))
            ->values()
            ->map(fn (QueueTicket $ticket): NowServingItem => $this->mapCallItem($ticket));

        return new DisplayBoardData(
            nowServing: $nowServing,
            recentCalls: $recentCalls,
            nextQueues: $nextQueues, // <-- PASSING DATA KE DTO
            signageContents: $this->displayTvSettings->signageContents(),
            generatedAt: now()->toIso8601String(),
        );
    }

    private function mapCallItem(QueueTicket $ticket): NowServingItem
    {
        return new NowServingItem(
            ticketNumber: $ticket->ticket_number,
            serviceName: $ticket->service->name,
            serviceCode: $ticket->service->code,
            counterName: $ticket->counter?->name ?? '-',
            counterCode: $ticket->counter?->code,
            status: $ticket->status->value,
            calledAt: $ticket->called_at?->toIso8601String(),
            callId: ($latestCallId = $ticket->getAttribute('latest_call_id')) !== null
                ? (int) $latestCallId
                : null,
        );
    }
}