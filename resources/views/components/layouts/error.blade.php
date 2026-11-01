@props(['code', 'title', 'tone' => 'muted'])

@php
    $account = auth()->user();

    // Clases completas por tono (nunca concatenar variables en el nombre de la clase).
    // Sin íconos: las páginas de error solo cargan el CSS para ser robustas (útiles también en 500/503).
    $toneClasses = match ($tone) {
        'danger' => 'bg-[var(--color-danger-bg)] text-[var(--color-danger)]',
        'warning' => 'bg-[var(--color-warning-bg)] text-[var(--color-warning)]',
        'info' => 'bg-[var(--color-info-bg)] text-[var(--color-info)]',
        default => 'bg-[var(--surface-sunken)] text-[var(--text-secondary)]',
    };
@endphp

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }} · Trayectoria Estudiantil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full flex-col bg-[var(--surface-page)] font-sans text-[var(--text-primary)]">
    <header class="flex h-[var(--topbar-height)] w-full shrink-0 items-center gap-3 border-b border-[var(--border-subtle)] bg-[var(--surface-card)] px-5">
        <a href="{{ $account ? route('panel.index') : route('login.show') }}" class="flex items-center gap-[10px] rounded-[var(--radius-md)]" aria-label="Trayectoria Estudiantil">
            <x-logo class="h-8 w-8" />
            <span class="hidden text-sm font-semibold text-[var(--text-primary)] sm:inline">Trayectoria Estudiantil</span>
        </a>

        <div class="flex-1"></div>

        @if($account)
            <div class="hidden flex-col text-right leading-tight sm:flex">
                <span class="text-sm font-medium text-[var(--text-primary)]">{{ $account->name }}</span>
                <span class="text-xs text-[var(--text-muted)]">{{ $account->role->label() }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-[var(--radius-md)] px-2 py-1 text-xs font-medium text-[var(--text-secondary)] hover:bg-[var(--surface-sunken)]">Salir</button>
            </form>
        @else
            <a href="{{ route('login.show') }}" class="rounded-[var(--radius-md)] px-2 py-1 text-xs font-medium text-[var(--text-secondary)] hover:bg-[var(--surface-sunken)]">Iniciar sesión</a>
        @endif
    </header>

    <main class="flex flex-1 items-center justify-center p-4">
        <div class="flex w-full max-w-[480px] flex-col gap-4 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-8">
            <span class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold {{ $toneClasses }}">Error {{ $code }}</span>

            <h1 class="text-xl font-semibold text-[var(--text-primary)]">{{ $title }}</h1>

            <div class="flex flex-col gap-3 text-sm text-[var(--text-secondary)]">
                {{ $slot }}
            </div>

            <div class="mt-2 flex flex-wrap gap-3">
                @isset($actions)
                    {{ $actions }}
                @else
                    @if($account)
                        <x-button :href="route('panel.index')" variant="primary">Volver al panel</x-button>
                    @else
                        <x-button :href="route('login.show')" variant="primary">Iniciar sesión</x-button>
                    @endif
                @endisset
            </div>
        </div>
    </main>
</body>
</html>
