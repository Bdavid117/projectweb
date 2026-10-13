<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Solicitud {{ $solicitud['number'] }} · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex items-center gap-[6px] text-sm text-[var(--text-secondary)]">
            <a href="/panel">Inicio</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <a href="{{ route('requests.index') }}">Solicitudes</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <span class="text-[var(--text-primary)]">Solicitud {{ $solicitud['number'] }}</span>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex flex-1 flex-col gap-[6px]">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold text-[var(--text-primary)]">{{ $solicitud['type'] }}</h1>
                    <x-status-badge :text="$solicitud['status']" :tone="$solicitud['tone']" />
                </div>
                <p class="text-sm text-[var(--text-secondary)]">Radicada el {{ $solicitud['filedAt'] }} por {{ $solicitud['student'] }} · Código {{ $solicitud['code'] }} · Periodo {{ $solicitud['term'] }}S</p>
            </div>
            <x-button variant="secondary" :href="route('requests.index')">Volver al listado</x-button>
        </div>

        @if($errors->has('estado'))
            <div class="flex gap-[10px] rounded-[var(--radius-md)] bg-[var(--color-danger-bg)] p-3">
                <x-lucide-circle-alert class="h-4 w-4 shrink-0 text-[var(--color-danger)]" />
                <p class="text-sm font-semibold text-[var(--color-danger)]">{{ $errors->first('estado') }}</p>
            </div>
        @endif

        <div class="flex flex-col gap-5 lg:flex-row">
            <div class="flex min-w-0 flex-1 flex-col gap-5">
                <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Descripción de la solicitud</h2>
                    <p class="whitespace-pre-line text-sm leading-relaxed text-[var(--text-secondary)]">{{ $solicitud['description'] }}</p>
                </div>

                <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Soportes adjuntos ({{ count($attachments) }})</h2>
                    @forelse($attachments as $file)
                        <div class="flex items-center gap-[10px] rounded-[var(--radius-md)] bg-[var(--surface-page)] px-3 py-[10px]">
                            <x-lucide-file-text class="h-4 w-4 text-[var(--color-primary-600)]" />
                            <span class="flex-1 truncate text-sm font-semibold text-[var(--text-primary)]">{{ $file['name'] }}</span>
                            <span class="text-xs text-[var(--text-muted)]">{{ $file['size'] }}</span>
                            <a href="{{ $file['url'] }}" class="text-sm font-semibold text-[var(--color-primary-600)]">Descargar</a>
                        </div>
                    @empty
                        <p class="text-sm text-[var(--text-muted)]">Esta solicitud no tiene soportes adjuntos.</p>
                    @endforelse
                </div>

                <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Trazabilidad de estados · RF-27</h2>
                    @foreach($trail as $step)
                        <div class="flex items-center gap-3">
                            <span class="h-[10px] w-[10px] shrink-0 rounded-full {{ $step['dot'] }}"></span>
                            <div class="flex flex-col gap-[2px]">
                                <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $step['label'] }}</span>
                                <span class="text-xs text-[var(--text-muted)]">{{ $step['meta'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-5 lg:w-[360px]">
                @if($studentCard)
                    <div class="flex flex-col gap-[10px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Estudiante</h2>
                        @foreach([['Nombre', $studentCard['name']], ['Código', $studentCard['code']], ['Cohorte', $studentCard['cohort']], ['PAPA actual', $studentCard['papa']], ['Tutor', $studentCard['tutor']]] as [$label, $value])
                            <div class="flex justify-between gap-3">
                                <span class="text-xs text-[var(--text-muted)]">{{ $label }}</span>
                                <span class="text-right text-sm font-semibold text-[var(--text-primary)]">{{ $value }}</span>
                            </div>
                        @endforeach
                        <a href="{{ route('students.show', $studentCard['id']) }}" class="text-sm font-semibold text-[var(--color-primary-600)]">Ver ficha del estudiante</a>
                    </div>
                @endif

                @if($repeatedWarning)
                    <div class="flex items-start gap-[10px] rounded-[var(--radius-md)] bg-[var(--color-warning-bg)] p-3">
                        <x-lucide-triangle-alert class="h-4 w-4 shrink-0 text-[var(--color-warning)]" />
                        <p class="text-xs text-[var(--color-warning)]">{{ $repeatedWarning }}</p>
                    </div>
                @endif

                @if($decision)
                    <div class="flex flex-col gap-[10px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Decisión del comité</h2>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-[var(--text-muted)]">Decisión</span>
                            <x-status-badge :text="$decision['text']" :tone="$decision['tone']" />
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-[var(--text-muted)]">Acta</span>
                            <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $decision['acta'] ?? '—' }}</span>
                        </div>
                        @if($decision['observations'])
                            <p class="whitespace-pre-line text-sm text-[var(--text-secondary)]">{{ $decision['observations'] }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
