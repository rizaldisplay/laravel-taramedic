@php
    $title = $title ?? '';
    $subtitle = $subtitle ?? '';
@endphp

<div class="min-w-0">
    <div class="mb-2 flex items-center gap-2 text-[10px] font-bold tracking-[.15em] text-brand-700 uppercase dark:text-brand-300">
        <span class="h-px w-5 bg-brand-400"></span>
        Pusat kendali klinik
    </div>
    <h1 class="admin-page-title">{{ $title }}</h1>
    @if (! empty($subtitle))
        <p class="admin-page-subtitle">{{ $subtitle }}</p>
    @endif
</div>
