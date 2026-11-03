@props(['groups', 'user'])

@php
    // Pestañas: ítems con `tab` y grupos con `tab` (llevan a su primer ítem). Lo demás queda en «Más».
    $tabs = [];
    foreach ($groups as $group) {
        if ($group['label'] && $group['tab']) {
            $tabs[] = ['icon' => $group['icon'], 'label' => $group['label'], 'href' => $group['items'][0]['href'], 'active' => $group['active']];
        } else {
            foreach ($group['items'] as $item) {
                if ($item['tab']) {
                    $tabs[] = $item;
                }
            }
        }
    }
    $moreActive = collect($groups)->contains(fn ($group) => $group['active'] && ! $group['tab'] && collect($group['items'])->contains(fn ($item) => $item['active'] && ! $item['tab']));
@endphp

{{-- Móvil (< md): cuatro destinos fijos abajo y «Más», que abre una hoja con el resto y la cuenta. --}}
<div x-data="{ more: false }" @keydown.escape.window="more = false" x-effect="document.documentElement.classList.toggle('overflow-hidden', more)" class="md:hidden print:hidden">
    <div x-show="more" x-cloak x-transition.opacity.duration.150ms @click="more = false" class="fixed inset-0 z-30 bg-[var(--trz-velo)]"></div>
    <div x-show="more" x-cloak x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
        class="fixed inset-x-0 bottom-[var(--tabbar-height)] z-40 max-h-[75vh] overflow-y-auto overscroll-contain rounded-t-[20px] border border-b-0 border-[var(--trz-linea)] bg-[var(--trz-superficie)] pb-2 shadow-[var(--shadow-hoja)]" role="dialog" aria-label="Más opciones">
        <div class="flex h-[18px] items-end justify-center pt-2" aria-hidden="true"><span class="h-1 w-10 rounded-full bg-[var(--trz-borde-control)]"></span></div>
        <nav aria-label="Más secciones">
            <x-nav.list :groups="$groups" skip-tabs />
        </nav>
        <div class="mt-2 flex flex-col gap-3 border-t border-[var(--trz-linea)] px-5 py-4">
            <div class="flex flex-col">
                <span class="text-[15px] font-bold text-[var(--trz-tinta)]">{{ $user['name'] }}</span>
                <span class="text-[13px] text-[var(--trz-texto-2)]">{{ $user['role'] }}</span>
            </div>
            <x-theme-toggle />
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex min-h-12 w-full items-center gap-3 text-left text-base font-semibold text-[var(--trz-alerta)]">
                    <x-ui-icon name="log-out" class="h-5 w-5" />
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>

    <nav aria-label="Navegación principal" class="fixed inset-x-0 bottom-0 z-40 flex h-[var(--tabbar-height)] border-t border-[var(--trz-linea)] bg-[var(--trz-superficie)]">
        @foreach($tabs as $tab)
            <a href="{{ $tab['href'] }}" @if($tab['active']) aria-current="page" @endif
                class="flex flex-1 flex-col items-center justify-center gap-[3px] border-t-[3px] text-[11px] font-semibold min-[340px]:text-[12px] min-[380px]:text-[13px] {{ $tab['active'] ? 'border-[var(--trz-hilo)] text-[var(--trz-hilo-texto)]' : 'border-transparent text-[var(--trz-texto-2)]' }}">
                <x-ui-icon :name="$tab['icon']" class="h-[22px] w-[22px]" />
                {{ $tab['label'] }}
            </a>
        @endforeach
        <button type="button" @click="more = !more" :aria-expanded="more"
            :class="more || {{ $moreActive ? 'true' : 'false' }} ? 'border-[var(--trz-hilo)] text-[var(--trz-hilo-texto)]' : 'border-transparent text-[var(--trz-texto-2)]'"
            class="flex flex-1 flex-col items-center justify-center gap-[3px] border-t-[3px] text-[11px] font-semibold min-[340px]:text-[12px] min-[380px]:text-[13px]">
            <x-ui-icon name="menu" class="h-[22px] w-[22px]" />
            Más
        </button>
    </nav>
</div>
