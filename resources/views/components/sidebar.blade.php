@props(['groups'])

<nav class="hidden h-full w-[var(--sidebar-width)] shrink-0 flex-col gap-[3px] overflow-y-auto border-r border-[var(--border-subtle)] bg-[var(--surface-card)] p-3 sm:flex">
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
