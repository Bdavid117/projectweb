@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => null])

{{-- Encabezado de pantalla: volver (opcional), titular grande y acciones a la derecha (slot `actions`) o al pie en móvil. --}}
<header class="flex flex-col gap-5">
    @if($back)
        <x-back-link :href="$back">{{ $backLabel }}</x-back-link>
    @endif
    <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between md:gap-12">
        <div class="flex min-w-0 flex-col gap-2.5">
            <h1 class="text-[28px] font-semibold leading-[1.1] text-[var(--trz-tinta)] sm:text-[42px] sm:leading-[1.08]">{{ $title }}</h1>
            @if($subtitle)
                <p class="max-w-[68ch] text-base leading-[1.45] text-[var(--trz-texto-2)] sm:text-lg">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-3">{{ $actions }}</div>
        @endisset
    </div>
</header>
