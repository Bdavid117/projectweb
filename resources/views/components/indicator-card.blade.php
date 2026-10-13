@props(['label', 'value', 'detail', 'valueClass' => 'text-[var(--text-primary)]'])

<div class="flex w-full flex-col gap-[6px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[18px]">
    <p class="text-[11px] font-semibold tracking-[0.6px] text-[var(--text-muted)]">{{ $label }}</p>
    <p class="text-[30px] font-bold {{ $valueClass }}">{{ $value }}</p>
    <p class="text-xs text-[var(--text-secondary)]">{{ $detail }}</p>
</div>
