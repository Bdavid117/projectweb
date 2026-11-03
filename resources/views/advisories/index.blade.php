@php
    $s = $summary;
@endphp

<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mis asesorías · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Mis asesorías" :subtitle="$total.' estudiantes asignados en '.$termLabel.'. Solo aparecen quienes aceptaron su solicitud de tutoría.'">
            <x-slot:actions>
                <x-button variant="primary" icon="user-plus" :href="route('tutor-requests.index')">Solicitar tutoría</x-button>
            </x-slot:actions>
        </x-page-header>

        @if($total > 0)
            <x-frase>
                De sus {{ $total }} tutorados, {{ $s['lowPapa'] === 0 ? 'ninguno tiene' : ($s['lowPapa'] === 1 ? '1 tiene' : $s['lowPapa'].' tienen') }} el PAPA por debajo de {{ $s['papaAlert'] }}
                y {{ $s['withAlerts'] === 0 ? 'ninguno acumula' : ($s['withAlerts'] === 1 ? '1 acumula' : $s['withAlerts'].' acumulan') }} una alerta abierta.
                @if($s['lastSession'])La última tutoría registrada fue el {{ $s['lastSession']->isoFormat('D [de] MMMM') }}.@endif
            </x-frase>
        @endif

        <x-filter-bar :action="route('advisories.index')">
            <x-search-field name="q" :value="$filters['q']" placeholder="Buscar por nombre o código" />
            <div class="w-full sm:w-[220px]"><x-filter-select name="alertas" all="Alertas: todas" :options="$alertOptions" :selected="$filters['alertas']" /></div>
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </x-filter-bar>

        @if($students->isEmpty())
            <x-empty-state kind="historia" title="No hay estudiantes que coincidan con los filtros." text="Pruebe con otro nombre o quite el filtro de alertas." />
        @else
            <table class="hidden w-full text-[15px] md:table">
                <caption class="sr-only">Estudiantes asignados</caption>
                <thead>
                    <tr class="border-b-[1.5px] border-[var(--trz-linea)] text-[13px] font-semibold text-[var(--trz-texto-2)]">
                        <th scope="col" class="py-2.5 pr-4 text-left font-semibold">Estudiante</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Cohorte</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Última tutoría</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">PAPA</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Alertas</th>
                        <th scope="col" class="py-2.5 pl-4 text-right font-semibold"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $row)
                        <tr class="border-b border-[var(--trz-linea)] hover:bg-[var(--trz-hilo-fondo)]">
                            <th scope="row" class="py-3.5 pr-4 text-left font-normal"><a href="{{ $row['profileUrl'] }}" class="text-base font-semibold text-[var(--trz-tinta)] hover:text-[var(--trz-hilo-texto)] hover:underline">{{ $row['name'] }}</a></th>
                            <td class="px-4 py-3.5 text-[var(--trz-tinta)]">{{ $row['cohort'] }}</td>
                            <td class="px-4 py-3.5 text-[var(--trz-tinta)]">{{ $row['last'] }}</td>
                            <td class="px-4 py-3.5">
                                @if($row['papaLow'])
                                    <span class="inline-flex items-center gap-1.5 font-bold text-[var(--trz-alerta)]"><x-ui-icon name="triangle-alert" class="h-[15px] w-[15px]" /><span class="sr-only">PAPA bajo el mínimo:</span>{{ $row['papa'] }}</span>
                                @else
                                    <span class="text-[var(--trz-tinta)]">{{ $row['papa'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5"><x-status-badge :text="$row['alert']" :tone="$row['tone']" /></td>
                            <td class="py-3.5 pl-4 text-right"><x-link-arrow :href="$row['registerUrl']" class="justify-end">Registrar tutoría</x-link-arrow></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <ul class="flex flex-col md:hidden">
                @foreach($students as $row)
                    <li class="flex flex-col gap-1.5 border-b border-[var(--trz-linea)] py-3.5 first:border-t">
                        <span class="flex items-center justify-between gap-3">
                            <a href="{{ $row['profileUrl'] }}" class="text-base font-semibold text-[var(--trz-tinta)] hover:underline">{{ $row['name'] }}</a>
                            <span class="text-[15px] font-bold {{ $row['papaLow'] ? 'text-[var(--trz-alerta)]' : 'text-[var(--trz-tinta)]' }}">{{ $row['papa'] }}</span>
                        </span>
                        <span class="flex items-center justify-between gap-3">
                            <span class="text-[13px] text-[var(--trz-texto-2)]">{{ $row['cohort'] }} · última tutoría {{ $row['last'] }}</span>
                            <x-status-badge :text="$row['alert']" :tone="$row['tone']" />
                        </span>
                        <x-link-arrow :href="$row['registerUrl']">Registrar tutoría</x-link-arrow>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-pagination :paginator="$students" noun="estudiantes asignados" />
    </x-page>
</x-layouts.app>
