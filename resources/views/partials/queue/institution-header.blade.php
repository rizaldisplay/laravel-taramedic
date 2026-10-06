@php
    use App\Services\SettingsService;
    $settings = app(SettingsService::class);
    $institutionName = $settings->institutionName();
    $institutionTagline = $settings->institutionTagline();
    $institutionLogoUrl = $settings->institutionLogoUrl();
@endphp

<header class="queue-institution-header {{ $class ?? '' }}">
    <div class="flex min-w-0 items-center gap-4">
        <img src="{{ $institutionLogoUrl ?: asset('images/logo/logo-icon.svg') }}" alt="" class="h-10 w-auto object-contain md:h-12" aria-hidden="true">
        <div class="min-w-0">
            <p class="truncate text-lg font-bold text-white md:text-xl">{{ $institutionName }}</p>
            <p class="truncate text-sm text-white/60">{{ $institutionTagline }}</p>
        </div>
    </div>

    <div class="flex shrink-0 items-center gap-3">
        @if ($showPrinterStatus ?? false)
            @include('partials.queue.kiosk-printer-status')
        @endif

        @if ($showClock ?? true)
            <div class="text-right" x-data="{ time: '' }" x-init="const tick = () => time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }); tick(); setInterval(tick, 1000)">
                <p class="text-2xl font-semibold tabular-nums text-white md:text-3xl" x-text="time"></p>
                <p class="text-xs text-white/50">{{ now()->locale('id')->translatedFormat('d M Y') }}</p>
            </div>
        @endif
    </div>
</header>
