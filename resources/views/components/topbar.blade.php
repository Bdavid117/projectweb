@props(['user', 'period' => '2026-2S'])

@php
    $account = auth()->user();
    $email = $account?->email;
    $canOpenProfile = $account?->role === \App\Enums\Role::Estudiante;
@endphp

<header class="relative z-40 flex h-[var(--topbar-height)] w-full shrink-0 items-center gap-3 border-b border-[var(--border-subtle)] bg-[var(--surface-card)] px-5">
    <button type="button" class="-ml-1 flex h-9 w-9 items-center justify-center rounded-[var(--radius-md)] text-[var(--color-neutral-600)] hover:bg-[var(--surface-sunken)] sm:hidden" aria-label="Abrir menú de navegación" x-data @click="$dispatch('sidebar-toggle')">
        <x-lucide-menu class="h-5 w-5" />
    </button>

    <a href="{{ route('panel.index') }}" class="flex items-center gap-[10px] rounded-[var(--radius-md)]" aria-label="Trayectoria Estudiantil, ir al inicio">
        <x-logo class="h-8 w-8" />
        <span class="hidden text-sm font-semibold text-[var(--text-primary)] sm:inline">Trayectoria Estudiantil</span>
    </a>

    <div class="flex-1"></div>

    <div class="hidden items-center gap-[6px] rounded-full bg-[var(--surface-sunken)] px-[11px] py-[5px] sm:flex">
        <x-lucide-calendar class="h-[14px] w-[14px] text-[var(--text-secondary)]" />
        <span class="text-xs font-medium text-[var(--text-secondary)]">{{ $period }}</span>
    </div>

    <div class="relative" x-data="notifications('{{ route('notifications.index') }}')" @keydown.escape.window="open = false" @click.outside="open = false">
        <button type="button" class="relative flex h-9 w-9 items-center justify-center rounded-[var(--radius-md)] text-[var(--color-neutral-500)] hover:bg-[var(--surface-sunken)]" aria-label="Notificaciones" aria-haspopup="true" :aria-expanded="open" @click="open = !open">
            <x-lucide-bell class="h-[18px] w-[18px]" />
            <span x-show="count > 0" x-cloak x-text="count" class="absolute right-[3px] top-[3px] flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--color-danger)] px-1 text-[10px] font-semibold text-white"></span>
        </button>
        <div x-show="open" x-cloak x-transition.origin.top.right.duration.150ms class="absolute right-[-60px] top-full mt-2 w-[min(340px,calc(100vw-24px))] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] shadow-lg sm:right-0">
            <div class="border-b border-[var(--border-subtle)] px-4 py-3 text-sm font-semibold text-[var(--text-primary)]">Notificaciones</div>
            <div x-html="html"></div>
        </div>
    </div>

    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
        <button type="button" class="flex items-center gap-2 rounded-[var(--radius-md)] py-1 pl-1 pr-2 hover:bg-[var(--surface-sunken)]" aria-label="Menú de usuario" aria-haspopup="true" :aria-expanded="open" @click="open = !open">
            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-xs font-semibold text-[var(--color-primary-700)]">
                {{ $user['initials'] }}
            </div>
            <div class="hidden flex-col text-left leading-tight sm:flex">
                <span class="text-sm font-medium text-[var(--text-primary)]">{{ $user['name'] }}</span>
                <span class="text-xs text-[var(--text-muted)]">{{ $user['role'] }}</span>
            </div>
            <x-lucide-chevron-down class="h-[14px] w-[14px] text-[var(--color-neutral-400)] transition-transform" ::class="open && 'rotate-180'" />
        </button>
        <div x-show="open" x-cloak x-transition.origin.top.right.duration.150ms class="absolute right-0 top-full mt-2 w-[260px] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] shadow-lg">
            <div class="flex flex-col gap-[2px] border-b border-[var(--border-subtle)] px-4 py-3">
                <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $user['name'] }}</span>
                <span class="text-xs text-[var(--text-muted)]">{{ $email }}</span>
                <span class="text-xs text-[var(--text-secondary)]">{{ $user['role'] }} · {{ $period }}</span>
            </div>
            @if($canOpenProfile)
                <a href="{{ route('profile.show') }}" class="flex items-center gap-2 px-4 py-3 text-sm text-[var(--text-primary)] hover:bg-[var(--surface-page)]">
                    <x-lucide-user class="h-4 w-4 text-[var(--color-neutral-500)]" />
                    Mi perfil
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm text-[var(--text-primary)] hover:bg-[var(--surface-page)]">
                    <x-lucide-log-out class="h-4 w-4 text-[var(--color-neutral-500)]" />
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
        @csrf
        <button type="submit" class="flex items-center gap-1 rounded-[var(--radius-md)] px-2 py-1 text-xs font-medium text-[var(--text-secondary)] hover:bg-[var(--surface-sunken)]" aria-label="Salir del sistema">
            <x-lucide-log-out class="h-4 w-4" />
            <span>Salir</span>
        </button>
    </form>
</header>
