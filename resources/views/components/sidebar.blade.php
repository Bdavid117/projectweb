@props(['groups'])

<div x-data="{ open: false }" @sidebar-toggle.window="open = !open" @keydown.escape.window="open = false" class="contents">
    <div x-show="open" x-cloak x-transition.opacity @click="open = false" class="fixed inset-x-0 bottom-0 top-[var(--topbar-height)] z-30 bg-[var(--color-primary-900)]/40 sm:hidden"></div>

    <nav aria-label="Navegación principal" :class="open ? 'fixed bottom-0 left-0 top-[var(--topbar-height)] z-40 flex! shadow-xl' : ''" class="hidden h-full w-[var(--sidebar-width)] shrink-0 flex-col gap-[3px] overflow-y-auto border-r border-[var(--border-subtle)] bg-[var(--surface-card)] p-3 sm:flex">
        @foreach($groups as $group)
            @if($group['label'] ?? null)
                <p class="px-[10px] pb-[6px] pt-3 text-[11px] font-semibold tracking-[0.6px] text-[var(--text-muted)]">
                    {{ $group['label'] }}
                </p>
            @endif
            @foreach($group['items'] as $item)
                <x-nav-item :icon="$item['icon']" :label="$item['label']" :href="$item['href']" :active="$item['active']" />
            @endforeach
        @endforeach
    </nav>
</div>
