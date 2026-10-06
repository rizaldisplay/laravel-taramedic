@props([
    'title',
    'description' => null,
    'closeAction' => 'cancelEdit',
    'size' => 'lg',
])

@php
    $sizeClass = match ($size) {
        'xl' => 'max-w-4xl',
        'sm' => 'max-w-lg',
        default => 'max-w-2xl',
    };
@endphp

<div
    class="admin-modal-root"
    role="dialog"
    aria-modal="true"
    aria-labelledby="admin-modal-title"
    @keydown.escape.window="$wire.{{ $closeAction }}()"
>
    <button
        type="button"
        class="admin-modal-backdrop"
        wire:click="{{ $closeAction }}"
        aria-label="Tutup dialog"
    ></button>

    <section class="admin-modal-panel {{ $sizeClass }}" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())">
        <header class="admin-modal-header">
            <div class="min-w-0">
                <p class="admin-modal-eyebrow">Form data</p>
                <h2 id="admin-modal-title" class="admin-modal-title">{{ $title }}</h2>
                @if ($description)
                    <p class="admin-modal-description">{{ $description }}</p>
                @endif
            </div>
            <button type="button" wire:click="{{ $closeAction }}" class="admin-modal-close" aria-label="Tutup dialog">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </header>

        <div class="admin-modal-body">
            {{ $slot }}
        </div>

        @isset($footer)
            <footer class="admin-modal-footer">
                {{ $footer }}
            </footer>
        @endisset
    </section>
</div>
