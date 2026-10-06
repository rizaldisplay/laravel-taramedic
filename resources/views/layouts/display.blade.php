<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#eff7f7">
    <title>{{ $title ?? 'Monitor Antrian' }} — {{ config('queue_system.institution_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="display-tv-shell font-kiosk antialiased">
    {{ $slot }}
    @livewireScriptConfig
</body>
</html>
