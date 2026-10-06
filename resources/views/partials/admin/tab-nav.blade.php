@php
    $tabs = $tabs ?? [];
    $active = $active ?? '';
@endphp

<nav class="admin-tab-nav" aria-label="Tab navigasi">
    @foreach ($tabs as $key => $tab)
        <button
            wire:key="settings-tab-{{ $key }}"
            type="button"
            wire:click="$set('tab', '{{ $key }}')"
            @class([
                'admin-tab-btn',
                'admin-tab-btn-active' => $active === $key,
                'admin-tab-btn-inactive' => $active !== $key,
            ])
        >
            @if (! empty($tab['icon']))
                @include('partials.admin.icons.'.$tab['icon'], [
                    'active' => $active === $key,
                    'class' => 'h-4 w-4 shrink-0 ' . ($active === $key ? 'text-white' : 'text-gray-500 dark:text-gray-400'),
                ])
            @endif
            {{ $tab['label'] }}
        </button>
    @endforeach
</nav>
