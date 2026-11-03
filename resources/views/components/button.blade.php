@props(['variant' => 'primary', 'icon' => null, 'href' => null, 'type' => 'button'])

@php
    // Clases completas por variante (nunca concatenar variables: Tailwind solo compila literales).
    $base = 'inline-flex min-h-[var(--control-height)] items-center justify-center gap-2 rounded-[var(--radius-md)] text-[15px] font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-45 aria-disabled:cursor-not-allowed aria-disabled:opacity-45';
    $variants = [
        'primary' => 'bg-[var(--trz-hilo)] px-[18px] py-2.5 text-[var(--trz-sobre-hilo)] hover:bg-[var(--trz-hilo-hover)]',
        'secondary' => 'border-[1.5px] border-[var(--trz-tinta)] bg-[var(--trz-superficie)] px-[18px] py-2.5 text-[var(--trz-tinta)] hover:bg-[var(--trz-hilo-fondo)]',
        'subtle' => 'bg-transparent px-3 py-2 text-[var(--trz-hilo-texto)] hover:bg-[var(--trz-hilo-fondo)]',
        'danger' => 'border-[1.5px] border-[var(--trz-alerta)] bg-[var(--trz-superficie)] px-[18px] py-2.5 text-[var(--trz-alerta)] hover:bg-[var(--trz-alerta-fondo)]',
    ];
    $classes = $base . ' ' . $variants[$variant];
    $tag = $href ? 'a' : 'button';
@endphp
<{{ $tag }}
    @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if($icon)
        <x-ui-icon :name="$icon" class="h-[17px] w-[17px]" />
    @endif
    {{ $slot }}
</{{ $tag }}>
