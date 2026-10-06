@php
    use App\Services\SettingsService;
    $settings = app(SettingsService::class);
    $institutionName = $settings->institutionName();
    $institutionTagline = $settings->institutionTagline();
    $institutionLogoUrl = $settings->institutionLogoUrl();
@endphp

<header class="display-tv-header shrink-0">
    <div class="display-tv-header-brand">
        <img
            src="{{ $institutionLogoUrl ?: asset('images/logo/logo-icon.svg') }}"
            alt=""
            class="display-tv-header-logo"
            aria-hidden="true"
        >
        <div class="display-tv-header-name">
            <p class="display-tv-header-institution">{{ $institutionName }}</p>
        </div>
        <span class="display-tv-header-divider" aria-hidden="true"></span>
        <p class="display-tv-header-tagline">{{ $institutionTagline }}</p>
    </div>

    <div
        class="display-tv-header-clock"
        x-data="{
            date: '',
            time: '',
            tick() {
                const now = new Date();
                this.date = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                this.time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            },
        }"
        x-init="tick(); setInterval(() => tick(), 1000)"
    >
        <p class="display-tv-header-date" x-text="date"></p>
        <span class="display-tv-header-clock-divider" aria-hidden="true"></span>
        <p class="display-tv-header-time" x-text="time"></p>
    </div>
</header>
