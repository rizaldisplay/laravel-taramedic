<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#071018">
    <title>{{ $title ?? 'Masuk' }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body
    x-data="navaLayout"
    :class="{ 'dark': darkMode === true }"
    class="bg-ink-50 font-sans antialiased dark:bg-ink-950"
>
    @php
        $settings = app(\App\Services\SettingsService::class);
        $institutionName = $settings->institutionName();
        $institutionTagline = $settings->institutionTagline();
        $institutionLogoUrl = $settings->institutionLogoUrl();
    @endphp

    <main class="grid min-h-screen lg:grid-cols-[minmax(0,1.05fr)_minmax(460px,.95fr)]">
        <section class="relative hidden overflow-hidden bg-ink-950 p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
            <div class="absolute inset-0 opacity-40" style="background-image: linear-gradient(rgb(255 255 255 / .045) 1px, transparent 1px), linear-gradient(90deg, rgb(255 255 255 / .045) 1px, transparent 1px); background-size: 42px 42px;"></div>
            <div class="absolute -top-40 -left-28 h-96 w-96 rounded-full bg-brand-400/20 blur-3xl"></div>
            <div class="absolute -right-24 -bottom-36 h-96 w-96 rounded-full bg-warning-400/10 blur-3xl"></div>

            <div class="relative flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-400 text-xl font-black text-ink-950">
                    @if ($institutionLogoUrl)
                        <img src="{{ $institutionLogoUrl }}" alt="" class="max-h-10 max-w-10 object-contain">
                    @else
                        N
                    @endif
                </span>
                <div>
                    <p class="font-bold tracking-tight">{{ config('app.name') }}</p>
                    <p class="text-xs font-semibold tracking-[.16em] text-brand-300 uppercase">Antrean klinik</p>
                </div>
            </div>

            <div class="relative max-w-2xl py-16">
                <p class="mb-5 text-sm font-bold tracking-[.18em] text-brand-300 uppercase">Sistem antrean klinik</p>
                <h1 class="text-5xl font-bold leading-[1.04] tracking-[-.055em] xl:text-6xl">Pelayanan klinik lebih tertib, dari tiket hingga loket.</h1>
                <p class="mt-6 max-w-xl text-base leading-7 text-ink-300 xl:text-lg">Kelola pengambilan nomor, panggilan pasien, dan laporan layanan dalam satu sistem yang ringkas.</p>

                <div class="mt-10 grid max-w-xl grid-cols-3 gap-3">
                    @foreach ([['01', 'Kiosk mandiri'], ['02', 'Panggilan realtime'], ['03', 'Laporan layanan']] as [$number, $label])
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                            <p class="text-xs font-bold text-brand-300">{{ $number }}</p>
                            <p class="mt-4 text-sm font-semibold text-white/85">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="relative flex items-end justify-between gap-8 border-t border-white/10 pt-6">
                <div>
                    <p class="font-semibold">{{ $institutionName }}</p>
                    <p class="mt-1 text-sm text-ink-400">{{ $institutionTagline }}</p>
                </div>
                <span class="rounded-full border border-success-400/25 bg-success-400/10 px-3 py-1.5 text-xs font-bold text-success-400">Sistem siap</span>
            </div>
        </section>

        <section class="relative flex min-h-screen items-center justify-center bg-white px-5 py-10 dark:bg-ink-950 sm:px-10">
            <div class="absolute top-5 right-5 flex items-center gap-2">
                <button
                    type="button"
                    class="flex h-11 w-11 items-center justify-center rounded-xl border border-ink-200 bg-white text-ink-600 hover:border-brand-300 hover:text-brand-700 dark:border-white/10 dark:bg-white/5 dark:text-ink-300"
                    @click.prevent="darkMode = !darkMode"
                    aria-label="Ganti tema tampilan"
                >
                    <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75 9.75 9.75 0 018.25 6a9.718 9.718 0 01.748-3.752A9.753 9.753 0 003 11.25 9.75 9.75 0 0012.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                    <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                </button>
            </div>

            <div class="w-full max-w-md">
                <div class="mb-10 flex items-center gap-3 lg:hidden">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-400 text-lg font-black text-ink-950">
                        @if ($institutionLogoUrl)
                            <img src="{{ $institutionLogoUrl }}" alt="" class="max-h-9 max-w-9 object-contain">
                        @else
                            N
                        @endif
                    </span>
                    <div>
                        <p class="font-bold text-ink-950 dark:text-white">{{ config('app.name') }}</p>
                        <p class="text-xs text-ink-500">{{ $institutionName }}</p>
                    </div>
                </div>

                {{ $slot }}
            </div>
        </section>
    </main>

    @livewireScriptConfig
</body>
</html>
