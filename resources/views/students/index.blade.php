<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Estudiantes · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Estudiantes</h1>
                <p class="text-sm text-[var(--text-secondary)]">312 estudiantes activos en el programa · RF-07</p>
            </div>
            <div class="flex gap-3">
                <x-button variant="subtle" icon="download">Exportar</x-button>
                <x-button variant="primary" icon="upload">Cargar Excel</x-button>
            </div>
        </div>

        <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[14px] sm:flex-row sm:items-center">
            <div class="flex h-[var(--control-height)] flex-1 items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3">
                <x-lucide-search class="h-4 w-4 text-[var(--color-neutral-400)]" />
                <input type="text" placeholder="Buscar por nombre, código o documento" class="w-full bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
            </div>
            <div class="flex h-[var(--control-height)] items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 text-sm text-[var(--text-secondary)]">
                Cohorte: todas <x-lucide-chevron-down class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
            </div>
            <div class="flex h-[var(--control-height)] items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 text-sm text-[var(--text-secondary)]">
                Estado: activo <x-lucide-chevron-down class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
            </div>
            <div class="flex h-[var(--control-height)] items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 text-sm text-[var(--text-secondary)]">
                Tutor: todos <x-lucide-chevron-down class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
            </div>
            <x-button variant="secondary">Filtrar</x-button>
        </div>

        <!-- Desktop table (hidden on mobile) -->
        <div class="hidden flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] sm:flex">
            <div class="flex gap-3 px-4 py-[11px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                <span class="w-24">CÓDIGO</span>
                <span class="flex-1">ESTUDIANTE</span>
                <span class="w-[92px]">COHORTE</span>
                <span class="w-16">PAPA</span>
                <span class="w-[150px]">AVANCE</span>
                <span class="w-[110px]">ESTADO</span>
                <span class="w-[76px]"></span>
            </div>
            @foreach($students as $student)
                <div class="flex items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm">
                    <span class="w-24 text-[var(--text-secondary)]">{{ $student['code'] }}</span>
                    <span class="flex-1 text-[var(--text-primary)]">{{ $student['name'] }}</span>
                    <span class="w-[92px] text-[var(--text-secondary)]">{{ $student['cohort'] }}</span>
                    <span class="w-16 text-[var(--text-secondary)]">{{ $student['papa'] }}</span>
                    <span class="flex w-[150px] items-center gap-2">
                        <span class="h-[7px] flex-1 rounded-full bg-[var(--surface-sunken)]">
                            <span class="block h-[7px] rounded-full bg-[var(--color-primary-500)]" style="width: {{ $student['advance'] }}%"></span>
                        </span>
                        <span class="text-xs text-[var(--text-secondary)]">{{ $student['advance'] }}%</span>
                    </span>
                    <span class="w-[110px]"><x-status-badge :text="$student['status']" :tone="$student['tone']" /></span>
                    <a href="/estudiantes/{{ $student['code'] }}" class="w-[76px] text-sm font-medium text-[var(--color-primary-600)]">Ver ficha</a>
                </div>
            @endforeach
        </div>

        <!-- Mobile cards (hidden on desktop), per the 360px mockup -->
        <div class="flex flex-col gap-3 sm:hidden">
            @foreach($students as $student)
                <a href="/estudiantes/{{ $student['code'] }}" class="flex flex-col gap-2 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-3">
                    <div class="flex items-center gap-2">
                        <span class="flex-1 text-sm font-medium text-[var(--text-primary)]">{{ $student['name'] }}</span>
                        <x-status-badge :text="$student['status']" :tone="$student['tone']" />
                    </div>
                    <p class="text-xs text-[var(--text-secondary)]">{{ $student['code'] }} · PAPA {{ $student['papa'] }} · avance {{ $student['advance'] }}%</p>
                    <span class="block h-[6px] rounded-full bg-[var(--surface-sunken)]">
                        <span class="block h-[6px] rounded-full bg-[var(--color-primary-500)]" style="width: {{ $student['advance'] }}%"></span>
                    </span>
                </a>
            @endforeach
        </div>

        <div class="hidden items-center gap-2 sm:flex">
            <span class="flex-1 text-sm text-[var(--text-secondary)]">Mostrando 1–8 de 312 estudiantes</span>
            <span class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)]">Anterior</span>
            <span class="rounded-[var(--radius-md)] bg-[var(--color-primary-600)] px-3 py-[7px] text-sm text-white">1</span>
            <span class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)]">2</span>
            <span class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)]">3</span>
            <span class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)]">Siguiente</span>
        </div>
    </div>
</x-layouts.app>
