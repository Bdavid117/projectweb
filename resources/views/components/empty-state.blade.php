@props(['kind' => 'historia', 'title', 'text' => null])

{{-- Estado vacío: un hilo con nodos que esperan un dato, qué falta y, si se puede, cómo empezar (slot). --}}
@php
    $ring = 'fill-[var(--trz-superficie)] [stroke:var(--trz-borde-control)]';
    $line = 'fill-[var(--trz-linea)]';
    $hilo = 'fill-[var(--trz-hilo)]';
@endphp
<div {{ $attributes->merge(['class' => 'flex flex-col items-start gap-3.5 rounded-[var(--radius-xl)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)] p-7']) }}>
    <svg class="h-16 w-[240px] max-w-full" viewBox="0 0 240 64" fill="none" aria-hidden="true">
        @switch($kind)
            @case('tesis')
                <rect x="10" y="30" width="150" height="3" rx="1.5" class="{{ $hilo }}" /><rect x="166" y="30" width="50" height="3" rx="1.5" class="{{ $line }}" />
                <circle cx="10" cy="31.5" r="9" class="{{ $hilo }}" />
                <rect x="215" y="24" width="14" height="14" rx="2" transform="rotate(45 222 31)" class="fill-[var(--trz-superficie)] [stroke:var(--trz-hito-texto)]" stroke-width="2" />
                @break
            @case('practica')
                <rect x="10" y="30" width="90" height="3" rx="1.5" class="{{ $hilo }}" /><rect x="120" y="30" width="40" height="3" rx="1.5" class="{{ $line }}" /><rect x="172" y="30" width="40" height="3" rx="1.5" class="{{ $line }}" />
                <circle cx="10" cy="31.5" r="9" class="{{ $hilo }}" />
                <rect x="215" y="24" width="14" height="14" rx="2" transform="rotate(45 222 31)" class="fill-[var(--trz-superficie)] [stroke:var(--trz-hito-texto)]" stroke-width="2" />
                @break
            @case('solicitudes')
                <rect x="10" y="30" width="220" height="3" rx="1.5" class="{{ $line }}" />
                @foreach([10, 120, 230] as $x)<circle cx="{{ $x }}" cy="31.5" r="9" class="{{ $ring }}" stroke-width="2.5" />@endforeach
                @break
            @case('experiencias')
                <rect x="48" y="30" width="30" height="3" rx="1.5" class="{{ $line }}" /><rect x="142" y="30" width="30" height="3" rx="1.5" class="{{ $line }}" />
                @foreach([28, 120, 212] as $x)<rect x="{{ $x - 7 }}" y="24.5" width="14" height="14" rx="2" transform="rotate(45 {{ $x }} 31.5)" class="fill-[var(--trz-superficie)] [stroke:var(--trz-hito-texto)]" stroke-width="2" />@endforeach
                @break
            @case('tutor')
                <rect x="34" y="30" width="60" height="3" rx="1.5" class="{{ $line }}" /><rect x="112" y="30" width="24" height="3" rx="1.5" class="{{ $line }}" /><rect x="150" y="30" width="60" height="3" rx="1.5" class="{{ $line }}" />
                <rect x="2" y="23" width="16" height="16" rx="3" class="fill-[var(--trz-superficie)] [stroke:var(--trz-brote)]" stroke-width="2.5" />
                <circle cx="230" cy="31.5" r="9" class="{{ $ring }}" stroke-width="2.5" />
                @break
            @case('solicitar')
                <rect x="10" y="30" width="90" height="3" rx="1.5" class="fill-[var(--trz-brote)]" /><rect x="124" y="30" width="40" height="3" rx="1.5" class="{{ $line }}" /><rect x="176" y="30" width="40" height="3" rx="1.5" class="{{ $line }}" />
                <rect x="2" y="23" width="16" height="16" rx="3" class="fill-[var(--trz-brote)]" />
                <circle cx="230" cy="31.5" r="9" class="{{ $ring }}" stroke-width="2.5" />
                @break
            @case('cargas')
                @foreach([[8, 200], [28, 150], [48, 100]] as [$y, $w])
                    <rect x="0" y="{{ $y }}" width="{{ $w }}" height="3" rx="1.5" class="{{ $line }}" />
                    <circle cx="{{ $w + 9 }}" cy="{{ $y + 1.5 }}" r="9" class="{{ $ring }}" stroke-width="2.5" />
                @endforeach
                @break
            @case('bitacora')
                @foreach([10, 22, 14, 26, 8, 20, 12, 18] as $i => $h)
                    <rect x="{{ 6 + $i * 30 }}" y="{{ 28 - $h / 2 }}" width="3" height="{{ $h }}" rx="1.5" class="{{ $line }}" />
                @endforeach
                <rect x="0" y="52" width="240" height="3" rx="1.5" class="{{ $line }}" />
                @break
            @default
                <rect x="10" y="30" width="220" height="3" rx="1.5" class="{{ $line }}" />
                @foreach([10, 65, 120, 175, 230] as $x)<circle cx="{{ $x }}" cy="31.5" r="9" class="{{ $ring }}" stroke-width="2.5" />@endforeach
        @endswitch
    </svg>
    <div class="flex flex-col gap-1.5">
        <h3 class="text-xl font-semibold leading-tight text-[var(--trz-tinta)]">{{ $title }}</h3>
        @if($text)
            <p class="max-w-[60ch] text-[15px] leading-relaxed text-[var(--trz-texto-2)]">{{ $text }}</p>
        @endif
        @isset($body)
            <p class="max-w-[60ch] text-[15px] leading-relaxed text-[var(--trz-texto-2)]">{{ $body }}</p>
        @endisset
    </div>
    @if(trim((string) $slot) !== '')
        <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
    @endif
</div>
