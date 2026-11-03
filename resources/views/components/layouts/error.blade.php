@props(['code', 'title'])

@php
    $account = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    {{-- Solo CSS: las páginas de error tienen que funcionar también en 500 y 503, sin depender de JavaScript. --}}
    <x-head :title="$title.' · Trayectoria Estudiantil'" :assets="false" />
</head>
<body class="flex min-h-full flex-col bg-[var(--trz-papel)] font-sans text-[var(--trz-tinta)] antialiased">
    <header class="flex h-[var(--topbar-height)] w-full shrink-0 items-center gap-3 border-b border-[var(--trz-linea)] bg-[var(--trz-superficie)] px-5">
        <a href="{{ $account ? route('panel.index') : route('login.show') }}" class="flex min-h-11 items-center gap-2.5 rounded-[var(--radius-md)]" aria-label="Trayectoria Estudiantil">
            <x-logo class="h-[30px] w-auto" />
            <span class="hidden font-display text-base font-bold text-[var(--trz-tinta)] sm:inline">Trayectoria Estudiantil</span>
        </a>

        <div class="flex-1"></div>

        @if($account)
            <div class="hidden flex-col text-right leading-tight sm:flex">
                <span class="text-sm font-semibold text-[var(--trz-tinta)]">{{ $account->name }}</span>
                <span class="text-[13px] text-[var(--trz-texto-2)]">{{ $account->role->label() }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="min-h-11 rounded-[var(--radius-md)] px-3 py-2 text-sm font-semibold text-[var(--trz-texto-2)] hover:bg-[var(--trz-hilo-fondo)]">Salir</button>
            </form>
        @else
            <a href="{{ route('login.show') }}" class="inline-flex min-h-11 items-center rounded-[var(--radius-md)] px-3 py-2 text-sm font-semibold text-[var(--trz-hilo-texto)] hover:bg-[var(--trz-hilo-fondo)]">Iniciar sesión</a>
        @endif
    </header>

    <main class="flex flex-1 items-center justify-center p-4">
        <div class="flex w-full max-w-[540px] flex-col gap-4 rounded-[var(--radius-xl)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)] p-7 sm:p-10">
            {{-- El código es grande pero no es el mensaje: lo acompaña un hilo que se corta y un nodo abierto. --}}
            <div class="flex items-center gap-3" aria-hidden="true">
                <span class="font-display text-[64px] font-semibold leading-none text-[var(--trz-hilo-texto)] sm:text-[72px]">{{ $code }}</span>
                <svg class="h-[30px] w-[84px] shrink-0" viewBox="0 0 84 30" fill="none">
                    <rect x="0" y="13" width="46" height="3" rx="1.5" class="fill-[var(--trz-hilo)]" />
                    <rect x="58" y="13" width="14" height="3" rx="1.5" class="fill-[var(--trz-linea)]" />
                    <circle cx="76" cy="14.5" r="7.5" class="fill-[var(--trz-superficie)] [stroke:var(--trz-borde-control)]" stroke-width="2.5" />
                </svg>
            </div>
            <span class="sr-only">Error {{ $code }}</span>

            <h1 class="text-[22px] font-semibold leading-tight text-[var(--trz-tinta)] sm:text-[26px]">{{ $title }}</h1>

            <div class="flex flex-col gap-3 text-base leading-relaxed text-[var(--trz-texto-2)]">
                {{ $slot }}
            </div>

            <div class="mt-3 flex flex-wrap gap-3">
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
