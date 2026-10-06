<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body
    x-data="navaLayout"
    :class="{ 'dark': darkMode === true }"
    class="bg-ink-50 font-sans antialiased"
>
    <div class="flex h-screen overflow-hidden">
        @include('layouts.partials.sidebar')

        <div class="admin-shell relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
            @include('layouts.partials.overlay')

            <main class="flex min-h-full flex-col">
                @include('layouts.partials.header')

                <div class="mx-auto w-full max-w-[1540px] flex-1 p-4 md:p-6 xl:p-8 2xl:p-10">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @livewireScriptConfig
</body>
</html>
