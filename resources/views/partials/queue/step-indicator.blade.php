@props(['current' => 1])

@php
    $steps = [
        1 => 'Pilih',
        2 => 'Konfirmasi',
        3 => 'Tiket',
    ];
@endphp

<nav aria-label="Langkah pengambilan nomor" class="kiosk-no-print mb-6 flex items-center justify-center gap-2 md:mb-8 md:gap-3">
    @foreach ($steps as $number => $label)
        @if ($number > 1)
            <div @class(['queue-step-line max-w-10 md:max-w-16', 'queue-step-line-done' => $current > $number - 1])></div>
        @endif
        <div class="flex flex-col items-center gap-1">
            <div @class([
                'queue-step-dot',
                'queue-step-dot-active' => $current === $number,
                'queue-step-dot-done' => $current > $number,
                'queue-step-dot-pending' => $current < $number,
            ])>
                @if ($current > $number)
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                @else
                    {{ $number }}
                @endif
            </div>
            <span @class(['text-[11px] font-medium uppercase tracking-wide md:text-xs', $current === $number ? 'text-white' : 'text-white/45'])>{{ $label }}</span>
        </div>
    @endforeach
</nav>
