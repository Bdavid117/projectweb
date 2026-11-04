<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $student['name'] }} · Trayectoria Estudiantil">
    <x-page class="print:gap-6 print:px-0 print:pt-0">
        <div class="flex flex-col gap-6">
            <x-back-link href="/estudiantes" class="print:hidden">Estudiantes</x-back-link>

            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between lg:gap-12">
                <div class="flex min-w-0 items-center gap-4 sm:gap-5">
                    <span class="flex h-[72px] w-[72px] shrink-0 items-center justify-center rounded-full bg-[var(--trz-hilo-fondo)] text-2xl font-bold text-[var(--trz-hilo-texto)] sm:h-20 sm:w-20 sm:text-3xl lg:h-[88px] lg:w-[88px] lg:text-4xl" aria-hidden="true">{{ $student['initials'] }}</span>
                    <div class="flex min-w-0 flex-col gap-2">
                        <h1 class="text-2xl font-semibold leading-[1.15] text-[var(--trz-tinta)] sm:text-[42px] sm:leading-[1.08]">{{ $student['name'] }}</h1>
                        <p class="text-[15px] leading-relaxed text-[var(--trz-texto-2)]">{{ $student['code'] }} · {{ $student['cohort'] }} · {{ $student['enrollment'] }} · {{ $student['tutor'] }}</p>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-3 print:hidden">
                    <x-button variant="secondary" icon="printer" x-data @click="window.print()">Imprimir ficha</x-button>
                    @if($canRegisterTutorship)
                        <x-button variant="primary" icon="message-square" :href="route('tutorships.create', $student['id'])">Registrar tutoría</x-button>
                    @endif
                    @if($canRegisterIntervention)
                        <x-button variant="secondary" icon="clipboard-list" :href="route('interventions.create', $student['id'])">Registrar intervención</x-button>
                    @endif
                </div>
            </div>

            <div class="flex flex-col gap-2.5 border-y border-[var(--trz-linea)] py-4 sm:flex-row sm:items-center sm:gap-8" role="group" aria-label="Avance en el plan de estudios">
                <div class="flex items-baseline gap-3">
                    <span class="font-display text-[34px] font-semibold leading-none text-[var(--trz-tinta)] sm:text-[44px]">{{ $progress['percent'] }} %</span>
                    <span class="text-sm text-[var(--trz-texto-2)]">{{ $progress['details'][0]['value'] }} créditos</span>
                </div>
                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-[var(--trz-linea)]" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress['percent'] }}" aria-label="Avance del plan: {{ $progress['percent'] }} por ciento">
                    <span class="block h-full rounded-full bg-[var(--trz-hilo)]" style="width: {{ $progress['percent'] }}%"></span>
                </div>
            </div>
        </div>

        <div x-data="tabs(@js(array_keys($tabs)), @js($tabs))" class="flex flex-col gap-8">
            {{-- Índice de la ficha: chips que se deslizan en pantallas chicas. --}}
            <div class="-mx-[18px] overflow-x-auto px-[18px] sm:mx-0 sm:px-0 print:hidden">
                <div role="tablist" aria-label="Secciones de la ficha del estudiante" class="flex w-max gap-2.5 pb-1">
                    @foreach($tabs as $key => $label)
                        <button type="button" role="tab" id="tab-{{ $key }}" x-ref="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                            :aria-selected="active === '{{ $key }}' ? 'true' : 'false'"
                            :tabindex="active === '{{ $key }}' ? 0 : -1"
                            @click="select('{{ $key }}')" @keydown="onKey($event, '{{ $key }}')"
                            :class="active === '{{ $key }}' ? 'border-[var(--trz-hilo)] bg-[var(--trz-hilo-fondo)] text-[var(--trz-hilo-texto)]' : 'border-[var(--trz-borde-control)] bg-[var(--trz-superficie)] text-[var(--trz-tinta)] hover:bg-[var(--trz-hilo-fondo)]'"
                            class="inline-flex min-h-11 items-center whitespace-nowrap rounded-full border-[1.5px] px-[18px] text-[15px] font-semibold">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            {{-- General: cuaderno de ruta + datos del avance --}}
            <div role="tabpanel" id="panel-general" aria-labelledby="tab-general" tabindex="0" x-show="active === 'general'" class="grid grid-cols-1 items-start gap-x-16 gap-y-10 lg:grid-cols-[minmax(0,1fr)_380px]">
                <x-section title="Cuaderno de ruta" note="El PAPA de cada periodo, las experiencias y las tutorías en un solo eje. La historia se conserva por periodo, no se sobrescribe.">
                    @if(empty($notebook))
                        <x-empty-state kind="historia" title="Sin historia registrada" text="Cuando haya registros académicos, experiencias o tutorías, el cuaderno de ruta las mostrará aquí." />
                    @else
                        <div class="flex flex-col gap-7">
                            @foreach($notebook as $group)
                                <div class="flex flex-col gap-3">
                                    <h3 class="font-sans text-[13px] font-bold tracking-[0.08em] text-[var(--trz-texto-2)]">{{ $group['period'] }}</h3>
                                    <ul class="flex flex-col gap-3 border-l-[3px] border-[var(--trz-linea)] pl-5">
                                        @foreach($group['items'] as $item)
                                            <li class="relative flex items-start gap-3">
                                                <x-stream-mark :kind="$item['kind']" class="mt-[5px] -ml-[30px] bg-clip-padding ring-[6px] ring-[var(--trz-papel)]" />
                                                <span class="text-base leading-snug text-[var(--trz-tinta)]">{{ $item['text'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                            <p class="flex flex-wrap items-center gap-x-5 gap-y-1 text-[13px] text-[var(--trz-texto-2)]">
                                <span class="inline-flex items-center gap-2"><x-stream-mark kind="academico" />Académico</span>
                                <span class="inline-flex items-center gap-2"><x-stream-mark kind="experiencia" />Experiencia</span>
                                @if($showTutorials)<span class="inline-flex items-center gap-2"><x-stream-mark kind="acompanamiento" />Acompañamiento</span>@endif
                            </p>
                        </div>
                    @endif
                </x-section>

                <div class="flex flex-col gap-8">
                    <x-section title="Avance en el plan">
                        <dl class="flex flex-col border-t border-[var(--trz-linea)]">
                            @foreach($progress['details'] as $detail)
                                <div class="flex items-center justify-between gap-4 border-b border-[var(--trz-linea)] py-3">
                                    <dt class="text-sm text-[var(--trz-texto-2)]">{{ $detail['label'] }}</dt>
                                    <dd class="text-[15px] font-semibold text-[var(--trz-tinta)]">{{ $detail['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-section>

                    <div class="flex items-start gap-3.5 rounded-[var(--radius-lg)] bg-[var(--trz-brote-fondo)] p-4">
                        <x-ui-icon name="lock" class="mt-0.5 h-5 w-5 shrink-0 text-[var(--trz-brote)]" />
                        <div class="flex flex-col gap-1">
                            <p class="text-[15px] font-bold text-[var(--trz-tinta)]">Información restringida</p>
                            <p class="text-sm leading-relaxed text-[var(--trz-texto-2)]">El perfil psicosocial y de salud solo es visible para Coordinación. Cada consulta queda auditada.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Académica --}}
            <div role="tabpanel" id="panel-academica" aria-labelledby="tab-academica" tabindex="0" x-show="active === 'academica'" x-cloak>
                <x-section title="Historia académica por periodo" note="PAPA, promedio del periodo y avance en créditos.">
                    @if(empty($academicRows))
                        <x-empty-state kind="historia" title="Sin historia académica registrada." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[520px] text-[15px]">
                                <thead>
                                    <tr class="border-b-[1.5px] border-[var(--trz-linea)] text-[13px] font-semibold text-[var(--trz-texto-2)]">
                                        <th scope="col" class="py-2.5 pr-4 text-left font-semibold">Periodo</th>
                                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">PAPA</th>
                                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Promedio</th>
                                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Créditos</th>
                                        <th scope="col" class="py-2.5 pl-4 text-left font-semibold">Avance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($academicRows as $row)
                                        <tr class="border-b border-[var(--trz-linea)]">
                                            <th scope="row" class="py-3.5 pr-4 text-left font-semibold text-[var(--trz-tinta)]">{{ $row['period'] }}</th>
                                            <td class="px-4 py-3.5 font-semibold text-[var(--trz-tinta)]">{{ $row['papa'] }}</td>
                                            <td class="px-4 py-3.5 text-[var(--trz-texto-2)]">{{ $row['average'] }}</td>
                                            <td class="px-4 py-3.5 text-[var(--trz-texto-2)]">{{ $row['credits'] }}</td>
                                            <td class="py-3.5 pl-4 text-[var(--trz-tinta)]">{{ $row['advance'] }} %</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-section>
            </div>

            {{-- Experiencias --}}
            <div role="tabpanel" id="panel-experiencias" aria-labelledby="tab-experiencias" tabindex="0" x-show="active === 'experiencias'" x-cloak>
                <x-section title="Línea de tiempo de experiencias formativas" note="Registradas por el estudiante y validadas por Coordinación.">
                    @forelse($timeline as $item)
                        @if($loop->first)<ul class="flex flex-col border-t border-[var(--trz-linea)]">@endif
                        <li class="flex items-center gap-4 border-b border-[var(--trz-linea)] py-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--trz-hito-fondo)] text-[var(--trz-hito-texto)]"><x-ui-icon :name="$item['icon']" class="h-[18px] w-[18px]" /></span>
                            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                <p class="text-base font-semibold text-[var(--trz-tinta)]">{{ $item['description'] }}</p>
                                <p class="text-sm text-[var(--trz-texto-2)]">{{ $item['period'] }}</p>
                            </div>
                            <x-status-badge :text="$item['tag']" :tone="$item['tone']" />
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <x-empty-state kind="experiencias" title="Sin experiencias registradas." />
                    @endforelse
                </x-section>
            </div>

            {{-- Trabajo de grado --}}
            <div role="tabpanel" id="panel-trabajo-grado" aria-labelledby="tab-trabajo-grado" tabindex="0" x-show="active === 'trabajo-grado'" x-cloak>
                <x-section title="Trabajo de grado">
                    @forelse($theses as $process)
                        @if($loop->first)<ul class="flex flex-col border-t border-[var(--trz-linea)]">@endif
                        <li class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--trz-linea)] py-4">
                            <div class="flex flex-col gap-0.5">
                                <p class="text-base font-semibold text-[var(--trz-tinta)]">{{ $process['title'] }}</p>
                                <p class="text-sm text-[var(--trz-texto-2)]">{{ $process['meta'] }}</p>
                            </div>
                            <x-status-badge :text="$process['status']" :tone="$process['tone']" />
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <x-empty-state kind="tesis" title="Sin trabajo de grado registrado." />
                    @endforelse
                </x-section>
            </div>

            {{-- Práctica --}}
            <div role="tabpanel" id="panel-practica" aria-labelledby="tab-practica" tabindex="0" x-show="active === 'practica'" x-cloak>
                <x-section title="Práctica empresarial">
                    @forelse($internships as $process)
                        @if($loop->first)<ul class="flex flex-col border-t border-[var(--trz-linea)]">@endif
                        <li class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--trz-linea)] py-4">
                            <div class="flex flex-col gap-0.5">
                                <p class="text-base font-semibold text-[var(--trz-tinta)]">{{ $process['title'] }}</p>
                                <p class="text-sm text-[var(--trz-texto-2)]">{{ $process['meta'] }}</p>
                            </div>
                            <x-status-badge :text="$process['status']" :tone="$process['tone']" />
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <x-empty-state kind="practica" title="Sin práctica registrada." />
                    @endforelse
                </x-section>
            </div>

            {{-- Solicitudes --}}
            <div role="tabpanel" id="panel-solicitudes" aria-labelledby="tab-solicitudes" tabindex="0" x-show="active === 'solicitudes'" x-cloak>
                <x-section title="Solicitudes ante el comité" note="Radicadas por el estudiante, con su estado actual.">
                    @if(empty($requests))
                        <x-empty-state kind="solicitudes" title="Sin solicitudes radicadas." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[560px] text-[15px]">
                                <thead>
                                    <tr class="border-b-[1.5px] border-[var(--trz-linea)] text-[13px] font-semibold text-[var(--trz-texto-2)]">
                                        <th scope="col" class="py-2.5 pr-4 text-left font-semibold">Radicada</th>
                                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Tipo</th>
                                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Periodo</th>
                                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Estado</th>
                                        <th scope="col" class="py-2.5 pl-4 text-right font-semibold"><span class="sr-only">Acciones</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $row)
                                        <tr class="border-b border-[var(--trz-linea)]">
                                            <td class="py-3.5 pr-4 text-[var(--trz-texto-2)]">{{ $row['date'] }}</td>
                                            <td class="px-4 py-3.5 font-semibold text-[var(--trz-tinta)]">{{ $row['type'] }}</td>
                                            <td class="px-4 py-3.5 text-[var(--trz-texto-2)]">{{ $row['period'] }}</td>
                                            <td class="px-4 py-3.5"><x-status-badge :text="$row['status']" :tone="$row['tone']" /></td>
                                            <td class="py-3.5 pl-4 text-right"><x-link-arrow :href="$row['url']" class="justify-end">Ver detalle</x-link-arrow></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-section>
            </div>

            @if($showTutorials)
            {{-- Tutorías --}}
            <div role="tabpanel" id="panel-tutorias" aria-labelledby="tab-tutorias" tabindex="0" x-show="active === 'tutorias'" x-cloak>
                <x-section title="Tutorías" note="Visibles para Coordinación y el profesor con vínculo vigente.">
                    @forelse($tutorials as $tutorial)
                        @if($loop->first)<ul class="flex flex-col border-t border-[var(--trz-linea)]">@endif
                        <li class="flex items-start gap-4 border-b border-[var(--trz-linea)] py-4">
                            <x-stream-mark kind="acompanamiento" class="mt-1.5" />
                            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                <p class="text-base font-semibold text-[var(--trz-tinta)]">{{ $tutorial['reason'] }}</p>
                                <p class="text-sm text-[var(--trz-texto-2)]">{{ $tutorial['date'] }}@if($tutorial['note']) · {{ $tutorial['note'] }}@endif</p>
                            </div>
                            @if($tutorial['editUrl'])
                                <x-link-arrow :href="$tutorial['editUrl']" icon="pencil" class="shrink-0 print:hidden">Editar</x-link-arrow>
                            @endif
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <x-empty-state kind="tutor" title="Sin tutorías registradas." />
                    @endforelse
                </x-section>
            </div>

            {{-- Intervenciones --}}
            <div role="tabpanel" id="panel-intervenciones" aria-labelledby="tab-intervenciones" tabindex="0" x-show="active === 'intervenciones'" x-cloak>
                <x-section title="Intervenciones de acompañamiento" note="Con su origen y su resultado. Visibles para Coordinación y el profesor con vínculo vigente.">
                    @forelse($interventions as $item)
                        @if($loop->first)<ul class="flex flex-col border-t border-[var(--trz-linea)]">@endif
                        <li class="flex flex-col gap-2 border-b border-[var(--trz-linea)] py-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <x-status-badge :text="$item['status']" :tone="$item['tone']" />
                                    <span class="text-base font-semibold text-[var(--trz-tinta)]">Origen: {{ $item['origen'] }}</span>
                                </div>
                                @if($item['closeUrl'])
                                    <x-link-arrow :href="$item['closeUrl']" icon="check" class="shrink-0 print:hidden">Cerrar</x-link-arrow>
                                @endif
                            </div>
                            <p class="text-sm text-[var(--trz-texto-2)]">{{ $item['date'] }} · {{ $item['responsable'] }}</p>
                            <p class="text-[15px] leading-relaxed text-[var(--trz-tinta)]">{{ $item['description'] }}</p>
                            @if($item['result'])
                                <p class="text-[15px] leading-relaxed text-[var(--trz-texto-2)]"><span class="font-semibold text-[var(--trz-tinta)]">Resultado:</span> {{ $item['result'] }}</p>
                            @endif
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <x-empty-state kind="tutor" title="Sin intervenciones registradas." text="Regístrelas desde el botón de la ficha o desde una alerta, solicitud o tutoría del estudiante." />
                    @endforelse
                </x-section>
            </div>
            @endif
        </div>
    </x-page>
</x-layouts.app>
