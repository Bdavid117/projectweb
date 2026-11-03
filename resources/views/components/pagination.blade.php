@props(['paginator', 'noun' => 'registros'])

@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = $paginator->total() > 0 ? range(max(1, $current - 2), min($last, $current + 2)) : [];
    $btn = 'inline-flex min-h-[var(--control-height)] items-center justify-center rounded-[var(--radius-md)] border-[1.5px] px-[18px] text-[15px] font-semibold';
    $on = $btn.' border-[var(--trz-tinta)] bg-[var(--trz-superficie)] text-[var(--trz-tinta)] hover:bg-[var(--trz-hilo-fondo)]';
    $off = $btn.' border-[var(--trz-linea)] text-[var(--trz-texto-3)]';
    $num = 'hidden min-h-[var(--control-height)] min-w-[var(--control-height)] items-center justify-center rounded-[var(--radius-md)] px-2 text-[15px] font-semibold sm:inline-flex';
@endphp

<nav aria-label="Paginación" class="flex flex-wrap items-center gap-x-2 gap-y-3">
    <span class="flex-1 text-sm text-[var(--trz-texto-2)]">
        @if($paginator->total() === 0)
            Sin resultados
        @else
            Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }} {{ $noun }}
        @endif
    </span>
    @if($paginator->hasPages())
        @if($paginator->onFirstPage())
            <span class="{{ $off }}">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $on }}">Anterior</a>
        @endif
        @foreach($pages as $page)
            @if($page === $current)
                <span aria-current="page" class="{{ $num }} bg-[var(--trz-hilo)] text-[var(--trz-sobre-hilo)]">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" class="{{ $num }} text-[var(--trz-texto-2)] hover:bg-[var(--trz-hilo-fondo)]">{{ $page }}</a>
            @endif
        @endforeach
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $on }}">Siguiente</a>
        @else
            <span class="{{ $off }}">Siguiente</span>
        @endif
    @endif
</nav>
