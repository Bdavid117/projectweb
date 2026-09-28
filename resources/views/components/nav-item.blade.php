@props(['icon', 'label', 'href' => '#', 'active' => false])

<a
    href="{{ $href }}"
    class="flex w-full items-center gap-[10px] rounded-[var(--radius-md)] px-3 py-[10px] text-sm font-medium {{ $active ? 'bg-[var(--color-primary-50)] text-[var(--color-primary-700)]' : 'text-[var(--color-neutral-600)] hover:bg-[var(--surface-sunken)]' }}"
>
    <x-dynamic-component
        :component="'lucide-' . $icon"
        class="h-[18px] w-[18px] {{ $active ? 'text-[var(--color-primary-600)]' : 'text-[var(--color-neutral-500)]' }}"
    />
    <span>{{ $label }}</span>
</a>
