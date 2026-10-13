<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mis asesorías · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Mis asesorías</h1>
            <p class="text-sm text-[var(--text-secondary)]">{{ $total }} estudiantes asignados en {{ $termLabel }} · RF-35</p>
        </div>

        <form method="GET" action="{{ route('advisories.index') }}" class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[14px] lg:flex-row lg:items-center">
            <div class="flex h-[var(--control-height)] flex-1 items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3">
                <x-lucide-search class="h-4 w-4 text-[var(--color-neutral-400)]" />
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por nombre o código" class="w-full bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
            </div>
            <x-filter-select name="alertas" all="Alertas: todas" :options="$alertOptions" :selected="$filters['alertas']" />
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </form>

        <div class="hidden min-w-0 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] sm:flex">
            <div class="min-w-0 overflow-x-auto">
                <div class="flex min-w-[760px] gap-3 px-4 py-[11px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                    <span class="flex-1">ESTUDIANTE</span>
                    <span class="w-[90px]">COHORTE</span>
                    <span class="w-[140px]">ÚLTIMA TUTORÍA</span>
                    <span class="w-16">PAPA</span>
                    <span class="w-[170px]">ALERTAS</span>
                    <span class="w-[150px]"></span>
                </div>
                @forelse($students as $row)
                    <div class="flex min-w-[760px] items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm">
                        <a href="{{ $row['profileUrl'] }}" class="flex-1 font-semibold text-[var(--text-primary)]">{{ $row['name'] }}</a>
                        <span class="w-[90px] text-[var(--text-secondary)]">{{ $row['cohort'] }}</span>
                        <span class="w-[140px] text-[var(--text-secondary)]">{{ $row['last'] }}</span>
                        <span class="w-16 {{ $row['papaLow'] ? 'font-semibold text-[var(--color-danger)]' : 'text-[var(--text-secondary)]' }}">{{ $row['papa'] }}</span>
                        <span class="w-[170px]"><x-status-badge :text="$row['alert']" :tone="$row['tone']" /></span>
                        <a href="{{ $row['registerUrl'] }}" class="w-[150px] text-sm font-semibold text-[var(--color-primary-600)]">Registrar tutoría</a>
                    </div>
                @empty
                    <div class="border-t border-[var(--border-subtle)] px-4 py-8 text-center text-sm text-[var(--text-muted)]">No hay estudiantes que coincidan con los filtros.</div>
                @endforelse
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:hidden">
            @forelse($students as $row)
                <div class="flex flex-col gap-2 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-3">
                    <div class="flex items-center gap-2">
                        <a href="{{ $row['profileUrl'] }}" class="flex-1 text-sm font-medium text-[var(--text-primary)]">{{ $row['name'] }}</a>
                        <x-status-badge :text="$row['alert']" :tone="$row['tone']" />
                    </div>
                    <p class="text-xs text-[var(--text-secondary)]">{{ $row['cohort'] }} · PAPA {{ $row['papa'] }} · última tutoría {{ $row['last'] }}</p>
                    <a href="{{ $row['registerUrl'] }}" class="text-sm font-semibold text-[var(--color-primary-600)]">Registrar tutoría</a>
                </div>
            @empty
                <p class="rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-4 text-center text-sm text-[var(--text-muted)]">No hay estudiantes que coincidan con los filtros.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$students" noun="estudiantes asignados" />
    </div>
</x-layouts.app>
