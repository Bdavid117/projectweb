@php
    $h = $headline;
    $isProfessor = $h['scoped'];
    $subject = $isProfessor ? ($h['active'] === 1 ? 'estudiante bajo su tutoría' : 'estudiantes bajo su tutoría') : ($h['active'] === 1 ? 'estudiante activo' : 'estudiantes activos');
    $attention = $h['open'] === 0 ? 'ninguno con situaciones por atender' : $h['open'].' con situaciones por atender';
    $tenths = $h['papaDelta'] === null ? null : (int) round(abs($h['papaDelta']) * 10);
    $delta = match (true) {
        $tenths === null => null,
        $tenths === 0 => 'igual que el periodo anterior',
        default => ($tenths === 1 ? 'una décima' : ($tenths < 10 ? $tenths.' décimas' : \App\Support\Labels::decimal(abs($h['papaDelta'])).' puntos')).($h['papaDelta'] > 0 ? ' más' : ' menos').' que el periodo anterior',
    };
@endphp

<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Panel · Trayectoria Estudiantil">
    <x-page>
        <div class="flex flex-col gap-8">
            <p class="text-sm text-[var(--trz-texto-2)]">{{ $termLabel === 'sin periodo' ? 'Sin periodo vigente' : 'Periodo '.$termLabel }}, con datos al {{ $cutoff }}.</p>

            <div class="flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between lg:gap-12">
                <div class="flex min-w-0 flex-col gap-4">
                    @if($isProfessor && $h['active'] === 0)
                        <h1 class="max-w-[22ch] text-[28px] font-semibold leading-[1.1] text-[var(--trz-tinta)] sm:text-[40px] sm:leading-[1.06] lg:text-[52px]">Aún no tiene estudiantes bajo su tutoría.</h1>
                        <p class="max-w-[60ch] text-lg leading-[1.45] text-[var(--trz-texto-2)]">Pida acompañar a un estudiante; lo verá aquí cuando acepte.</p>
                    @else
                        <h1 class="max-w-[24ch] text-[28px] font-semibold leading-[1.1] text-[var(--trz-tinta)] sm:text-[40px] sm:leading-[1.06] lg:text-[52px]">{{ $h['active'] }} {{ $subject }}, {{ $attention }}.</h1>
                        @if($h['papa'] !== null)
                            <p class="max-w-[62ch] text-lg leading-[1.45] text-[var(--trz-texto-2)]">
                                {{ $isProfessor ? 'Su PAPA medio es' : 'El PAPA medio es' }} {{ $h['papa'] }}@if($delta), {{ $delta }},@endif
                                y el avance medio del plan es {{ $h['advance'] }} %.@if($isProfessor) Solo ve a quienes aceptaron su solicitud de tutoría.@endif
                            </p>
                        @endif
                    @endif
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-3">
                    @if($isProfessor)
                        <x-button variant="subtle" :href="route('advisories.index')">Mis asesorías</x-button>
                        <x-button variant="primary" icon="user-plus" :href="route('tutor-requests.index')">Solicitar tutoría</x-button>
                    @else
                        <x-button variant="subtle" icon="download" disabled aria-disabled="true" title="Aún no disponible">Exportar</x-button>
                        <x-button variant="primary" icon="upload" disabled aria-disabled="true" title="Aún no disponible">Cargar Excel</x-button>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 items-start gap-x-16 gap-y-12 lg:grid-cols-[minmax(0,1fr)_420px]">
            <x-section :title="$isProfessor ? 'Dónde está cada tutorado' : 'Dónde está cada cohorte'" note="Cada punto es un estudiante, ubicado según el porcentaje que lleva del plan de estudios; pulse uno para abrir su ficha. Quienes tienen una alerta abierta llevan un anillo.">
                @if(empty($cohortMap['rows']))
                    <x-empty-state kind="historia" title="Sin datos de avance por cohorte" text="Cuando haya registros académicos del periodo, cada estudiante aparecerá aquí como un punto." />
                @else
                    <x-cohort-map :rows="$cohortMap['rows']" :reading="$cohortMap['reading']" :filters="$cohortMap['filters']" :total="$cohortMap['total']" :initial="$cohortFilter ?? 'todos'" />
                @endif
            </x-section>

            <div class="flex flex-col gap-12">
                @php
                    $activeKinds = ($cohortFilter ?? 'todos') === 'todos' ? [] : explode(',', $cohortFilter);
                    $matchesFilter = fn ($row) => collect($activeKinds)->every(fn ($kind) => in_array($kind, $row['tags'], true));
                    $shownRows = collect($attentionRows)->filter($matchesFilter)->take(6)->pluck('id')->all();
                    $noneMatch = ! empty($attentionRows) && $shownRows === [];
                @endphp
                <x-section title="Piden atención" note="Las reglas del programa se evalúan cada día (PAPA bajo o en caída, matrícula sin avance, solicitudes repetidas y falta de tutoría).">
                    @if(empty($attentionRows))
                        <p class="border-y border-[var(--trz-linea)] py-5 text-[15px] text-[var(--trz-texto-2)]">Sin situaciones por atender.</p>
                    @else
                        {{-- Sigue al filtro de tipos del mapa: muestra hasta 6 de las alertas recientes que le corresponden. --}}
                        <div x-data="attentionList(@js($activeKinds))">
                            <ul class="flex flex-col border-t border-[var(--trz-linea)]">
                                @foreach($attentionRows as $row)
                                    <li data-attention data-tags="{{ implode(' ', $row['tags']) }}" @unless(in_array($row['id'], $shownRows, true)) hidden @endunless class="flex flex-col gap-2 border-b border-[var(--trz-linea)] py-4">
                                        <div class="flex items-baseline justify-between gap-3">
                                            <a href="{{ $row['url'] }}" class="text-[17px] font-semibold text-[var(--trz-tinta)] hover:text-[var(--trz-hilo-texto)] hover:underline max-md:-my-2.5 max-md:py-2.5">{{ $row['name'] }}</a>
                                            <span class="shrink-0 text-[13px] text-[var(--trz-texto-2)]">{{ $row['date'] }}</span>
                                        </div>
                                        <p class="text-[15px] text-[var(--trz-texto-2)]">{{ $row['issue'] }}</p>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <x-status-badge :text="$row['status']" :tone="$row['tone']" />
                                            @if($row['open'])
                                                <x-button variant="subtle" icon="clipboard-list" :href="route('interventions.create', ['student' => $row['student_id'], 'origen' => 'alerta', 'origen_id' => $row['id']])" class="-ml-3 sm:-ml-0">Registrar intervención</x-button>
                                                <form method="POST" action="{{ route('alerts.attend', $row['id']) }}">
                                                    @csrf
                                                    <x-button variant="subtle" type="submit" icon="check">Marcar atendida</x-button>
                                                </form>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                            <p x-show="empty" @unless($noneMatch) x-cloak style="display: none" @endunless class="border-b border-[var(--trz-linea)] py-5 text-[15px] text-[var(--trz-texto-2)]">Ninguna de las situaciones recientes corresponde a este filtro. Quite algún tipo del mapa para verlas.</p>
                        </div>
                        <x-link-arrow href="/estudiantes">Ver {{ $isProfessor ? 'a todos mis tutorados' : 'todas las situaciones' }}</x-link-arrow>
                    @endif
                </x-section>

                <x-section title="Movimiento reciente">
                    @if(empty($recentActivity))
                        <p class="text-[15px] text-[var(--trz-texto-2)]">Sin actividad reciente.</p>
                    @else
                        <ul class="flex flex-col gap-4">
                            @foreach($recentActivity as $activity)
                                <li class="flex items-start gap-3.5">
                                    <x-stream-mark :kind="match ($activity['icon']) { 'sparkles' => 'experiencia', 'message-square' => 'acompanamiento', default => 'academico' }" class="mt-1.5" />
                                    <div class="flex min-w-0 flex-col gap-0.5">
                                        <p class="text-base font-semibold leading-snug text-[var(--trz-tinta)]">{{ $activity['description'] }}</p>
                                        <p class="text-[13px] text-[var(--trz-texto-2)]">{{ $activity['meta'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-section>
            </div>
        </div>
    </x-page>
</x-layouts.app>
