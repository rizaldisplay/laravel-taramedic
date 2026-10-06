@php
    $ticketNumber = $ticketNumber ?? null;
    $serviceCode = strtoupper($serviceCode ?? '');
    $size = $size ?? 'lg';
    $empty = blank($ticketNumber);

    if (! $empty && $serviceCode !== '') {
        $numericPart = str_starts_with(strtoupper($ticketNumber), $serviceCode)
            ? substr($ticketNumber, strlen($serviceCode))
            : preg_replace('/^[A-Z]+/i', '', $ticketNumber);
    } else {
        $numericPart = '---';
    }
@endphp

<div @class([
    'display-ticket-split',
    'display-ticket-split-lg' => $size === 'lg',
    'display-ticket-split-sm' => $size === 'sm',
    'display-ticket-split-tv' => $size === 'tv',
])>
    <div class="display-ticket-split-code">
        {{ $empty ? '-' : $serviceCode }}
    </div>
    <div class="display-ticket-split-number">
        {{ $empty ? '---' : $numericPart }}
    </div>
</div>
