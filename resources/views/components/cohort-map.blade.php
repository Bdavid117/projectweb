@props(['rows', 'reading' => null])

{{-- Mapa de avance: una fila por cohorte, un punto por estudiante a la altura (horizontal) de su avance del plan.
     La posición de cada punto es un dato por fila, por eso es el único lugar con `style` (left/top en %). --}}
@php
    $dotShift = fn (string $seed, int $i) => (crc32($seed.$i) % 17) - 8;
@endphp
<figure {{ $attributes->merge(['class' => 'flex flex-col gap-3']) }}>
    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-[var(--trz-texto-2)]">
        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[var(--trz-hilo)]" aria-hidden="true"></span>Estudiante</span>
        <span class="inline-flex items-center gap-2"><span class="h-4 w-4 rounded-full border-[2.5px] border-[var(--trz-alerta)]" aria-hidden="true"></span>Con alerta abierta</span>
    </div>

    <div>
        <div role="img" aria-label="Avance del plan de cada estudiante, agrupado por cohorte">
            @foreach($rows as $row)
                <div class="grid grid-cols-[52px_1fr] sm:grid-cols-[64px_1fr] items-stretch">
                    <span class="flex items-center text-sm text-[var(--trz-texto-2)]">{{ $row['label'] }}</span>
                    <div class="relative h-[46px] border-b border-[var(--trz-linea)] {{ $loop->first ? 'border-t' : '' }}
                        bg-[linear-gradient(to_right,var(--trz-linea)_1px,transparent_1px)] bg-[length:25%_100%] border-x">
                        @foreach($row['dots'] as $i => $dot)
                            <span title="{{ $dot['label'] }}" class="absolute -ml-[5px] -mt-[5px] h-2.5 w-2.5 rounded-full bg-[var(--trz-hilo)] {{ $dot['alert'] ? 'outline outline-[2.5px] outline-offset-[3px] outline-[color:var(--trz-alerta)]' : '' }}"
                                style="left: {{ $dot['pct'] }}%; top: calc(50% + {{ $dotShift($row['label'].$dot['label'], $i) }}px)"></span>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div class="grid grid-cols-[52px_1fr] sm:grid-cols-[64px_1fr]">
                <span></span>
                <div class="mt-2 flex justify-between text-[13px] text-[var(--trz-texto-2)]">
                    @foreach(['0 %', '25 %', '50 %', '75 %', '100 %'] as $tick)
                        <span>{{ $tick }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <ul class="sr-only">
        @foreach($rows as $row)
            @foreach($row['dots'] as $dot)
                <li>Cohorte {{ $row['label'] }}: {{ $dot['label'] }}</li>
            @endforeach
        @endforeach
    </ul>

    @if($reading)
        <figcaption class="text-base leading-relaxed text-[var(--trz-tinta)]">{{ $reading }}</figcaption>
    @endif
</figure>
