<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proxify Pro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex bg-ink text-text-primary font-sans antialiased">
    <x-sidebar />

    <main class="flex-1 p-6">
        <h1 class="text-2xl font-semibold">Dashboard</h1>
    </main>

    @fluxAppearance
    @fluxScripts
</body>
</html>
