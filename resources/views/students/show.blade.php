@php($ringCircumference = 2 * pi() * 58)
@php($ringOffset = $ringCircumference * (1 - $progress['percent'] / 100))

<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $student['name'] }} · Trayectoria Estudiantil">
    <div class="flex flex-col gap-4 p-4 sm:p-7">
        <div class="flex items-center gap-[6px] text-sm text-[var(--text-secondary)]">
            <a href="/panel">Inicio</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <a href="/estudiantes">Estudiantes</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <span class="text-[var(--text-primary)]">{{ $student['name'] }}</span>
        </div>

        <div class="flex flex-col gap-4 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[18px] sm:flex-row sm:items-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-lg font-semibold text-[var(--color-primary-700)]">
                {{ $student['initials'] }}
            </div>
            <div class="flex flex-1 flex-col gap-[6px]">
                <h1 class="text-xl font-semibold text-[var(--text-primary)]">{{ $student['name'] }}</h1>
                <div class="flex flex-wrap gap-2">
                    @foreach([$student['code'], $student['cohort'], $student['enrollment'], $student['tutor']] as $chip)
                        <span class="rounded-full bg-[var(--surface-sunken)] px-[9px] py-1 text-xs text-[var(--text-secondary)]">{{ $chip }}</span>
                    @endforeach
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-button variant="secondary">Exportar ficha</x-button>
                <x-button variant="primary">Registrar tutoría</x-button>
            </div>
        </div>

        <div class="flex gap-1 overflow-x-auto border-b border-[var(--border-subtle)]">
            @foreach($tabs as $index => $tab)
                <span class="shrink-0 border-b-2 px-[14px] py-[10px] text-sm font-medium {{ $index === 0 ? 'border-[var(--color-primary-600)] text-[var(--color-primary-700)]' : 'border-transparent text-[var(--text-secondary)]' }}">
                    {{ $tab }}
                </span>
            @endforeach
        </div>

        <div class="flex flex-col gap-4 lg:flex-row">
            <div class="flex flex-1 flex-col gap-4">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex flex-col gap-[2px] px-4 pb-[10px] pt-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Evolución del PAPA por periodo</h2>
                        <p class="text-xs text-[var(--text-muted)]">Historia conservada por periodo, no sobrescrita · RNF-08</p>
                    </div>
                    <div class="flex items-end gap-[14px] overflow-x-auto border-t border-[var(--border-subtle)] px-4 py-4">
                        @foreach($papaChart as $bar)
                            <div class="flex min-w-[36px] flex-1 flex-col items-center justify-end gap-[7px]">
                                <span class="text-xs font-medium text-[var(--text-secondary)]">{{ $bar['value'] }}</span>
                                <div class="w-full rounded-t-[var(--radius-sm)] bg-[var(--color-primary-500)]" style="height: {{ $bar['height'] }}px"></div>
                                <span class="text-xs text-[var(--text-muted)]">{{ $bar['period'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex flex-col gap-[2px] px-4 pb-[10px] pt-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Línea de tiempo de experiencias formativas</h2>
                        <p class="text-xs text-[var(--text-muted)]">Registradas y validadas por Coordinación · RF-30, RF-33</p>
                    </div>
                    <div class="flex flex-col border-t border-[var(--border-subtle)] px-4 pb-4">
                        @foreach($timeline as $item)
                            <div class="flex gap-3 py-2">
                                <div class="flex w-7 flex-col items-center">
                                    <div class="flex h-[26px] w-[26px] items-center justify-center rounded-full bg-[var(--surface-sunken)]">
                                        <x-dynamic-component :component="'lucide-' . $item['icon']" class="h-[14px] w-[14px] text-[var(--color-primary-600)]" />
                                    </div>
                                    @if(!$loop->last)
                                        <span class="mt-1 w-[2px] flex-1 bg-[var(--border-subtle)]"></span>
                                    @endif
                                </div>
                                <div class="flex flex-1 flex-col gap-[3px] pb-2 pt-[3px]">
                                    <p class="text-sm text-[var(--text-primary)]">{{ $item['description'] }}</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-[var(--text-muted)]">{{ $item['period'] }}</span>
                                        <x-status-badge :text="$item['tag']" :tone="$item['tone']" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="px-4 pb-[10px] pt-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Procesos en curso</h2>
                    </div>
                    <div class="flex flex-col gap-[10px] border-t border-[var(--border-subtle)] p-4">
                        @foreach($processes as $process)
                            <div class="flex items-center gap-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] p-3">
                                <div class="flex flex-1 flex-col gap-[3px]">
                                    <p class="text-sm font-medium text-[var(--text-primary)]">{{ $process['title'] }}</p>
                                    <p class="text-xs text-[var(--text-muted)]">{{ $process['meta'] }}</p>
                                </div>
                                <x-status-badge :text="$process['status']" :tone="$process['tone']" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4 lg:w-[340px]">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="px-4 pb-[10px] pt-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Avance en el plan de estudios</h2>
                    </div>
                    <div class="flex flex-col items-center gap-[18px] border-t border-[var(--border-subtle)] px-4 pb-[18px] pt-1">
                        <svg width="132" height="132" viewBox="0 0 132 132" class="shrink-0">
                            <circle cx="66" cy="66" r="58" fill="none" stroke="var(--color-neutral-200)" stroke-width="12" />
                            <circle
                                cx="66" cy="66" r="58" fill="none" stroke="var(--color-primary-600)" stroke-width="12"
                                stroke-linecap="round" transform="rotate(-90 66 66)"
                                stroke-dasharray="{{ $ringCircumference }}" stroke-dashoffset="{{ $ringOffset }}"
                            />
                            <text x="66" y="62" text-anchor="middle" class="fill-[var(--text-primary)] text-[22px] font-bold">{{ $progress['percent'] }}%</text>
                            <text x="66" y="80" text-anchor="middle" class="fill-[var(--text-muted)] text-[11px]">cursado</text>
                        </svg>
                        <div class="flex w-full flex-col gap-[9px]">
                            @foreach($progress['details'] as $detail)
                                <div class="flex items-center gap-2">
                                    <span class="flex-1 text-sm text-[var(--text-secondary)]">{{ $detail['label'] }}</span>
                                    <span class="text-sm font-medium text-[var(--text-primary)]">{{ $detail['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex flex-col gap-[2px] px-4 pb-[10px] pt-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Últimas tutorías</h2>
                        <p class="text-xs text-[var(--text-muted)]">Visibles para tutores y Coordinación</p>
                    </div>
                    <div class="flex flex-col gap-[10px] border-t border-[var(--border-subtle)] p-4">
                        @foreach($tutorials as $tutorial)
                            <div class="flex flex-col gap-1 rounded-[var(--radius-md)] border border-[var(--border-subtle)] p-[11px]">
                                <span class="text-xs text-[var(--text-muted)]">{{ $tutorial['date'] }}</span>
                                <span class="text-sm font-medium text-[var(--text-primary)]">{{ $tutorial['reason'] }}</span>
                                <span class="text-xs text-[var(--text-secondary)]">{{ $tutorial['note'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-[11px] rounded-[var(--radius-lg)] border border-[var(--color-restricted)] bg-[var(--color-restricted-bg)] p-[14px]">
                    <x-lucide-lock class="h-[17px] w-[17px] shrink-0 text-[var(--color-restricted)]" />
                    <div class="flex flex-col gap-[3px]">
                        <p class="text-sm font-semibold text-[var(--color-restricted)]">Información restringida</p>
                        <p class="text-xs text-[var(--text-secondary)]">El perfil psicosocial y de salud solo es visible para Coordinación. Cada consulta queda auditada (RNF-05, RNF-07).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
