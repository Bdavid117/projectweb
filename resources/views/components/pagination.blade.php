@props(['paginator', 'noun' => 'registros'])

@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = $paginator->total() > 0 ? range(max(1, $current - 2), min($last, $current + 2)) : [];
@endphp

<nav aria-label="Paginación" class="flex flex-wrap items-center gap-x-2 gap-y-2">
    <span class="flex-1 text-sm text-[var(--text-secondary)]">
        @if($paginator->total() === 0)
            Sin resultados
        @else
            Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }} {{ $noun }}
        @endif
    </span>
    @if($paginator->hasPages())
        @if($paginator->onFirstPage())
            <span class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-muted)]">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)] hover:bg-[var(--surface-sunken)]">Anterior</a>
        @endif
        @foreach($pages as $page)
            @if($page === $current)
                <span aria-current="page" class="rounded-[var(--radius-md)] bg-[var(--color-primary-600)] px-3 py-[7px] text-sm text-white">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)] hover:bg-[var(--surface-sunken)]">{{ $page }}</a>
            @endif
        @endforeach
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)] hover:bg-[var(--surface-sunken)]">Siguiente</a>
        @else
            <span class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-muted)]">Siguiente</span>
        @endif
    @endif
</nav>
