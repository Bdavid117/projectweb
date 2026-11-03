@php
    $s = $dashboard['summary'] ?? null;
    $exp = $s['experiences'] ?? ['validated' => 0, 'pending' => 0];
    $plural = fn (int $n, string $one, string $many) => $n.' '.($n === 1 ? $one : $many);
    $sentence = $s === null ? '' : ($s['progress'] !== null
            ? 'Lleva el '.$s['progress'].' % del plan con un PAPA de '.$s['papa'].($s['enrollment'] ? ', en su '.$s['enrollment'] : '').'.'
            : 'Aún no hay registros académicos cargados a su nombre.')
        .' Tiene '.$plural($s['open'], 'solicitud abierta', 'solicitudes abiertas').' y '.$plural($exp['validated'], 'experiencia validada', 'experiencias validadas')
        .($exp['pending'] > 0 ? '; '.($exp['pending'] === 1 ? 'otra espera' : $exp['pending'].' esperan').' a Coordinación' : '').'.';
@endphp

<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mi panel · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Mi panel" :subtitle="$dashboard ? $dashboard['header']['name'].' · '.$dashboard['header']['code'].' · '.$dashboard['header']['cohort'] : 'Su usuario aún no tiene ficha de estudiante'">
            @if($dashboard)
                <x-slot:actions>
                    <x-button variant="primary" icon="plus" href="/solicitudes/nueva">Nueva solicitud</x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        @if($dashboard)
            <x-frase>{{ $sentence }}</x-frase>

            <x-section title="Mi recorrido" heading-id="recorrido">
                <x-journey :steps="$dashboard['journey']['steps']" :progress="$dashboard['journey']['progress']" class="pt-2" />
            </x-section>

            <div class="grid items-start gap-x-16 gap-y-12 lg:grid-cols-[minmax(0,1fr)_520px]">
                <x-section title="Mis solicitudes recientes">
                    <x-slot:aside><x-link-arrow href="/solicitudes">Ver todas</x-link-arrow></x-slot:aside>
                    @forelse($dashboard['requests'] as $row)
                        @if($loop->first)<ul class="flex flex-col border-t border-[var(--trz-linea)]">@endif
                        <li class="border-b border-[var(--trz-linea)]">
                            <a href="{{ route('requests.show', $row['id']) }}" class="flex items-center justify-between gap-4 py-4 hover:bg-[var(--trz-hilo-fondo)]">
                                <span class="flex min-w-0 flex-col gap-0.5">
                                    <span class="text-[17px] font-semibold text-[var(--trz-tinta)]">{{ $row['type'] }}</span>
                                    <span class="text-sm text-[var(--trz-texto-2)]">Radicada {{ $row['when'] }}</span>
                                </span>
                                <x-status-badge :text="$row['status'][0]" :tone="$row['status'][1]" />
                            </a>
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <x-empty-state kind="solicitudes" title="Aún no ha radicado solicitudes" text="Cuando radique una, la verá aquí con su estado y su recorrido.">
                            <x-button variant="primary" icon="plus" href="/solicitudes/nueva">Nueva solicitud</x-button>
                        </x-empty-state>
                    @endforelse
                </x-section>

                <x-section title="Mi proceso">
                    <ul class="flex flex-col border-t border-[var(--trz-linea)]">
                        @foreach([
                            ['users', 'Mi tutor', $dashboard['header']['tutor'], '/mi-tutor'],
                            ['graduation-cap', 'Trabajo de grado', $dashboard['thesis'] ? $dashboard['thesis']['status'] : 'Sin registrar', '/trabajo-de-grado'],
                            ['briefcase', 'Práctica', $dashboard['internship'] ? $dashboard['internship']['period'] : 'Sin registrar', '/practica'],
                            ['book-open', 'Historia académica', 'Registros por periodo', '/historia-academica'],
                            ['sparkles', 'Experiencias', $exp['validated'].' validadas, '.$exp['pending'].' pendientes', '/experiencias'],
                        ] as [$icon, $label, $detail, $href])
                            <li class="border-b border-[var(--trz-linea)]">
                                <a href="{{ $href }}" class="flex items-center gap-3.5 py-3.5 hover:bg-[var(--trz-hilo-fondo)]">
                                    <x-ui-icon :name="$icon" class="h-[22px] w-[22px] shrink-0 text-[var(--trz-hilo-texto)]" />
                                    <span class="flex min-w-0 flex-1 flex-col">
                                        <span class="text-base font-semibold text-[var(--trz-tinta)]">{{ $label }}</span>
                                        <span class="text-sm text-[var(--trz-texto-2)]">{{ $detail }}</span>
                                    </span>
                                    <x-ui-icon name="arrow-right" class="h-[17px] w-[17px] shrink-0 text-[var(--trz-hilo-texto)]" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-section>
            </div>
        @else
            <x-empty-state kind="historia" title="Su usuario aún no tiene ficha de estudiante" text="Cuando Coordinación la cree, aquí verá su recorrido, sus solicitudes y su proceso." />
        @endif
    </x-page>
</x-layouts.app>
