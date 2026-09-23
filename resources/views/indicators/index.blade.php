<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Indicadores del programa · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Indicadores del programa</h1>
                <p class="text-sm text-[var(--text-secondary)]">Vista exclusiva de Coordinación · calculada sobre datos históricos por periodo · RF-38</p>
            </div>
            <x-button variant="subtle" icon="download">Exportar</x-button>
        </div>

        <div class="flex flex-wrap items-center gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-3">
            <div class="flex h-[38px] items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 text-sm text-[var(--text-secondary)]">
                Cohorte: todas <x-lucide-chevron-down class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
            </div>
            <div class="flex h-[38px] items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 text-sm text-[var(--text-secondary)]">
                Periodo: 2026-2S <x-lucide-chevron-down class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
            </div>
            <div class="flex h-[38px] items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 text-sm text-[var(--text-secondary)]">
                Estado: activos <x-lucide-chevron-down class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
            </div>
            <x-button variant="secondary">Aplicar</x-button>
            <div class="flex-1"></div>
            <span class="text-sm text-[var(--text-muted)]">Datos al 20/09/2026</span>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach($keyIndicators as $indicator)
                <x-indicator-card :label="$indicator['label']" :value="$indicator['value']" :detail="$indicator['detail']" :value-class="$indicator['valueClass'] ?? null" />
            @endforeach
        </div>

        <div class="flex flex-col gap-4 lg:flex-row">
            <div class="flex flex-1 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                <div class="flex flex-col gap-[2px] px-4 pb-2 pt-[14px]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Distribución del PAPA</h2>
                    <p class="text-xs text-[var(--text-muted)]">Número de estudiantes por rango</p>
                </div>
                <div class="flex items-end gap-3 border-t border-[var(--border-subtle)] px-4 py-4">
                    @foreach($papaHistogram as $bar)
                        <div class="flex flex-1 flex-col items-center justify-end gap-[7px]">
                            <span class="text-xs font-medium text-[var(--text-secondary)]">{{ $bar['value'] }}</span>
                            <div class="w-full rounded-t-[var(--radius-sm)] bg-[var(--color-primary-500)]" style="height: {{ $bar['height'] }}px"></div>
                            <span class="text-xs text-[var(--text-muted)]">{{ $bar['range'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] lg:w-[470px]">
                <div class="flex flex-col gap-[2px] px-4 pb-2 pt-[14px]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Participación en experiencias formativas</h2>
                    <p class="text-xs text-[var(--text-muted)]">Estudiantes con al menos un registro</p>
                </div>
                <div class="flex flex-col gap-3 border-t border-[var(--border-subtle)] px-4 py-[10px]">
                    @foreach($participation as $row)
                        <div class="flex flex-col gap-[5px]">
                            <div class="flex items-center gap-2">
                                <span class="flex-1 text-sm text-[var(--text-primary)]">{{ $row['label'] }}</span>
                                <span class="text-sm text-[var(--text-secondary)]">{{ $row['value'] }}</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-[var(--surface-sunken)]">
                                <div class="h-2 rounded-full bg-[var(--color-primary-500)]" style="width: {{ $row['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
            <div class="flex flex-col gap-[2px] px-4 pb-2 pt-[14px]">
                <h2 class="text-sm font-semibold text-[var(--text-primary)]">Solicitudes por tipo y estado</h2>
                <p class="text-xs text-[var(--text-muted)]">Periodo 2026-2S · RF-28</p>
            </div>
            <div class="hidden gap-3 border-t border-[var(--border-subtle)] px-4 py-[10px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)] sm:flex">
                <span class="flex-1">TIPO DE SOLICITUD</span>
                <span class="w-[110px]">RADICADAS</span>
                <span class="w-[120px]">EN REVISIÓN</span>
                <span class="w-[125px]">CON DECISIÓN</span>
                <span class="w-[125px]">TIEMPO MEDIO</span>
            </div>
            @foreach($requestsByType as $row)
                <div class="flex flex-col gap-1 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm sm:flex-row sm:items-center sm:gap-3">
                    <span class="flex-1 font-medium text-[var(--text-primary)] sm:font-normal">{{ $row['type'] }}</span>
                    <span class="text-[var(--text-secondary)] sm:w-[110px]">Radicadas: {{ $row['filed'] }}</span>
                    <span class="text-[var(--text-secondary)] sm:w-[120px]">En revisión: {{ $row['review'] }}</span>
                    <span class="text-[var(--text-secondary)] sm:w-[125px]">Con decisión: {{ $row['decided'] }}</span>
                    <span class="text-[var(--text-secondary)] sm:w-[125px]">Tiempo medio: {{ $row['avgTime'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
