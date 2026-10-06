@props([
    'title' => 'Panduan singkat',
    'description' => null,
    'items' => [],
])

<aside class="admin-guide" aria-label="{{ $title }}">
    <div class="admin-guide-icon" aria-hidden="true">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M9.172 9a3 3 0 115.656 0c0 2-2.828 2-2.828 4M12 3a9 9 0 100 18 9 9 0 000-18z"/>
        </svg>
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex flex-col gap-1 lg:flex-row lg:items-baseline lg:justify-between lg:gap-5">
            <h2 class="admin-guide-title">{{ $title }}</h2>
            @if ($description)
                <p class="admin-guide-description">{{ $description }}</p>
            @endif
        </div>
        @if ($items !== [])
            <ol class="admin-guide-steps">
                @foreach ($items as $item)
                    <li wire:key="guide-{{ $loop->index }}">
                        <span>{{ $loop->iteration }}</span>
                        <p>{{ $item }}</p>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</aside>
