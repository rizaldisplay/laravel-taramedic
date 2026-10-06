@props(['title'])

<header {{ $attributes->merge(['class' => 'kiosk-reference-header kiosk-process-header']) }}>
    <h1>{{ $title }}</h1>
    <div class="kiosk-process-printer">
        @include('partials.queue.kiosk-printer-status')
    </div>
</header>
