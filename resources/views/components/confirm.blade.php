@props(['title', 'text', 'confirm', 'action', 'method' => 'DELETE', 'trigger' => 'Eliminar', 'triggerIcon' => 'trash-2', 'triggerVariant' => 'danger'])

{{-- Confirmación de lo irreversible. El disparador abre un <dialog> nativo (atrapa el foco y cierra con Escape);
     el foco inicial queda en «Cancelar». La acción real es un formulario POST con el método indicado. --}}
@php($dialogId = 'confirmar-'.\Illuminate\Support\Str::random(6))
<div x-data class="inline-flex">
    <x-button :variant="$triggerVariant" :icon="$triggerIcon" @click="$refs.dialogo.showModal()" aria-haspopup="dialog">{{ $trigger }}</x-button>

    <dialog x-ref="dialogo" aria-labelledby="{{ $dialogId }}" class="m-auto w-[min(560px,calc(100vw-32px))] rounded-[var(--radius-xl)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)] p-0 text-[var(--trz-tinta)] shadow-[var(--shadow-menu)] backdrop:bg-[var(--trz-velo)]">
        <form method="POST" action="{{ $action }}" class="flex flex-col gap-4 p-7">
            @csrf
            @method($method)
            <div class="flex items-center gap-3">
                <x-ui-icon name="triangle-alert" class="h-6 w-6 shrink-0 text-[var(--trz-alerta)]" />
                <h2 id="{{ $dialogId }}" class="text-[22px] sm:text-2xl font-semibold leading-tight">{{ $title }}</h2>
            </div>
            <p class="text-base leading-relaxed text-[var(--trz-texto-2)]">{{ $text }}</p>
            <div class="flex flex-wrap justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" autofocus @click="$refs.dialogo.close()">Cancelar</x-button>
                <x-button variant="danger" type="submit">{{ $confirm }}</x-button>
            </div>
        </form>
    </dialog>
</div>
