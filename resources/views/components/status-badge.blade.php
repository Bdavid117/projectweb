@props(['text', 'tone' => 'success', 'icon' => null])

{{-- Chip de estado: forma + ícono + texto, nunca solo color. --}}
@php
    $tones = [
        'success' => ['bg-[var(--trz-logro-fondo)] text-[var(--trz-logro)]', 'circle-check'],
        'warning' => ['bg-[var(--trz-hito-fondo)] text-[var(--trz-hito-texto)]', 'hourglass'],
        'danger' => ['bg-[var(--trz-alerta-fondo)] text-[var(--trz-alerta)]', 'circle-x'],
        'info' => ['bg-[var(--trz-hilo-fondo)] text-[var(--trz-hilo-texto)]', 'info'],
        'review' => ['bg-[var(--trz-hilo-fondo)] text-[var(--trz-hilo-texto)]', 'eye'],
        'primary' => ['bg-[var(--trz-hilo-fondo)] text-[var(--trz-hilo-texto)]', 'graduation-cap'],
        'restricted' => ['bg-[var(--trz-brote-fondo)] text-[var(--trz-brote)]', 'lock'],
        'neutral' => ['border border-[var(--trz-linea)] bg-[var(--trz-superficie)] text-[var(--trz-texto-2)]', 'file-text'],
    ];
    [$classes, $defaultIcon] = $tones[$tone] ?? $tones['info'];
    $glyph = $icon === false ? null : ($icon ?? $defaultIcon);
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-[7px] whitespace-nowrap rounded-[var(--radius-sm)] px-3 py-[5px] text-[13px] font-bold '.$classes]) }}>
    @if($glyph)
        <x-ui-icon :name="$glyph" class="h-[15px] w-[15px]" />
    @endif
    {{ $text }}
</span>
