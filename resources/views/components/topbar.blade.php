@props(['user', 'period' => '2026-2S'])

<header class="flex h-[var(--topbar-height)] w-full shrink-0 items-center gap-3 border-b border-[var(--border-subtle)] bg-[var(--surface-card)] px-5">
    <x-lucide-menu class="h-5 w-5 text-[var(--color-neutral-600)] sm:hidden" />

    <div class="flex items-center gap-[10px]">
        <div class="flex h-[30px] w-[30px] items-center justify-center rounded-[var(--radius-md)] bg-[var(--color-primary-600)]">
            <x-lucide-graduation-cap class="h-[18px] w-[18px] text-[var(--color-white)]" />
        </div>
        <span class="hidden text-sm font-semibold text-[var(--text-primary)] sm:inline">Trayectoria Estudiantil</span>
    </div>

    <div class="flex-1"></div>

    <div class="hidden items-center gap-[6px] rounded-full bg-[var(--surface-sunken)] px-[11px] py-[5px] sm:flex">
        <x-lucide-calendar class="h-[14px] w-[14px] text-[var(--text-secondary)]" />
        <span class="text-xs font-medium text-[var(--text-secondary)]">{{ $period }}</span>
    </div>

    <x-lucide-bell class="h-[18px] w-[18px] text-[var(--color-neutral-500)]" />

    <div class="flex items-center gap-2 rounded-[var(--radius-md)] py-1 pl-1 pr-2">
        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-xs font-semibold text-[var(--color-primary-700)]">
            {{ $user['initials'] }}
        </div>
        <div class="flex flex-col leading-tight">
            <span class="text-sm font-medium text-[var(--text-primary)]">{{ $user['name'] }}</span>
            <span class="text-xs text-[var(--text-muted)]">{{ $user['role'] }}</span>
        </div>
        <x-lucide-chevron-down class="h-[14px] w-[14px] text-[var(--color-neutral-400)]" />
    </div>
</header>
