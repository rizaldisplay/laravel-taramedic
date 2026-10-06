@php
    use App\Services\SettingsService;

    $user = auth()->user();
    $initials = $user
        ? collect(explode(' ', $user->name))->filter()->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))->take(2)->join('')
        : '';
    $institutionName = app(SettingsService::class)->institutionName();
@endphp

<header class="admin-header flex min-h-18 w-full items-center px-3 sm:px-5 xl:px-7">
    <div class="flex w-full items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-ink-200 bg-white text-ink-600 hover:border-brand-300 hover:text-brand-700 dark:border-white/10 dark:bg-white/5 dark:text-ink-300"
                @click.stop="toggleSidebar()"
                aria-label="Buka atau tutup navigasi"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h10M4 17h16"/></svg>
            </button>

            <div class="hidden min-w-0 sm:block">
                <p class="truncate font-display text-sm font-bold tracking-[-.02em] text-ink-900 dark:text-white">{{ $institutionName }}</p>
                <div class="mt-0.5 flex items-center gap-2 text-xs text-ink-500 dark:text-ink-400">
                    <span>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                    <span aria-hidden="true">•</span>
                    <span x-data="{ time: '{{ now()->format('H:i') }}' }" x-init="setInterval(() => time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }), 30000)" x-text="time"></span>
                    <span>WIB</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <span class="hidden items-center gap-2 rounded-full border border-success-200 bg-success-50 px-3 py-1.5 text-xs font-bold text-success-700 md:inline-flex dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400">
                <span class="h-2 w-2 rounded-full bg-success-500"></span>
                Operasional
            </span>

            <button
                type="button"
                class="flex h-11 w-11 items-center justify-center rounded-xl border border-ink-200 bg-white text-ink-600 hover:border-brand-300 hover:text-brand-700 dark:border-white/10 dark:bg-white/5 dark:text-ink-300"
                @click.prevent="darkMode = ! darkMode"
                aria-label="Ganti tema tampilan"
            >
                <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75 9.75 9.75 0 018.25 6a9.718 9.718 0 01.748-3.752A9.753 9.753 0 003 11.25 9.75 9.75 0 0012.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
            </button>

            @auth
                <div class="relative" x-data="{ dropdownOpen: false }" @click.outside="dropdownOpen = false">
                    <button type="button" class="flex h-11 items-center gap-2 rounded-xl border border-ink-200 bg-white p-1.5 pr-2.5 hover:border-brand-300 dark:border-white/10 dark:bg-white/5" @click.prevent="dropdownOpen = ! dropdownOpen" :aria-expanded="dropdownOpen">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-ink-950 text-xs font-bold text-white dark:bg-brand-400 dark:text-ink-950">{{ $initials }}</span>
                        <span class="hidden max-w-28 truncate text-sm font-semibold text-ink-800 sm:block dark:text-white">{{ $user->name }}</span>
                        <svg class="h-4 w-4 text-ink-400" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>

                    <div x-show="dropdownOpen" x-cloak x-transition class="absolute right-0 mt-2 w-64 overflow-hidden rounded-2xl border border-ink-200 bg-white p-2 shadow-2xl dark:border-white/10 dark:bg-ink-900">
                        <div class="px-3 py-2">
                            <p class="truncate text-sm font-bold text-ink-900 dark:text-white">{{ $user->name }}</p>
                            <p class="truncate text-xs text-ink-500">{{ $user->email }}</p>
                        </div>
                        <div class="my-1 h-px bg-ink-100 dark:bg-white/8"></div>
                        <a href="{{ route('kiosk') }}" target="_blank" rel="noopener" class="flex rounded-xl px-3 py-2.5 text-sm font-semibold text-ink-700 hover:bg-brand-50 hover:text-brand-800 dark:text-ink-300 dark:hover:bg-white/5">Buka kiosk</a>
                        <a href="{{ route('display') }}" target="_blank" rel="noopener" class="flex rounded-xl px-3 py-2.5 text-sm font-semibold text-ink-700 hover:bg-brand-50 hover:text-brand-800 dark:text-ink-300 dark:hover:bg-white/5">Buka monitor</a>
                        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-ink-100 pt-1 dark:border-white/8">
                            @csrf
                            <button type="submit" class="flex w-full rounded-xl px-3 py-2.5 text-sm font-semibold text-error-600 hover:bg-error-50 dark:text-error-400 dark:hover:bg-error-500/10">Keluar</button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </div>
</header>
