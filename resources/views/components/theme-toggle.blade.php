{{-- Selector de tema: automático (sigue al sistema), claro u oscuro. La preferencia queda en este navegador.
     Icono sobre la etiqueta y mismo grosor de letra en los tres: así «Automático» cabe en un tercio de 250 px y la
     opción activa no cambia de ancho al elegirla. La activa se distingue por el fondo elevado y el contorno, no solo por color. --}}
<div x-data="theme" role="radiogroup" aria-label="Tema de color" class="grid grid-cols-3 gap-1 rounded-[var(--radius-md)] bg-[var(--trz-papel)] p-1">
    @foreach(['auto' => ['Automático', 'monitor'], 'light' => ['Claro', 'sun'], 'dark' => ['Oscuro', 'moon']] as $value => [$label, $icon])
        <button type="button" role="radio" :aria-checked="mode === '{{ $value }}'" @click="set('{{ $value }}')"
            :class="mode === '{{ $value }}' ? 'bg-[var(--trz-superficie)] text-[var(--trz-tinta)] shadow-sm ring-1 ring-inset ring-[color:var(--trz-borde-control)]' : 'text-[var(--trz-texto-2)] hover:text-[var(--trz-tinta)]'"
            class="flex min-h-14 min-w-0 flex-col items-center justify-center gap-1 rounded-[var(--radius-sm)] px-1 py-1.5 text-[13px] font-medium leading-tight">
            <x-ui-icon :name="$icon" class="h-[18px] w-[18px]" />
            <span>{{ $label }}</span>
        </button>
    @endforeach
</div>
