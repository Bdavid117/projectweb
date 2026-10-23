<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $title }} · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">{{ $title }}</h1>
                <p class="text-sm text-[var(--text-secondary)]">{{ $subtitle }}</p>
            </div>
            @if($canCreate)
                <x-button variant="primary" icon="file-plus" :href="route('requests.create')">Nueva solicitud</x-button>
            @endif
        </div>

        <form method="GET" action="{{ route('requests.index') }}" class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[14px] lg:flex-row lg:items-center">
            @unless($isStudent)
                <div class="flex h-[var(--control-height)] flex-1 items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3">
                    <x-ui-icon name="search" class="h-4 w-4 text-[var(--color-neutral-400)]" />
                    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por estudiante o código" class="w-full bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
                </div>
            @endunless
            <x-filter-select name="tipo" all="Tipo: todos" :options="$typeOptions" :selected="$filters['tipo']" />
            <x-filter-select name="estado" all="Estado: todos" :options="$statusOptions" :selected="$filters['estado']" />
            <x-filter-select name="periodo" all="Periodo: todos" :options="$termOptions" :selected="$filters['periodo']" />
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </form>

        <div class="hidden min-w-0 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] sm:flex">
            <div class="min-w-0 overflow-x-auto">
                <div class="flex min-w-[720px] gap-3 px-4 py-[11px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                    <span class="w-[100px]">RADICADA</span>
                    @unless($isStudent)
                        <span class="flex-1">ESTUDIANTE</span>
                    @endunless
                    <span class="{{ $isStudent ? 'flex-1' : 'w-[230px]' }}">TIPO</span>
                    <span class="w-20">PERIODO</span>
                    <span class="w-[130px]">ESTADO</span>
                    <span class="w-[100px]"></span>
                </div>
                @forelse($requests as $row)
                    <div class="flex min-w-[720px] items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm">
                        <span class="w-[100px] text-[var(--text-muted)]">{{ $row['date'] }}</span>
                        @unless($isStudent)
                            <span class="flex-1 font-semibold text-[var(--text-primary)]">{{ $row['student'] }}</span>
                        @endunless
                        <span class="{{ $isStudent ? 'flex-1' : 'w-[230px]' }} text-[var(--text-secondary)]">{{ $row['type'] }}</span>
                        <span class="w-20 text-[var(--text-secondary)]">{{ $row['period'] }}</span>
                        <span class="w-[130px]"><x-status-badge :text="$row['status']" :tone="$row['tone']" /></span>
                        <a href="{{ $row['url'] }}" class="w-[100px] text-sm font-semibold text-[var(--color-primary-600)]">Ver detalle</a>
                    </div>
                @empty
                    <div class="border-t border-[var(--border-subtle)] px-4 py-8 text-center text-sm text-[var(--text-muted)]">No hay solicitudes que coincidan con los filtros.</div>
                @endforelse
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:hidden">
            @forelse($requests as $row)
                <a href="{{ $row['url'] }}" class="flex flex-col gap-2 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-3">
                    <div class="flex items-center gap-2">
                        <span class="flex-1 text-sm font-medium text-[var(--text-primary)]">{{ $row['type'] }}</span>
                        <x-status-badge :text="$row['status']" :tone="$row['tone']" />
                    </div>
                    <p class="text-xs text-[var(--text-secondary)]">{{ $row['date'] }}@unless($isStudent) · {{ $row['student'] }}@endunless · {{ $row['period'] }}</p>
                </a>
            @empty
                <p class="rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-4 text-center text-sm text-[var(--text-muted)]">No hay solicitudes que coincidan con los filtros.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$requests" noun="solicitudes" />
    </div>
</x-layouts.app>
