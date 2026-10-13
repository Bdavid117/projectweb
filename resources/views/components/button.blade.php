@props(['variant' => 'primary', 'icon' => null, 'href' => null, 'type' => 'button'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-[var(--radius-md)] text-sm font-semibold transition-colors';
    $variants = [
        'primary' => 'bg-[var(--color-primary-600)] text-[var(--text-on-brand)] px-[18px] py-[10px] hover:bg-[var(--color-primary-700)]',
        'secondary' => 'bg-[var(--surface-card)] text-[var(--text-secondary)] border border-[var(--border-default)] px-[18px] py-[10px] hover:bg-[var(--surface-sunken)]',
        'subtle' => 'bg-transparent text-[var(--color-primary-600)] px-3 py-2 gap-[6px] hover:bg-[var(--color-primary-50)]',
    ];
    $classes = $base . ' ' . $variants[$variant];
    $tag = $href ? 'a' : 'button';
@endphp
<{{ $tag }}
    @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="h-4 w-4" />
    @endif
    {{ $slot }}
</{{ $tag }}>
