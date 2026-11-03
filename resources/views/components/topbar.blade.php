@props(['user', 'groups', 'title' => null, 'period' => null])

@php
    $account = auth()->user();
    $email = $account?->email;
    $canOpenProfile = $account?->role === \App\Enums\Role::Estudiante;
    $period ??= rescue(fn () => \App\Models\AcademicTerm::current()?->codigo, null, false) ?? '—';
    $pageTitle = $title ? trim(explode(' · ', $title)[0]) : null;
    $linkBase = 'relative inline-flex h-[var(--topbar-height)] items-center gap-1.5 whitespace-nowrap px-3.5 text-[15px] transition-colors';
@endphp

<header class="sticky top-0 z-40 border-b border-[var(--trz-linea)] bg-[var(--trz-superficie)] print:hidden">
    <div class="flex h-[var(--topbar-height)] items-center gap-3 px-[18px] sm:px-5 xl:px-7">
        {{-- Tablet (md a xl): la barra se pliega en un botón que abre el cajón. --}}
        <button type="button" class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-[var(--radius-md)] border-[1.5px] border-[var(--trz-borde-control)] text-[var(--trz-tinta)] hover:bg-[var(--trz-hilo-fondo)] md:flex xl:hidden"
            aria-label="Abrir el menú de navegación" x-data @click="$dispatch('nav-toggle')">
            <x-ui-icon name="menu" class="h-[22px] w-[22px]" />
        </button>

        <a href="{{ route('panel.index') }}" class="flex min-h-11 min-w-0 items-center gap-2.5 rounded-[var(--radius-md)] md:shrink-0" aria-label="Trayectoria Estudiantil, ir al inicio">
            <x-logo class="h-[30px] w-auto shrink-0" />
            <span class="hidden font-display text-base font-bold text-[var(--trz-tinta)] md:inline">Trayectoria Estudiantil</span>
            @if($pageTitle)
                {{-- Hasta dos líneas antes de cortar: «Historia académica» ya no pierde letras a 360 px. --}}
                <span class="line-clamp-2 font-display text-base font-semibold leading-tight text-[var(--trz-tinta)] sm:text-lg md:hidden">{{ $pageTitle }}</span>
            @endif
        </a>

        {{-- Escritorio: elementos sueltos y menús desplegables por grupo. --}}
        <nav aria-label="Navegación principal" class="ml-2 hidden items-center xl:flex">
            @foreach($groups as $group)
                @if($group['label'])
                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <button type="button" aria-haspopup="true" :aria-expanded="open" @click="open = !open"
                            class="{{ $linkBase }} border-b-[3px] {{ $group['active'] ? 'border-[var(--trz-hilo)] font-semibold text-[var(--trz-tinta)]' : 'border-transparent font-medium text-[var(--trz-texto-2)] hover:bg-[var(--trz-hilo-fondo)] hover:text-[var(--trz-tinta)]' }}">
                            {{ $group['label'] }}
                            <x-ui-icon name="chevron-down" class="h-3.5 w-3.5 transition-transform" ::class="open && 'rotate-180'" />
                        </button>
                        <div x-show="open" x-cloak x-transition.origin.top.left.duration.150ms class="absolute left-0 top-full z-50 mt-1 w-[260px] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)] shadow-[var(--shadow-menu)]">
                            @foreach($group['items'] as $item)
                                <a href="{{ $item['href'] }}" @if($item['active']) aria-current="page" @endif
                                    class="flex min-h-11 items-center gap-3 px-[18px] py-2.5 text-[15px] hover:bg-[var(--trz-hilo-fondo)] {{ $item['active'] ? 'font-semibold text-[var(--trz-tinta)]' : 'font-medium text-[var(--trz-tinta)]' }}">
                                    <x-ui-icon :name="$item['icon']" class="h-[18px] w-[18px] text-[var(--trz-hilo-texto)]" />
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    @foreach($group['items'] as $item)
                        <a href="{{ $item['href'] }}" @if($item['active']) aria-current="page" @endif
                            class="{{ $linkBase }} border-b-[3px] {{ $item['active'] ? 'border-[var(--trz-hilo)] font-semibold text-[var(--trz-tinta)]' : 'border-transparent font-medium text-[var(--trz-texto-2)] hover:bg-[var(--trz-hilo-fondo)] hover:text-[var(--trz-tinta)]' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                @endif
            @endforeach
        </nav>

        <div class="flex-1 shrink-0 basis-0"></div>

        <div class="hidden items-center gap-2 rounded-[var(--radius-sm)] bg-[var(--trz-papel)] px-3 py-2 sm:flex" title="Periodo académico">
            <x-ui-icon name="calendar" class="h-[15px] w-[15px] text-[var(--trz-texto-2)]" />
            <span class="text-sm font-semibold text-[var(--trz-tinta)]">{{ $period }}</span>
        </div>

        <div class="relative" x-data="notifications('{{ route('notifications.index') }}')" @keydown.escape.window="open = false" @click.outside="open = false">
            <button type="button" class="relative flex h-11 w-11 items-center justify-center rounded-[var(--radius-md)] text-[var(--trz-texto-2)] hover:bg-[var(--trz-hilo-fondo)]" aria-label="Notificaciones" aria-haspopup="true" :aria-expanded="open" @click="open = !open">
                <x-ui-icon name="bell" class="h-5 w-5" />
                <span x-show="count > 0" x-cloak x-text="count" class="absolute right-1 top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-[var(--trz-alerta)] px-1 text-[11px] font-bold text-[var(--trz-papel)]"></span>
            </button>
            <div x-show="open" x-cloak x-transition.origin.top.right.duration.150ms class="absolute right-[-64px] top-full z-50 mt-1 w-[min(420px,calc(100vw-24px))] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)] shadow-[var(--shadow-menu)] sm:right-0">
                <div class="flex items-center justify-between border-b border-[var(--trz-linea)] px-[18px] py-3.5">
                    <h2 class="text-lg font-semibold text-[var(--trz-tinta)]">Notificaciones</h2>
                </div>
                <div x-html="html"></div>
            </div>
        </div>

        <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
            <button type="button" class="flex items-center gap-2 rounded-[var(--radius-md)] py-1 pl-1 pr-1 hover:bg-[var(--trz-hilo-fondo)] xl:pr-2" aria-label="Menú de usuario" aria-haspopup="true" :aria-expanded="open" @click="open = !open">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[var(--trz-hilo-fondo)] text-[13px] font-bold text-[var(--trz-hilo-texto)]">{{ $user['initials'] }}</span>
                <span class="hidden flex-col text-left leading-tight xl:flex">
                    <span class="text-[13px] font-bold text-[var(--trz-tinta)]">{{ $user['name'] }}</span>
                    <span class="text-[13px] text-[var(--trz-texto-2)]">{{ $user['role'] }}</span>
                </span>
            </button>
            <div x-show="open" x-cloak x-transition.origin.top.right.duration.150ms class="absolute right-0 top-full z-50 mt-1 w-[min(300px,calc(100vw-36px))] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)] shadow-[var(--shadow-menu)]">
                <div class="flex flex-col gap-0.5 border-b border-[var(--trz-linea)] px-[18px] py-3.5 [overflow-wrap:anywhere]">
                    <span class="text-[15px] font-bold text-[var(--trz-tinta)]">{{ $user['name'] }}</span>
                    <span class="text-sm text-[var(--trz-texto-2)]">{{ $email }}</span>
                    <span class="text-[13px] text-[var(--trz-texto-2)]">{{ $user['role'] }} · {{ $period }}</span>
                </div>
                @if($canOpenProfile)
                    <a href="{{ route('profile.show') }}" class="flex min-h-12 items-center gap-3 px-[18px] text-[15px] font-medium text-[var(--trz-tinta)] hover:bg-[var(--trz-hilo-fondo)]">
                        <x-ui-icon name="user" class="h-[18px] w-[18px] text-[var(--trz-hilo-texto)]" />
                        Mi perfil
                    </a>
                @endif
                <div class="border-t border-[var(--trz-linea)] p-3"><x-theme-toggle /></div>
                <form method="POST" action="{{ route('logout') }}" class="border-t border-[var(--trz-linea)]">
                    @csrf
                    <button type="submit" class="flex min-h-12 w-full items-center gap-3 px-[18px] text-left text-[15px] font-semibold text-[var(--trz-alerta)] hover:bg-[var(--trz-alerta-fondo)]">
                        <x-ui-icon name="log-out" class="h-[18px] w-[18px]" />
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
