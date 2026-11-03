@props(['message', 'tone' => 'success'])

@php
    $tones = [
        'success' => ['bg-[var(--trz-logro-fondo)] border-[var(--trz-logro)] text-[var(--trz-logro)]', 'circle-check'],
        'danger' => ['bg-[var(--trz-alerta-fondo)] border-[var(--trz-alerta)] text-[var(--trz-alerta)]', 'circle-alert'],
        'info' => ['bg-[var(--trz-hilo-fondo)] border-[var(--trz-hilo)] text-[var(--trz-hilo-texto)]', 'info'],
    ];
    [$classes, $icon] = $tones[$tone];
@endphp
<div role="{{ $tone === 'danger' ? 'alert' : 'status' }}" x-data="{ shown: true }" x-show="shown" {{ $attributes->merge(['class' => 'flex items-start gap-3.5 rounded-[var(--radius-lg)] border-[1.5px] px-[18px] py-4 '.$classes]) }}>
    <x-ui-icon :name="$icon" class="mt-0.5 h-[22px] w-[22px] shrink-0" />
    <p class="flex-1 text-[15px] font-semibold leading-snug">{{ $message }}</p>
    <button type="button" class="-m-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-[var(--radius-sm)] text-[var(--trz-texto-2)] hover:bg-[var(--trz-papel)]" aria-label="Cerrar aviso" @click="shown = false">
        <x-ui-icon name="x" class="h-[18px] w-[18px]" />
    </button>
</div>
