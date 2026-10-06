@php
    $call = $call ?? null;

    if ($call) {
        $serviceCode = strtoupper($call['service_code'] ?? '');
        $ticketNumber = $call['ticket_number'] ?? '';
        $numericPart = $serviceCode !== '' && str_starts_with(strtoupper($ticketNumber), $serviceCode)
            ? substr($ticketNumber, strlen($serviceCode))
            : preg_replace('/^[A-Z]+/i', '', $ticketNumber);
    } else {
        $serviceCode = '-';
        $numericPart = '---';
    }
@endphp

<div
    class="display-tv-now-call display-tv-now-call--active"
    x-show="currentCall"
    @if ($call === null) x-cloak @endif
>
    <p class="display-tv-now-service" x-text="currentCall?.service_name ?? ''">
        {{ $call['service_name'] ?? '' }}
    </p>

    <p
        class="display-tv-now-ticket"
        :aria-label="`Nomor ${currentCall?.ticket_number ?? ''}`"
    >
        <span class="display-tv-now-code" x-text="ticketCode()">{{ $serviceCode }}</span>
        <span class="display-tv-now-separator" aria-hidden="true">-</span>
        <span class="display-tv-now-number" x-text="ticketNumber()">{{ $numericPart }}</span>
    </p>

    <div class="display-tv-now-counter">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0H4Z"/>
        </svg>
        <p class="display-tv-now-counter-value" x-text="currentCall?.counter_name ?? ''">
            {{ $call['counter_name'] ?? '' }}
        </p>
    </div>
</div>

<div
    class="display-tv-now-call display-tv-now-call--idle"
    x-show="! currentCall"
    @if ($call !== null) x-cloak @endif
>
    <p class="display-tv-now-service">Belum ada panggilan</p>
    <p class="display-tv-now-ticket display-tv-now-ticket--idle" aria-hidden="true">---</p>

    <p class="display-tv-now-idle-hint">Menunggu panggilan</p>
</div>
