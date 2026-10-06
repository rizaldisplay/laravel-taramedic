@php
    use App\Enums\QueueTicketStatus;

    $call = $call ?? null;
    $index = $index ?? 0;
    $isEmpty = $call === null;
    $status = $isEmpty ? null : QueueTicketStatus::tryFrom($call['status'] ?? '');
    $statusLabel = $status?->label() ?? 'Dipanggil';

    if (! $isEmpty) {
        $serviceCode = strtoupper($call['service_code'] ?? '');
        $ticketNumber = $call['ticket_number'] ?? '';
        $numericPart = $serviceCode !== '' && str_starts_with(strtoupper($ticketNumber), $serviceCode)
            ? substr($ticketNumber, strlen($serviceCode))
            : preg_replace('/^[A-Z]+/i', '', $ticketNumber);
        $displayTicket = $serviceCode.'-'.$numericPart;
        $calledTime = filled($call['called_at'] ?? null)
            ? \Illuminate\Support\Carbon::parse($call['called_at'])->format('H:i')
            : '--:--';
    }
@endphp

<article
    @if (! $isEmpty)
        data-recent-ticket="{{ $call['ticket_number'] }}"
    @endif
    @class([
        'display-tv-recent-card',
        'display-tv-recent-card--idle' => $isEmpty,
    ])
    wire:key="recent-call-{{ $isEmpty ? 'empty' : $call['ticket_number'] }}-{{ $isEmpty ? $index : ($call['called_at'] ?? $index) }}"
>
    @if ($isEmpty)
        <span class="display-tv-recent-index">{{ $index + 1 }}</span>
        <span class="display-tv-recent-ticket display-tv-recent-ticket--idle" aria-hidden="true">---</span>
        <span class="display-tv-recent-service display-tv-recent-service--idle">Belum ada riwayat</span>
        <span class="display-tv-recent-time">--:--</span>
        <span class="sr-only">Belum ada panggilan sebelumnya</span>
    @else
        <span class="display-tv-recent-index">{{ $index + 1 }}</span>
        <span class="display-tv-recent-ticket">{{ $displayTicket }}</span>
        <span class="display-tv-recent-service">{{ $call['service_name'] }}</span>
        <span class="display-tv-recent-time">{{ $calledTime }}</span>
        <span class="sr-only">{{ $statusLabel }} di {{ $call['counter_name'] }}</span>
    @endif
</article>
