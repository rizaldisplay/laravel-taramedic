@props([
    'institutionName',
    'institutionTagline' => 'Pelayanan Ramah, Kesehatan Anda, Prioritas Kami',
    'institutionLogoUrl' => null,
])

<header {{ $attributes->merge(['class' => 'kiosk-reference-header']) }}>
    <div class="kiosk-header-brand">
        <span class="kiosk-brand-mark" aria-hidden="true">
            @if ($institutionLogoUrl)
                <img src="{{ $institutionLogoUrl }}" alt="">
            @else
                <svg viewBox="0 0 76 76" fill="none">
                    <rect x="28" y="2" width="20" height="40" rx="6" fill="#08275f"/>
                    <rect x="7" y="22" width="62" height="23" rx="7" fill="#1458bd"/>
                    <circle cx="27" cy="34" r="5" fill="white"/>
                    <circle cx="49" cy="34" r="5" fill="white"/>
                    <path d="M19 51c0-8 5-13 11-13 4 0 7 2 8 5 2-3 5-5 9-5 6 0 11 5 11 13v13H19V51Z" fill="white"/>
                    <circle cx="38" cy="48" r="7" fill="#54b85d"/>
                    <path d="M18 63c0-8 5-14 12-14 4 0 7 2 8 5 2-3 5-5 9-5 7 0 12 6 12 14v9H18v-9Z" fill="#54b85d"/>
                </svg>
            @endif
        </span>
        <strong>{{ $institutionName }}</strong>
    </div>

    <div class="kiosk-header-side">
        <div class="kiosk-header-tagline">
            <svg viewBox="0 0 48 56" fill="none" aria-hidden="true"><path d="M24 2 44 10v15c0 13-8 23-20 29C12 48 4 38 4 25V10L24 2Z" stroke="currentColor" stroke-width="2.2"/><path d="M24 14v22M13 25h22" stroke="#38a852" stroke-width="5"/></svg>
            <span>{{ $institutionTagline ?: 'Pelayanan Ramah, Kesehatan Anda, Prioritas Kami' }}</span>
        </div>
        @include('partials.queue.kiosk-printer-status')
    </div>
</header>
