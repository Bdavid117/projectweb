@props(['steps', 'progress' => null])

{{-- Recorrido del estudiante. Escritorio (desde 1100 px): un hilo horizontal con el marcador «Hoy» en su avance del plan.
     Tablet y móvil: el mismo recorrido en vertical. Las posiciones (en %) son un dato por nodo, por eso llevan `style`. --}}
@php
    $at = ['ingreso' => 0, 'practica' => 62, 'trabajo' => 80, 'grado' => 100];
    $today = $progress === null ? null : max(1, min(99, $progress));
@endphp
<div {{ $attributes }}>
    <div class="relative mx-3 hidden h-[150px] min-[1100px]:block">
        <span class="absolute left-0 right-0 top-[38px] h-1 rounded-full bg-[var(--trz-linea)]" aria-hidden="true"></span>
        @if($today !== null)
            <span class="absolute left-0 top-[38px] h-1 rounded-full bg-[var(--trz-hilo)]" style="width: {{ $today }}%" aria-hidden="true"></span>
            <span class="absolute top-0 -translate-x-1/2 whitespace-nowrap text-sm font-bold text-[var(--trz-hilo-texto)]" style="left: {{ $today }}%">Hoy · {{ $progress }} % del plan</span>
            <span class="absolute top-[28px] h-6 w-6 -translate-x-1/2 rounded-full border-4 border-[var(--trz-hilo)] bg-[var(--trz-superficie)]" style="left: {{ $today }}%" aria-hidden="true"></span>
        @endif

        @foreach($steps as $step)
            @php
                $x = $at[$step['key']] ?? 50;
                $align = $loop->first ? 'items-start text-left' : ($loop->last ? 'items-end text-right' : 'items-center text-center');
                $shift = $loop->first ? '' : ($loop->last ? '-translate-x-full' : '-translate-x-1/2');
                $filled = $step['state'] === 'done' || ($step['key'] === 'practica' && $step['state'] === 'current') || $step['key'] === 'ingreso';
            @endphp
            <div class="absolute top-[30px] flex flex-col gap-2 {{ $loop->last ? 'w-[100px]' : 'w-[130px]' }} {{ $align }} {{ $shift }}" style="left: {{ $x }}%">
                @if($step['key'] === 'practica')
                    <span class="mt-[3px] block h-3.5 w-3.5 rotate-45 rounded-[2px] border-2 border-[var(--trz-hito-texto)] {{ $filled ? 'bg-[var(--trz-hito)]' : 'bg-[var(--trz-superficie)]' }}" aria-hidden="true"></span>
                @else
                    <span class="block h-5 w-5 rounded-full {{ $filled ? 'bg-[var(--trz-hilo)]' : ($step['state'] === 'current' ? 'border-[3px] border-[var(--trz-hilo)] bg-[var(--trz-superficie)]' : 'border-[3px] border-[var(--trz-borde-control)] bg-[var(--trz-superficie)]') }}" aria-hidden="true"></span>
                @endif
                <div class="mt-3 flex flex-col gap-0.5 {{ $align }}">
                    <span class="text-base font-semibold leading-snug text-[var(--trz-tinta)]">{{ $step['title'] }}</span>
                    <span class="text-sm leading-snug text-[var(--trz-texto-2)]">{{ $step['detail'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <x-thread class="min-[1100px]:hidden" :steps="collect($steps)->map(fn ($s) => ['title' => $s['title'], 'detail' => $s['detail'], 'done' => $s['state'] !== 'pending'])->all()" />
</div>
