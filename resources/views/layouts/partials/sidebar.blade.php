@php
    use App\Services\SettingsService;

    $settings = app(SettingsService::class);
    $institutionName = $settings->institutionName();
    $institutionLogoUrl = $settings->institutionLogoUrl();
    $user = auth()->user();
    $initials = $user
        ? collect(explode(' ', $user->name))->filter()->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))->take(2)->join('')
        : '';
    $homeRoute = $user?->can('access-admin') ? route('admin.dashboard') : route('operator.panel');

    $mainMenu = [
        ['route' => 'admin.dashboard', 'label' => 'Ringkasan', 'icon' => 'dashboard', 'can' => 'access-admin'],
        ['route' => 'operator.panel', 'label' => 'Ruang Operator', 'icon' => 'operator', 'can' => 'access-operator'],
    ];

    $adminMenu = [
        ['route' => 'admin.reports', 'label' => 'Laporan', 'icon' => 'queue'],
        ['route' => 'admin.users', 'label' => 'Tim & Akses', 'icon' => 'operator'],
        ['route' => 'admin.services', 'label' => 'Layanan', 'icon' => 'services'],
        ['route' => 'admin.counters', 'label' => 'Loket', 'icon' => 'counters'],
        ['route' => 'admin.display-tv', 'label' => 'Konten Display', 'icon' => 'display'],
        ['route' => 'admin.settings', 'label' => 'Konfigurasi', 'icon' => 'settings'],
    ];
@endphp

<aside
    :class="[
        sidebarMobileOpen ? 'translate-x-0' : '-translate-x-full',
        sidebarCollapsed ? 'xl:w-[84px]' : 'xl:w-[272px]',
    ]"
    class="admin-sidebar fixed top-0 left-0 z-9999 flex h-screen w-[272px] flex-col shadow-2xl transition-[width,transform] duration-300 xl:static xl:translate-x-0 xl:shadow-none"
    @keydown.escape.window="closeMobileSidebar()"
>
    <div class="flex h-20 shrink-0 items-center gap-3 px-4" :class="sidebarCollapsed ? 'xl:justify-center' : ''">
        <a href="{{ $homeRoute }}" class="flex min-w-0 items-center gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-400 text-ink-950 shadow-lg shadow-black/20">
                @if ($institutionLogoUrl)
                    <img src="{{ $institutionLogoUrl }}" alt="" class="max-h-9 max-w-9 object-contain">
                @else
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.25 3.5h5.5v5.75h5.75v5.5h-5.75v5.75h-5.5v-5.75H3.5v-5.5h5.75V3.5Z"/></svg>
                @endif
            </span>
            <span class="min-w-0" :class="sidebarCollapsed ? 'xl:hidden' : ''">
                <span class="block font-display text-base font-bold tracking-[-.03em] text-ink-950 dark:text-white">{{ config('app.name') }}</span>
                <span class="block truncate text-[10px] font-bold tracking-[.14em] text-brand-700 uppercase dark:text-brand-300">{{ $institutionName }}</span>
            </span>
        </a>

        <button type="button" class="ml-auto flex h-9 w-9 items-center justify-center rounded-xl text-ink-500 hover:bg-brand-50 hover:text-brand-700 xl:hidden dark:text-white/50 dark:hover:bg-white/8 dark:hover:text-white" @click="closeMobileSidebar()" aria-label="Tutup navigasi">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="mx-4 h-px bg-ink-200/80 dark:bg-white/8"></div>

    <div class="no-scrollbar flex flex-1 flex-col overflow-y-auto px-3 py-5">
        <nav class="space-y-7" aria-label="Navigasi utama">
            <div>
                <p class="mb-2 px-3 text-[10px] font-bold tracking-[.16em] text-ink-500 uppercase dark:text-ink-400" :class="sidebarCollapsed ? 'xl:hidden' : ''">Operasional</p>
                <ul class="space-y-1">
                    @foreach ($mainMenu as $item)
                        @if ($item['can'] && ! $user?->can($item['can']))
                            @continue
                        @endif
                        @php $isActive = request()->routeIs($item['route']); @endphp
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                @if (! empty($item['external'])) target="_blank" rel="noopener" @endif
                                @class(['menu-item', 'menu-item-active' => $isActive, 'menu-item-inactive' => ! $isActive])
                                :class="sidebarCollapsed ? 'xl:justify-center xl:px-2' : ''"
                                :title="sidebarCollapsed ? '{{ $item['label'] }}' : null"
                                @click="closeMobileSidebar()"
                            >
                                @include('partials.admin.icons.'.$item['icon'], ['active' => $isActive])
                                <span class="min-w-0 flex-1 truncate" :class="sidebarCollapsed ? 'xl:hidden' : ''">{{ $item['label'] }}</span>
                                @if (! empty($item['external']))
                                    <svg class="h-3.5 w-3.5 opacity-40" :class="sidebarCollapsed ? 'xl:hidden' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5h5v5m0-5L10 14m-1-8H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-3"/></svg>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @can('access-admin')
                <div>
                    <p class="mb-2 px-3 text-[10px] font-bold tracking-[.16em] text-ink-500 uppercase dark:text-ink-400" :class="sidebarCollapsed ? 'xl:hidden' : ''">Manajemen</p>
                    <ul class="space-y-1">
                        @foreach ($adminMenu as $item)
                            @php $isActive = request()->routeIs($item['route']); @endphp
                            <li>
                                <a
                                    href="{{ route($item['route']) }}"
                                    @class(['menu-item', 'menu-item-active' => $isActive, 'menu-item-inactive' => ! $isActive])
                                    :class="sidebarCollapsed ? 'xl:justify-center xl:px-2' : ''"
                                    :title="sidebarCollapsed ? '{{ $item['label'] }}' : null"
                                    @click="closeMobileSidebar()"
                                >
                                    @include('partials.admin.icons.'.$item['icon'], ['active' => $isActive])
                                    <span class="min-w-0 flex-1 truncate" :class="sidebarCollapsed ? 'xl:hidden' : ''">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endcan
        </nav>
    </div>

    <div class="shrink-0 border-t border-ink-200/80 p-3 dark:border-white/8">
        <div class="flex items-center gap-3 rounded-2xl bg-white/75 p-2.5 shadow-sm ring-1 ring-ink-200/70 dark:bg-white/5 dark:ring-white/8" :class="sidebarCollapsed ? 'xl:justify-center' : ''">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-sm font-bold text-brand-800 dark:bg-white/10 dark:text-white">{{ $initials }}</span>
            <div class="min-w-0 flex-1" :class="sidebarCollapsed ? 'xl:hidden' : ''">
                <p class="truncate text-sm font-semibold text-ink-950 dark:text-white">{{ $user?->name }}</p>
                <p class="text-[11px] font-semibold capitalize text-brand-700 dark:text-brand-300">{{ $user?->role->value }}</p>
            </div>
        </div>

        <button type="button" class="mt-2 hidden min-h-10 w-full items-center justify-center gap-2 rounded-xl text-xs font-semibold text-ink-500 hover:bg-brand-50 hover:text-brand-800 xl:flex dark:text-ink-400 dark:hover:bg-white/6 dark:hover:text-white" @click="sidebarCollapsed = ! sidebarCollapsed" :title="sidebarCollapsed ? 'Perluas navigasi' : 'Ciutkan navigasi'">
            <svg class="h-4 w-4 transition-transform" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            <span :class="sidebarCollapsed ? 'xl:hidden' : ''">Ciutkan navigasi</span>
        </button>
    </div>
</aside>
