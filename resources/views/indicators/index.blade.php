@php
    $h = $headline;
    $n = fn (int $count, string $one, string $many) => $count.' '.($count === 1 ? $one : $many);
@endphp

<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Indicadores del programa · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Indicadores del programa" :subtitle="'Periodo '.$termLabel.', calculados sobre los datos históricos por periodo, con datos al '.$cutoff.'.'">
            <x-slot:actions>
                <x-button variant="subtle" icon="download" disabled aria-disabled="true" title="Aún no disponible">Exportar</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-frase>
            De {{ $n($h['active'], 'estudiante activo', 'estudiantes activos') }}, {{ $h['internships'] }} {{ $h['internships'] === 1 ? 'está' : 'están' }} en práctica y {{ $h['theses'] }} {{ $h['theses'] === 1 ? 'desarrolla' : 'desarrollan' }} su trabajo de grado.
            Este periodo se {{ $h['filed'] === 1 ? 'radicó' : 'radicaron' }} {{ $n($h['filed'], 'solicitud', 'solicitudes') }} y {{ $h['pending'] }} {{ $h['pending'] === 1 ? 'sigue' : 'siguen' }} sin decisión.
        </x-frase>

        <div class="grid items-start gap-x-20 gap-y-12 lg:grid-cols-2">
            <x-section title="Distribución del PAPA" :note="'Número de estudiantes por rango. '.($papaUnit === 1 ? 'Cada punto es un estudiante.' : 'Cada punto equivale a '.$papaUnit.' estudiantes.')">
                @php($lowCount = $papaHistogram[0]['count'] + ($papaHistogram[1]['count'] ?? 0))
                <ul class="flex flex-col gap-4">
                    @foreach($papaHistogram as $bar)
                        <li class="flex flex-col gap-1.5">
                            <div class="flex items-center justify-between gap-3 text-[15px]">
                                <span class="text-[var(--trz-tinta)]">{{ $bar['range'] }}</span>
                                <span class="inline-flex items-center gap-2">
                                    @if($loop->first && $bar['count'] > 0)
                                        <span class="inline-flex items-center gap-1 text-[13px] font-semibold text-[var(--trz-alerta)]"><x-ui-icon name="triangle-alert" class="h-3.5 w-3.5" />bajo el mínimo</span>
                                    @endif
                                    <span class="font-bold text-[var(--trz-tinta)]">{{ $bar['value'] }}</span>
                                </span>
                            </div>
                            <x-dots :count="(int) ceil($bar['count'] / $papaUnit)" :tone="$loop->first ? 'alerta' : 'hilo'" />
                        </li>
                    @endforeach
                </ul>
                <p class="text-base text-[var(--trz-tinta)]">El {{ $h['active'] > 0 ? round($lowCount / max(1, array_sum(array_column($papaHistogram, 'count'))) * 100) : 0 }} % de los estudiantes ({{ $lowCount }}) tiene un PAPA por debajo de 3,3.</p>
            </x-section>

            <x-section title="Participación en experiencias formativas" :note="'Estudiantes con al menos un registro. '.($participationUnit === 1 ? 'Cada rombo es un estudiante.' : 'Cada rombo equivale a '.$participationUnit.' estudiantes.')">
                @if(empty($participation))
                    <x-empty-state kind="experiencias" title="Sin experiencias registradas" text="Cuando los estudiantes registren experiencias, aquí verá cuántos participan por categoría." />
                @else
                    <ul class="flex flex-col gap-4">
                        @foreach($participation as $row)
                            <li class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between gap-3 text-[15px]">
                                    <span class="text-[var(--trz-tinta)]">{{ $row['label'] }}</span>
                                    <span class="font-bold text-[var(--trz-tinta)]">{{ $row['count'] }} {{ $row['count'] === 1 ? 'estudiante' : 'estudiantes' }} ({{ $row['percent'] }} %)</span>
                                </div>
                                <x-dots :count="(int) ceil($row['count'] / $participationUnit)" shape="diamond" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-section>
        </div>

        <x-section title="Solicitudes por tipo y estado" :note="'Periodo '.$termLabel.'.'">
            <table class="hidden w-full text-[15px] md:table">
                <caption class="sr-only">Solicitudes del periodo {{ $termLabel }} por tipo y estado</caption>
                <thead>
                    <tr class="border-b-[1.5px] border-[var(--trz-linea)] text-[13px] font-semibold text-[var(--trz-texto-2)]">
                        <th scope="col" class="py-2.5 pr-4 text-left font-semibold">Tipo de solicitud</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-semibold">Radicadas</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-semibold">En revisión</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-semibold">Con decisión</th>
                        <th scope="col" class="py-2.5 pl-4 text-right font-semibold">Tiempo medio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requestsByType as $row)
                        <tr class="border-b border-[var(--trz-linea)]">
                            <th scope="row" class="py-3.5 pr-4 text-left font-semibold text-[var(--trz-tinta)]">{{ $row['type'] }}</th>
                            <td class="px-4 py-3.5 text-right text-[var(--trz-texto-2)]">{{ $row['filed'] }}</td>
                            <td class="px-4 py-3.5 text-right text-[var(--trz-texto-2)]">{{ $row['review'] }}</td>
                            <td class="px-4 py-3.5 text-right text-[var(--trz-texto-2)]">{{ $row['decided'] }}</td>
                            <td class="py-3.5 pl-4 text-right text-[var(--trz-texto-2)]">{{ $row['avgTime'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <ul class="flex flex-col border-t border-[var(--trz-linea)] md:hidden">
                @foreach($requestsByType as $row)
                    <li class="flex flex-col gap-1 border-b border-[var(--trz-linea)] py-3.5">
                        <span class="text-base font-semibold text-[var(--trz-tinta)]">{{ $row['type'] }}</span>
                        <span class="text-sm text-[var(--trz-texto-2)]">{{ $row['filed'] }} radicadas · {{ $row['review'] }} en revisión · {{ $row['decided'] }} con decisión · {{ $row['avgTime'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-section>
    </x-page>
</x-layouts.app>
