<?php

use App\Services\DisplayTvSettingsService;
use App\Services\AnnouncementSettingsService;
use App\Services\QueueManager;
use App\Services\SettingsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('layouts.display')] class extends Component
{
    public array $board = [];

    public function mount(): void
    {
        $this->refreshBoard();
    }

    /**
     * Echo realtime + local Livewire events. Do not type-hint services as
     * event params — JS/Echo payloads would replace the dependency and break refresh.
     */
    #[On('queue-updated')]
    public function refreshBoard(): void
    {
        $queue = app(QueueManager::class);
        $this->board = $queue->displayBoard()->toArray();
        $this->dispatch('display-board-synced', call: $this->board['now_serving'][0] ?? null);
    }

    public function with(): array
    {
        $runningTexts = collect($this->board['signage_contents'] ?? [])
            ->where('type', 'running_text')
            ->flatMap(fn (array $item): array => preg_split('/\R+/', $item['body'] ?? '') ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values();

        $nowServing = collect($this->board['now_serving'] ?? [])->values();
        $recentCalls = collect($this->board['recent_calls'] ?? [])->values();
        $videoContents = collect($this->board['signage_contents'] ?? [])
            ->where('type', 'video')
            ->values();

        if ($runningTexts->isEmpty()) {
            $runningTexts = collect([
                'Jaga kesehatan, jaga masa depan.',
                'Periksa kesehatan secara rutin, hidup lebih berkualitas.',
                'Terima kasih telah mempercayakan kesehatan Anda kepada kami.',
            ]);
        }

        return [
            'nowServing' => $nowServing->all(),
            'primaryCall' => $nowServing->first(),
            'recentCalls' => $recentCalls->all(),
            'recentLimit' => app(SettingsService::class)->getInt(
                'display_recent_limit',
                (int) config('queue_system.display_recent_limit', 4),
            ),
            'video' => $videoContents->first(),
            'videoMuted' => app(DisplayTvSettingsService::class)->videoMuted(),
            'videoVolume' => app(DisplayTvSettingsService::class)->videoVolume(),
            'videoDuckVolume' => app(DisplayTvSettingsService::class)->videoDuckVolume(),
            'announcementSettings' => app(AnnouncementSettingsService::class)->frontendConfig(),
            'runningTexts' => $runningTexts,
            'runningTextSignature' => hash('xxh3', $runningTexts->implode("\0")),
        ];
    }
};
?>

<div
    wire:poll.60s="refreshBoard"
    data-queue-realtime="display"
    data-announcement-settings="{{ json_encode($announcementSettings) }}"
    class="display-tv-layout"
    x-data="displayTvShell()"
>
    @include('partials.queue.display-tv-header')

    <main class="display-tv-main display-tv-main--video-dominant">
        <div class="display-tv-health-column">
            @php
                $mediaUrls = $video['media_urls'] ?? [];

                if ($mediaUrls === [] && ! empty($video['media_url'])) {
                    $mediaUrls = [$video['media_url']];
                }

                $hasVideo = $video !== null
                    && (! empty($video['external_url']) || $mediaUrls !== []);
            @endphp

            @if ($hasVideo)
                <section
                    class="display-tv-ad-panel display-tv-video"
                    aria-label="Video iklan dan informasi"
                    @if ($videoMuted) data-prefer-muted="1" @endif
                    data-video-volume="{{ $videoVolume }}"
                    data-video-duck-volume="{{ $videoDuckVolume }}"
                >
                    @include('partials.queue.display-tv-video-player', [
                        'preferMuted' => $videoMuted,
                        'videoVolume' => $videoVolume,
                        'externalUrl' => $video['external_url'] ?? null,
                        'mediaUrls' => $mediaUrls,
                        'title' => $video['title'] ?? 'Video iklan dan informasi',
                    ])
                </section>
            @else
                @include('partials.queue.display-health-poster')
            @endif

            @include('partials.queue.display-health-tips')
        </div>

        <aside class="display-tv-queue-column" aria-label="Informasi antrean">
            <section class="display-tv-called" aria-label="Nomor dipanggil">
                @include('partials.queue.display-now-serving', [
                    'call' => $primaryCall,
                ])
            </section>

            @include('partials.queue.display-recent-calls', [
                'recentCalls' => $recentCalls,
                'recentLimit' => $recentLimit,
            ])
        </aside>
    </main>

    <div
        class="display-tv-ticker-shell"
        wire:key="display-tv-ticker-{{ $runningTextSignature }}"
    >
        <div
            class="display-tv-ticker"
            aria-live="polite"
            wire:ignore
            x-data="displayTvTicker()"
        >
            <span class="display-tv-ticker-label">Info Kesehatan</span>
            <div class="display-tv-ticker-window">
                <div class="display-tv-ticker-track">
                    @foreach ([false, true] as $isDuplicate)
                        <div
                            class="display-tv-ticker-content"
                            @if ($isDuplicate) aria-hidden="true" @endif
                        >
                            @foreach ($runningTexts as $index => $text)
                                <span
                                    class="display-tv-ticker-item"
                                    wire:key="ticker-{{ $isDuplicate ? 'duplicate' : 'primary' }}-{{ $index }}"
                                >{{ $text }}</span>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <button
        type="button"
        class="display-tv-audio-unlock"
        x-show="! unlocked"
        x-cloak
        @click="unlock()"
    >
        Aktifkan suara TV
    </button>
</div>
