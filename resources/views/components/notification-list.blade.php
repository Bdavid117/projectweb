@props(['items'])

{{-- Cada aviso lleva la forma de su corriente: círculo (académico), rombo (experiencias), cuadrado (acompañamiento);
     las alertas, un anillo. La forma acompaña al texto, no lo reemplaza. --}}
@forelse($items as $item)
    @php
        $shape = match ($item['icon']) {
            'sparkles' => 'rotate-45 rounded-[2px] border-[1.5px] border-[var(--trz-hito-texto)] bg-[var(--trz-hito)] h-2.5 w-2.5 mt-[7px] ml-[3px]',
            'user-plus', 'users', 'clipboard-list' => 'rounded-[3px] bg-[var(--trz-brote)] h-3.5 w-3.5 mt-[5px]',
            'triangle-alert' => 'rounded-full border-[3px] border-[var(--trz-alerta)] h-3.5 w-3.5 mt-[5px]',
            default => 'rounded-full bg-[var(--trz-hilo)] h-3.5 w-3.5 mt-[5px]',
        };
    @endphp
    <a href="{{ $item['url'] }}" class="flex items-start gap-3.5 border-b border-[var(--trz-linea)] px-[18px] py-3.5 last:border-b-0 hover:bg-[var(--trz-hilo-fondo)]">
        <span class="inline-block shrink-0 {{ $shape }}" aria-hidden="true"></span>
        <span class="flex min-w-0 flex-col gap-[3px]">
            <span class="text-[15px] font-semibold leading-snug text-[var(--trz-tinta)]">{{ $item['text'] }}</span>
            <span class="text-[13px] text-[var(--trz-texto-2)]">{{ $item['meta'] }}</span>
        </span>
    </a>
@empty
    <div class="flex items-center gap-3 px-[18px] py-7">
        <x-ui-icon name="bell" class="h-[22px] w-[22px] shrink-0 text-[var(--trz-texto-3)]" />
        <p class="text-[15px] text-[var(--trz-texto-2)]">No tiene notificaciones pendientes.</p>
    </div>
@endforelse
