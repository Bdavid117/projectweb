@props(['steps'])

{{-- Recorrido vertical: cada paso es [titulo, detalle, hecho]. El tramo hacia un paso se pinta en azul si ese paso ya ocurrió. --}}
<ol {{ $attributes->merge(['class' => 'flex flex-col']) }}>
    @foreach($steps as $i => $step)
        @php
            $done = $step['done'];
            $next = $steps[$i + 1] ?? null;
        @endphp
        <li class="flex gap-3.5" @if(! $done) aria-label="{{ $step['title'] }} (pendiente)" @endif>
            <div class="flex w-[17px] shrink-0 flex-col items-center">
                <span class="mt-[3px] block h-[17px] w-[17px] shrink-0 rounded-full {{ $done ? 'bg-[var(--trz-hilo)]' : 'border-[2.5px] border-[var(--trz-borde-control)] bg-[var(--trz-superficie)]' }}" aria-hidden="true"></span>
                @if($next)
                    <span class="w-[3px] flex-1 rounded-full {{ $next['done'] ? 'bg-[var(--trz-hilo)]' : 'bg-[var(--trz-linea)]' }}" aria-hidden="true"></span>
                @endif
            </div>
            <div class="flex flex-col gap-0.5 {{ $next ? 'pb-6' : '' }}">
                <p class="text-[17px] font-semibold leading-snug {{ $done ? 'text-[var(--trz-tinta)]' : 'text-[var(--trz-texto-2)]' }}">{{ $step['title'] }}</p>
                @if(! empty($step['detail']))
                    <p class="text-sm text-[var(--trz-texto-2)]">{{ $step['detail'] }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
