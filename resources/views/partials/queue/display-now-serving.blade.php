@php
    $call = $call ?? null;
@endphp

<div
    class="display-tv-now-serving"
    :class="{ 'display-tv-now-serving--idle': ! currentCall }"
    x-data="displayTvNowServing(@js($call))"
    wire:ignore
    @display-announcement-started.window="showCall($event.detail)"
    @display-board-synced.window="syncCall($event.detail.call)"
    @display-announcement-queue-idle.window="finishQueue()"
    aria-live="assertive"
>
    <div class="display-tv-now-banner">
        <span x-text="currentCall ? 'Sedang Dipanggil' : 'Nomor Panggilan'">
            {{ $call ? 'Sedang Dipanggil' : 'Nomor Panggilan' }}
        </span>
    </div>

    <div class="display-tv-now-body">
        @include('partials.queue.display-now-call-card', ['call' => $call])
    </div>
</div>
