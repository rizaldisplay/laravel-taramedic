@php
    $recentCalls = $recentCalls ?? [];
    $recentLimit = $recentLimit ?? (int) config('queue_system.display_recent_limit', 4);
    $slots = collect($recentCalls)->take($recentLimit)->pad($recentLimit, null)->values();
@endphp

<section
    class="display-tv-recent"
    aria-label="Panggilan sebelumnya"
    x-data="displayTvRecentCalls()"
>
    <div class="display-tv-recent-heading">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>
        </svg>
        <span>Riwayat Antrian Terakhir</span>
    </div>

    <div class="display-tv-recent-flow">
        @foreach ($slots as $index => $item)
            @include('partials.queue.display-recent-call-card', [
                'call' => $item,
                'index' => $index,
            ])
        @endforeach
    </div>
</section>
