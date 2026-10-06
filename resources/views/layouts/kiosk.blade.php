<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <meta name="theme-color" content="#f8fbff">
    <title>{{ $title ?? 'Kios Antrean' }} — {{ config('queue_system.institution_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="queue-shell kiosk-shell font-kiosk antialiased">
    {{ $slot }}
    @livewireScriptConfig
</body>
</html>
