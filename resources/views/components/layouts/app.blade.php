@props(['user', 'navGroups', 'title' => null])

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'Trayectoria Estudiantil' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-[var(--surface-page)] font-sans text-[var(--text-primary)]">
    <div class="flex h-full flex-col">
        <x-topbar :user="$user" />
        <div class="flex flex-1 overflow-hidden">
            <x-sidebar :groups="$navGroups" />
            <main class="flex-1 overflow-y-auto">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
