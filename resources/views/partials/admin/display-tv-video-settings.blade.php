<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Video Informasi</h2>
            <p class="text-sm text-gray-500">Ditampilkan sebagai area utama pada layar ruang tunggu</p>
        </div>
        <label class="display-tv-toggle">
            <input type="checkbox" wire:model.live="video_enabled" class="display-tv-toggle-input">
            <span class="display-tv-toggle-track"></span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Aktif</span>
        </label>
    </div>

    @if ($video_enabled)
        <div class="admin-card-body space-y-6">
            <div class="grid gap-3 sm:grid-cols-2">
                <button
                    type="button"
                    wire:click="$set('video_source', 'upload')"
                    @class([
                        'display-tv-source-card',
                        'display-tv-source-card--active' => $video_source === 'upload',
                    ])
                >
                    <span class="display-tv-source-icon display-tv-source-icon--upload">
                        @include('partials.icons.film', ['class' => 'h-6 w-6'])
                    </span>
                    <span class="block text-left">
                        <span class="block font-semibold text-gray-900 dark:text-white">Upload Video</span>
                        <span class="mt-0.5 block text-xs text-gray-500">Beberapa file MP4/WebM — putar otomatis bergantian</span>
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="$set('video_source', 'youtube')"
                    @class([
                        'display-tv-source-card',
                        'display-tv-source-card--active' => in_array($video_source, ['youtube', 'url'], true),
                    ])
                >
                    <span class="display-tv-source-icon display-tv-source-icon--youtube">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2 31.5 31.5 0 0 0 0 12a31.5 31.5 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1A31.5 31.5 0 0 0 24 12a31.5 31.5 0 0 0-.5-5.8ZM9.75 15.02V8.98L15.5 12l-5.75 3.02Z"/>
                        </svg>
                    </span>
                    <span class="block text-left">
                        <span class="block font-semibold text-gray-900 dark:text-white">YouTube Playlist</span>
                        <span class="mt-0.5 block text-xs text-gray-500">URL embed playlist — autoplay via YouTube</span>
                    </span>
                </button>
            </div>

            @if ($video_source === 'upload')
                <div class="space-y-4">
                    <div class="display-tv-upload-zone">
                        @include('partials.admin.video-upload-field', [
                            'maxMb' => $maxVideoUploadMb,
                            'label' => 'Tambah Video ke Daftar Putar',
                        ])
                        <p class="mt-2 text-xs text-gray-500">
                            Pilih file satu per satu — unggahan otomatis. Maks. {{ $maxVideoUploadMb }} MB per file.
                            Video diputar bergantian dengan autoplay.
                        </p>
                    </div>

                    @if ($video_media_items !== [])
                        <div>
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    Daftar Putar ({{ count($video_media_items) }} video)
                                </h3>
                                <span class="text-xs text-gray-500">Urutan = urutan putar</span>
                            </div>

                            <div class="space-y-2">
                                @foreach ($video_media_items as $index => $item)
                                    <div
                                        class="display-tv-video-item"
                                        wire:key="display-tv-video-{{ $item['id'] }}"
                                    >
                                        <div class="flex min-w-0 flex-1 items-center gap-3">
                                            <span class="display-tv-video-order">{{ $index + 1 }}</span>
                                            <div class="min-w-0">
                                                <p class="truncate font-medium text-gray-900 dark:text-white">{{ $item['name'] }}</p>
                                                <p class="text-xs text-gray-500">
                                                    @if ($item['size'] > 0)
                                                        {{ number_format($item['size'] / 1048576, 1) }} MB
                                                    @endif
                                                    @if (! empty($item['url']))
                                                        ·
                                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline">Pratinjau</a>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="removeVideo({{ $item['id'] }})"
                                            wire:confirm="Hapus video ini dari daftar putar?"
                                            class="display-tv-video-remove"
                                        >
                                            @include('partials.icons.x-mark', ['class' => 'h-4 w-4'])
                                            Hapus
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="display-tv-empty-playlist">
                            @include('partials.icons.film', ['class' => 'h-8 w-8 text-gray-400'])
                            <p class="mt-2 text-sm font-medium text-gray-600 dark:text-gray-300">Belum ada video</p>
                            <p class="text-xs text-gray-500">Unggah minimal satu video untuk ditampilkan di Display TV</p>
                        </div>
                    @endif

                </div>
            @else
                <div class="space-y-4">
                    <div>
                        <label class="admin-form-label">URL Embed Playlist YouTube</label>
                        <input
                            wire:model="video_url"
                            class="admin-form-input font-mono text-xs sm:text-sm"
                            placeholder="https://www.youtube.com/embed/videoseries?list=PLxxxxxxxx"
                        >
                        @error('video_url') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                        <p class="font-semibold">Cara mendapatkan URL embed playlist:</p>
                        <ol class="mt-2 list-decimal space-y-1 pl-5 text-xs sm:text-sm">
                            <li>Buka playlist YouTube → Bagikan → Sematkan</li>
                            <li>Salin URL yang berbentuk <code class="rounded bg-white/60 px-1 dark:bg-black/20">youtube.com/embed/videoseries?list=PL...</code></li>
                            <li>Tempel di kolom di atas — autoplay diatur otomatis</li>
                        </ol>
                    </div>

                </div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-900/40">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Keseimbangan Suara</h3>
                        <p class="mt-1 text-xs leading-relaxed text-gray-500">
                            Video tetap terdengar, lalu volumenya turun otomatis saat nomor antrian dipanggil.
                        </p>
                    </div>
                    <label class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <input type="checkbox" wire:model.live="video_muted" class="rounded text-brand-500">
                        Bisukan video
                    </label>
                </div>

                @if (! $video_muted)
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <label class="block">
                            <span class="flex items-center justify-between gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Volume normal video
                                <span class="font-semibold text-brand-600 dark:text-brand-400">{{ $video_volume }}%</span>
                            </span>
                            <input
                                type="range"
                                min="0"
                                max="100"
                                step="1"
                                wire:model.live.debounce.150ms="video_volume"
                                class="mt-3 w-full accent-brand-600"
                            >
                            @error('video_volume') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                        </label>

                        <label class="block">
                            <span class="flex items-center justify-between gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Volume saat panggilan
                                <span class="font-semibold text-brand-600 dark:text-brand-400">{{ $video_duck_volume }}%</span>
                            </span>
                            <input
                                type="range"
                                min="0"
                                max="{{ $video_volume }}"
                                step="1"
                                wire:model.live.debounce.150ms="video_duck_volume"
                                class="mt-3 w-full accent-brand-600"
                            >
                            @error('video_duck_volume') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                        </label>
                    </div>

                    <p class="mt-4 text-xs text-gray-500">
                        Rekomendasi ruang tunggu: volume normal 20–30%, saat panggilan 3–5%.
                    </p>
                @endif
            </div>
        </div>
    @endif
</div>
