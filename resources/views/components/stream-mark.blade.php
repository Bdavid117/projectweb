@props(['kind' => 'academico', 'filled' => true])

{{-- Marca de corriente: círculo (académico), rombo (experiencias), cuadrado (acompañamiento) o anillo (alerta). --}}
@php
    $classes = match ($kind) {
        'experiencia' => 'h-2.5 w-2.5 rotate-45 rounded-[2px] border-[1.5px] border-[var(--trz-hito-texto)] '.($filled ? 'bg-[var(--trz-hito)]' : 'bg-[var(--trz-superficie)]'),
        'acompanamiento' => 'h-3.5 w-3.5 rounded-[3px] '.($filled ? 'bg-[var(--trz-brote)]' : 'border-2 border-[var(--trz-brote)] bg-[var(--trz-superficie)]'),
        'alerta' => 'h-3.5 w-3.5 rounded-full border-[3px] border-[var(--trz-alerta)]',
        default => 'h-3.5 w-3.5 rounded-full '.($filled ? 'bg-[var(--trz-hilo)]' : 'border-2 border-[var(--trz-borde-control)] bg-[var(--trz-superficie)]'),
    };
@endphp
<span {{ $attributes->merge(['class' => 'inline-block shrink-0 '.$classes]) }} aria-hidden="true"></span>
