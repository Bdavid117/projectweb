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
                @if(session('status'))
                    <div role="status" class="mx-4 mt-4 flex items-center gap-2 rounded-[var(--radius-md)] bg-[var(--color-success-bg)] p-3 text-sm font-medium text-[var(--color-success)] sm:mx-7 sm:mt-7">
                        <x-lucide-circle-check class="h-4 w-4 shrink-0" />
                        {{ session('status') }}
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
