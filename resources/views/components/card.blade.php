@props(['title' => null, 'titleClass' => 'text-lg'])

{{-- Contenedor con borde fino. Úsese solo cuando la agrupación tiene una función real (formularios, avisos). --}}
<div {{ $attributes->merge(['class' => 'rounded-[var(--radius-xl)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)]']) }}>
    @if(!empty($title))
        <div class="border-b border-[var(--trz-linea)] px-5 py-4">
            <h3 class="font-semibold text-[var(--trz-tinta)] {{ $titleClass }}">{{ $title }}</h3>
        </div>
    @endif

    {{ $slot }}
</div>
