<!DOCTYPE html>
<html lang="en" class="dark h-full overflow-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Proxify Pro') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-full overflow-hidden bg-ink text-text-primary font-sans antialiased">
    <x-sidebar />

    <main class="flex-1 overflow-hidden">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @fluxAppearance
    @fluxScripts
</body>
</html>
