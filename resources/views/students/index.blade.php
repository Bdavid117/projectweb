<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Estudiantes · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Estudiantes</h1>
                <p class="text-sm text-[var(--text-secondary)]">{{ $activeCount }} estudiantes activos en el programa · RF-07</p>
            </div>
            <div class="flex gap-3">
                <x-button variant="subtle" icon="download">Exportar</x-button>
                <x-button variant="primary" icon="upload">Cargar Excel</x-button>
            </div>
        </div>

        <form method="GET" action="{{ route('students.index') }}" class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[14px] lg:flex-row lg:items-center">
            <div class="flex h-[var(--control-height)] flex-1 items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3">
                <x-lucide-search class="h-4 w-4 text-[var(--color-neutral-400)]" />
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por nombre, código o documento" class="w-full bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
            </div>
            <x-filter-select name="cohorte" all="Cohorte: todas" :options="$cohortOptions" :selected="$filters['cohorte']" />
            <x-filter-select name="estado" all="Estado: todos" :options="$statusOptions" :selected="$filters['estado']" />
            <x-filter-select name="tutor" all="Tutor: todos" :options="$tutorOptions" :selected="$filters['tutor']" />
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </form>

        <!-- Desktop table (hidden on mobile) -->
        <div class="hidden min-w-0 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] sm:flex">
            <div class="min-w-0 overflow-x-auto">
                <div class="flex min-w-[620px] gap-3 px-4 py-[11px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                    <span class="w-24">CÓDIGO</span>
                    <span class="flex-1">ESTUDIANTE</span>
                    <span class="w-[92px]">COHORTE</span>
                    <span class="w-16">PAPA</span>
                    <span class="w-[150px]">AVANCE</span>
                    <span class="w-[110px]">ESTADO</span>
                    <span class="w-[76px]"></span>
                </div>
                @forelse($students as $student)
                    <div class="flex min-w-[620px] items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm">
                        <span class="w-24 text-[var(--text-secondary)]">{{ $student['code'] }}</span>
                        <span class="flex-1 text-[var(--text-primary)]">{{ $student['name'] }}</span>
                        <span class="w-[92px] text-[var(--text-secondary)]">{{ $student['cohort'] }}</span>
                        <span class="w-16 {{ $student['papaLow'] ? 'font-semibold text-[var(--color-danger)]' : 'text-[var(--text-secondary)]' }}">{{ $student['papa'] }}</span>
                        <span class="flex w-[150px] items-center gap-2">
                            <span class="h-[7px] flex-1 rounded-full bg-[var(--surface-sunken)]">
                                <span class="block h-[7px] rounded-full bg-[var(--color-primary-500)]" style="width: {{ $student['advance'] }}%"></span>
                            </span>
                            <span class="text-xs text-[var(--text-secondary)]">{{ $student['advance'] }}%</span>
                        </span>
                        <span class="w-[110px]"><x-status-badge :text="$student['status']" :tone="$student['tone']" /></span>
                        <a href="{{ route('students.show', $student['id']) }}" class="w-[76px] text-sm font-medium text-[var(--color-primary-600)]">Ver ficha</a>
                    </div>
                @empty
                    <div class="border-t border-[var(--border-subtle)] px-4 py-8 text-center text-sm text-[var(--text-muted)]">No hay estudiantes que coincidan con los filtros.</div>
                @endforelse
            </div>
        </div>

        <!-- Mobile cards (hidden on desktop), per the 360px mockup -->
        <div class="flex flex-col gap-3 sm:hidden">
            @forelse($students as $student)
                <a href="{{ route('students.show', $student['id']) }}" class="flex flex-col gap-2 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-3">
                    <div class="flex items-center gap-2">
                        <span class="flex-1 text-sm font-medium text-[var(--text-primary)]">{{ $student['name'] }}</span>
                        <x-status-badge :text="$student['status']" :tone="$student['tone']" />
                    </div>
                    <p class="text-xs text-[var(--text-secondary)]">{{ $student['code'] }} · PAPA {{ $student['papa'] }} · avance {{ $student['advance'] }}%</p>
                    <span class="block h-[6px] rounded-full bg-[var(--surface-sunken)]">
                        <span class="block h-[6px] rounded-full bg-[var(--color-primary-500)]" style="width: {{ $student['advance'] }}%"></span>
                    </span>
                </a>
            @empty
                <p class="rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-4 text-center text-sm text-[var(--text-muted)]">No hay estudiantes que coincidan con los filtros.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$students" noun="estudiantes" />
    </div>
</x-layouts.app>
