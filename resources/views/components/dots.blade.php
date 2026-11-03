@props(['count', 'shape' => 'circle', 'tone' => 'hilo'])

{{-- Fila de puntos (círculos del hilo o rombos de experiencias): un punto equivale a varios estudiantes. --}}
@php
    $dot = match (true) {
        $shape === 'diamond' => 'h-2.5 w-2.5 rotate-45 rounded-[2px] border-[1.5px] border-[var(--trz-hito-texto)] bg-[var(--trz-hito)] m-[2px]',
        $tone === 'alerta' => 'h-3 w-3 rounded-full bg-[var(--trz-alerta)]',
        default => 'h-3 w-3 rounded-full bg-[var(--trz-hilo)]',
    };
@endphp
<div {{ $attributes->merge(['class' => 'flex min-h-4 flex-wrap items-center gap-1']) }} aria-hidden="true">
    @for($i = 0; $i < $count; $i++)
        <span class="{{ $dot }}"></span>
    @endfor
</div>
