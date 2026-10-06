@php
    $preferMuted = $preferMuted ?? false;
    $mediaUrl = $mediaUrl ?? null;
    $mediaUrls = $mediaUrls ?? ($mediaUrl ? [$mediaUrl] : []);
    $externalUrl = $externalUrl ?? null;
    $title = $title ?? 'Video informasi';
    $videoVolume = $videoVolume ?? 25;
    $playlist = collect($mediaUrls)->filter()->values()->all();
@endphp

@if ($externalUrl)
    <iframe
        src="{{ \App\Support\DisplayVideoUrl::embedUrl($externalUrl, $preferMuted) }}"
        title="{{ $title }}"
        class="display-tv-video-media"
        allow="autoplay; fullscreen; encrypted-media"
        allowfullscreen
        loading="lazy"
        data-prefer-muted="{{ $preferMuted ? '1' : '0' }}"
        data-video-volume="{{ $videoVolume }}"
    ></iframe>
@elseif ($playlist !== [])
    <div
        class="display-tv-video-player"
        x-data="displayTvVideo(@js($preferMuted), @js($playlist), @js($videoVolume))"
        @display-video-sound-enabled.window="needsInteraction = false"
        data-prefer-muted="{{ $preferMuted ? '1' : '0' }}"
        wire:key="display-video-playlist-{{ md5(implode('|', $playlist)) }}"
    >
        <video
            class="display-tv-video-media"
            playsinline
            x-ref="video"
            x-init="boot()"
            @ended="onEnded()"
            @click="tryUnmute()"
        ></video>

        @if (count($playlist) > 1)
            <div class="pointer-events-none absolute inset-x-0 top-0 flex justify-end p-3">
                <span
                    class="rounded-full bg-black/55 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm"
                    x-text="`${currentIndex + 1} / ${mediaUrls.length}`"
                ></span>
            </div>
        @endif

        <button
            type="button"
            x-show="needsInteraction"
            x-cloak
            @click="tryUnmute()"
            class="absolute inset-x-4 bottom-4 rounded-xl bg-black/70 px-4 py-3 text-center text-sm font-medium text-white backdrop-blur-sm transition hover:bg-black/80"
        >
            Sentuh layar untuk mengaktifkan suara video
        </button>
    </div>
@endif
