<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Panel del programa · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Panel del programa</h1>
                <p class="text-sm text-[var(--text-secondary)]">Datos al 20/09/2026 · última sincronización con la API hace 2 horas</p>
            </div>
            <div class="flex gap-3">
                <x-button variant="subtle" icon="download">Exportar</x-button>
                <x-button variant="primary" icon="upload">Cargar Excel</x-button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach($indicators as $indicator)
                <x-indicator-card :label="$indicator['label']" :value="$indicator['value']" :detail="$indicator['detail']" :value-class="$indicator['valueClass'] ?? null" />
            @endforeach
        </div>

        <div class="flex flex-col gap-4 lg:flex-row">
            <div class="flex flex-1 flex-col gap-4">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex items-center gap-3 px-4 py-[14px]">
                        <div class="flex flex-1 flex-col gap-[2px]">
                            <h2 class="text-sm font-semibold text-[var(--text-primary)]">Situaciones que requieren atención</h2>
                            <p class="text-xs text-[var(--text-muted)]">Detectadas por reglas del programa · RF-37</p>
                        </div>
                        <a href="/estudiantes" class="text-sm font-medium text-[var(--color-primary-600)]">Ver todas</a>
                    </div>
                    <div class="overflow-x-auto">
                        <div class="flex min-w-[620px] gap-3 border-t border-[var(--border-subtle)] px-4 py-[9px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                            <span class="w-[210px]">ESTUDIANTE</span>
                            <span class="flex-1">SITUACIÓN</span>
                            <span class="w-24">DETECTADA</span>
                            <span class="w-[118px]">ESTADO</span>
                            <span class="w-[70px]"></span>
                        </div>
                        @foreach($attentionRows as $row)
                            <div class="flex min-w-[620px] items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-3 text-sm">
                                <span class="w-[210px] text-[var(--text-primary)]">{{ $row['name'] }}</span>
                                <span class="flex-1 text-[var(--text-secondary)]">{{ $row['issue'] }}</span>
                                <span class="w-24 text-[var(--text-secondary)]">{{ $row['date'] }}</span>
                                <span class="w-[118px]"><x-status-badge :text="$row['status']" :tone="$row['tone']" /></span>
                                <a href="/estudiantes" class="w-[70px] text-sm font-medium text-[var(--color-primary-600)]">Ver ficha</a>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex flex-col gap-[2px] px-4 py-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Avance en créditos por cohorte</h2>
                        <p class="text-xs text-[var(--text-muted)]">Porcentaje medio del plan de estudios cursado</p>
                    </div>
                    <div class="flex items-end gap-[18px] overflow-x-auto border-t border-[var(--border-subtle)] px-4 py-5">
                        @foreach($cohortChart as $bar)
                            <div class="flex min-w-[28px] flex-1 flex-col items-center justify-end gap-2">
                                <span class="text-xs font-medium text-[var(--text-secondary)]">{{ $bar['value'] }}</span>
                                <div class="w-full rounded-t-[var(--radius-sm)] bg-[var(--color-primary-500)]" style="height: {{ $bar['height'] }}px"></div>
                                <span class="text-xs text-[var(--text-muted)]">{{ $bar['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4 lg:w-[360px]">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex flex-col gap-[2px] px-4 py-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Actividad reciente</h2>
                        <p class="text-xs text-[var(--text-muted)]">Últimos registros del programa</p>
                    </div>
                    <div class="flex flex-col gap-1 border-t border-[var(--border-subtle)] py-1">
                        @foreach($recentActivity as $activity)
                            <div class="flex items-center gap-[11px] px-4 py-[10px]">
                                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[var(--surface-sunken)]">
                                    <x-dynamic-component :component="'lucide-' . $activity['icon']" class="h-[15px] w-[15px] text-[var(--color-primary-600)]" />
                                </div>
                                <div class="flex flex-1 flex-col gap-[2px]">
                                    <p class="text-sm text-[var(--text-primary)]">{{ $activity['description'] }}</p>
                                    <p class="text-xs text-[var(--text-muted)]">{{ $activity['meta'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="px-4 py-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Accesos rápidos</h2>
                    </div>
                    <div class="flex flex-col gap-2 border-t border-[var(--border-subtle)] p-4">
                        @foreach($quickLinks as $link)
                            <button type="button" class="flex w-full items-center gap-[9px] rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 py-[10px] text-sm font-medium text-[var(--text-secondary)] hover:bg-[var(--surface-sunken)]">
                                <x-dynamic-component :component="'lucide-' . $link['icon']" class="h-4 w-4 text-[var(--color-primary-600)]" />
                                {{ $link['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
