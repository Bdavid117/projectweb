@props(['groups'])

{{-- Cajón de navegación para tablet (md a xl). Se abre con el evento `nav-toggle` de la barra superior. --}}
<div x-data="{ open: false }" @nav-toggle.window="open = !open" @keydown.escape.window="open = false" class="print:hidden">
    <div x-show="open" x-cloak x-transition.opacity.duration.150ms @click="open = false" class="fixed inset-0 top-[var(--topbar-height)] z-40 bg-[var(--trz-velo)] xl:hidden"></div>
    <aside x-show="open" x-cloak x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
        class="fixed bottom-0 left-0 top-[var(--topbar-height)] z-50 w-[340px] max-w-[85vw] overflow-y-auto border-r border-[var(--trz-linea)] bg-[var(--trz-superficie)] py-5 xl:hidden" aria-label="Menú de navegación">
        <nav aria-label="Navegación principal">
            <x-nav.list :groups="$groups" />
        </nav>
    </aside>
</div>
