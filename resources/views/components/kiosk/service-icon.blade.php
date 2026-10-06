@props(['name', 'code', 'index' => 0])

@php
    $normalizedName = str($name)->lower();
    $icon = match (true) {
        $normalizedName->contains('gigi') => 'tooth',
        $normalizedName->contains(['laboratorium', 'lab']) => 'lab',
        $normalizedName->contains(['kia', 'anak', 'ibu']) => 'family',
        default => 'stethoscope',
    };
@endphp

<span {{ $attributes->merge(['class' => 'kiosk-service-icon is-theme-'.($index % 4)]) }} aria-hidden="true">
    @if ($icon === 'tooth')
        <svg viewBox="0 0 64 64" fill="none"><path d="M16 8c-8 4-9 15-5 24 3 7 5 25 11 25 5 0 4-17 10-17s5 17 10 17c6 0 8-18 11-25 4-9 3-20-5-24-7-4-11 2-16 2S23 4 16 8Z" stroke="currentColor" stroke-width="4" stroke-linejoin="round"/></svg>
    @elseif ($icon === 'lab')
        <svg viewBox="0 0 64 64" fill="none"><path d="M24 7h16M27 7v19L14 50a5 5 0 0 0 5 7h26a5 5 0 0 0 5-7L37 26V7" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 44h22M28 36h.01M37 47h.01" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg>
    @elseif ($icon === 'family')
        <svg viewBox="0 0 64 64" fill="none"><path d="M32 54S9 42 9 23c0-9 11-15 18-7l5 5 5-5c7-8 18-2 18 7 0 19-23 31-23 31Z" stroke="currentColor" stroke-width="4" stroke-linejoin="round"/><circle cx="32" cy="29" r="5" stroke="currentColor" stroke-width="3"/><path d="M23 42c2-5 5-8 9-8s7 3 9 8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
    @else
        <svg viewBox="0 0 64 64" fill="none"><path d="M18 7v16a14 14 0 0 0 28 0V7M13 7h10M41 7h10" stroke="currentColor" stroke-width="4" stroke-linecap="round"/><path d="M32 37v5a11 11 0 0 0 22 0v-4" stroke="currentColor" stroke-width="4" stroke-linecap="round"/><circle cx="54" cy="32" r="6" stroke="currentColor" stroke-width="4"/></svg>
    @endif
</span>
