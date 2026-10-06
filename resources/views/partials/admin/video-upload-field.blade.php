@php
    $model = $model ?? 'video_upload';
    $maxMb = $maxMb ?? \App\Support\UploadLimits::displayTvVideoMaxMegabytes();
@endphp

<div
    x-data="{
        property: @js($model),
        uploading: false,
        persisting: false,
        progress: 0,
        error: null,
        fileName: null,
        isTarget(property) {
            return property === this.property;
        },
    }"
    x-on:livewire-upload-start.window="if (isTarget($event.detail.property)) { uploading = true; persisting = false; progress = 0; error = null; fileName = $event.detail.fileName ?? null; }"
    x-on:livewire-upload-progress.window="if (isTarget($event.detail.property)) { progress = $event.detail.progress; }"
    x-on:livewire-upload-error.window="if (isTarget($event.detail.property)) { uploading = false; persisting = false; progress = 0; error = 'Unggahan gagal. Ukuran maksimal {{ $maxMb }} MB dan format MP4/WebM/MOV.'; }"
    x-on:livewire-upload-finish.window="if (isTarget($event.detail.property)) { uploading = false; progress = 100; persisting = true; }"
    x-on:display-tv-video-saved.window="persisting = false; progress = 100; error = null;"
    x-on:display-tv-video-failed.window="persisting = false; uploading = false; error = $event.detail?.message ?? 'Gagal menyimpan video.';"
    class="space-y-3"
>
    <div>
        <label class="admin-form-label">{{ $label ?? 'File Video (MP4 / WebM / MOV)' }}</label>
        <input
            type="file"
            wire:model="{{ $model }}"
            class="admin-form-input"
            accept=".mp4,.webm,.mov,.avi,.mkv,.m4v,video/*"
            x-bind:disabled="uploading || persisting"
        >
    </div>

    <div x-show="uploading || persisting" x-cloak class="rounded-xl border border-brand-200 bg-brand-50/60 p-4 dark:border-brand-500/30 dark:bg-brand-500/10">
        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
            <span class="font-medium text-brand-700 dark:text-brand-300" x-text="uploading ? 'Mengunggah ke server...' : 'Menyimpan video...'"></span>
            <span class="tabular-nums text-brand-600 dark:text-brand-400" x-show="uploading" x-text="`${Math.round(progress)}%`"></span>
        </div>
        <div class="h-2.5 overflow-hidden rounded-full bg-brand-100 dark:bg-brand-950">
            <div
                class="h-full rounded-full bg-brand-500 transition-all duration-300 ease-out"
                :style="`width: ${uploading ? progress : 100}%`"
            ></div>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-show="fileName" x-text="fileName ? `File: ${fileName}` : ''"></p>
    </div>

    <div x-show="error" x-cloak class="rounded-lg border border-error-200 bg-error-50 px-3 py-2 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-300" x-text="error"></div>

    @error($model)
        <p class="text-sm text-error-500">{{ $message }}</p>
    @enderror
</div>
