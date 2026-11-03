@php
    $latest = $records->first();
    $first = $records->last();
    $count = $records->count();
    $sentence = $count === 0 ? '' : 'Su PAPA '
        .($count > 1
            ? 'pasó de '.\App\Support\Labels::decimal($first->papa).' a '.\App\Support\Labels::decimal($latest->papa).' en '.$count.' periodos registrados'
            : 'es '.\App\Support\Labels::decimal($latest->papa).' en el único periodo registrado')
        .($latest->creditos_cursados ? ', y ya cursó '.$latest->creditos_cursados.' créditos: el '.(int) round((float) $latest->porcentaje_avance).' % del plan' : '').'.';
@endphp

<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mi historia académica · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Mi historia académica" subtitle="Registros académicos por periodo." />

        @if($count > 0)
            <x-frase>{{ $sentence }}</x-frase>

            <div class="grid grid-cols-1 items-start gap-x-20 gap-y-12 lg:grid-cols-2">
                <x-section title="Evolución del PAPA por periodo" note="Escala de 0 a 5.">
                    <div class="overflow-x-auto">
                        <div class="flex min-h-[200px] items-end gap-2 sm:gap-5 border-b border-[var(--trz-linea)] px-1 pt-3" role="img" aria-label="PAPA por periodo: {{ collect($papaSeries)->map(fn ($b) => $b['period'].' '.$b['label'])->implode(', ') }}">
                            @foreach($papaSeries as $bar)
                                <div class="flex min-w-[34px] flex-1 flex-col items-center justify-end gap-1.5">
                                    <span class="text-[13px] font-bold text-[var(--trz-tinta)] sm:text-sm">{{ $bar['label'] }}</span>
                                    <div class="w-full max-w-[44px] rounded-t-[6px] bg-[var(--trz-hilo)]" style="height: {{ $bar['height'] }}px"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex gap-2 px-1 pt-2 sm:gap-5">
                            @foreach($papaSeries as $bar)
                                <span class="min-w-[34px] flex-1 whitespace-nowrap text-center text-[11px] text-[var(--trz-texto-2)] sm:text-[13px]">{{ $bar['period'] }}</span>
                            @endforeach
                        </div>
                    </div>
                </x-section>

                <x-section title="Avance en créditos por periodo" note="Porcentaje del plan cursado, acumulado.">
                    <div class="overflow-x-auto">
                        <div class="flex min-h-[200px] items-end gap-2 sm:gap-5 border-b border-[var(--trz-linea)] px-1 pt-3" role="img" aria-label="Avance por periodo: {{ collect($creditSeries)->map(fn ($b) => $b['period'].' '.$b['label'])->implode(', ') }}">
                            @foreach($creditSeries as $bar)
                                <div class="flex min-w-[34px] flex-1 flex-col items-center justify-end gap-1.5">
                                    <span class="text-[13px] font-bold text-[var(--trz-tinta)] sm:text-sm">{{ $bar['label'] }}</span>
                                    <div class="w-full max-w-[44px] rounded-t-[6px] bg-[var(--trz-hilo)]" style="height: {{ $bar['height'] }}px"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex gap-2 px-1 pt-2 sm:gap-5">
                            @foreach($creditSeries as $bar)
                                <span class="min-w-[34px] flex-1 whitespace-nowrap text-center text-[11px] text-[var(--trz-texto-2)] sm:text-[13px]">{{ $bar['period'] }}</span>
                            @endforeach
                        </div>
                    </div>
                </x-section>
            </div>

            <x-section title="Registro por periodo">
                <div class="overflow-x-auto">
                    <table class="w-full text-[15px] sm:min-w-[560px]">
                        <thead>
                            <tr class="border-b-[1.5px] border-[var(--trz-linea)] text-[13px] font-semibold text-[var(--trz-texto-2)]">
                                <th scope="col" class="py-2.5 pr-2 text-left font-semibold sm:pr-4">Periodo</th>
                                <th scope="col" class="px-2 py-2.5 text-left font-semibold sm:px-4">PAPA</th>
                                <th scope="col" class="hidden px-4 py-2.5 text-left font-semibold sm:table-cell">Promedio periodo</th>
                                <th scope="col" class="px-2 py-2.5 text-left font-semibold sm:px-4">Créditos cursados</th>
                                <th scope="col" class="py-2.5 pl-2 text-left font-semibold sm:pl-4">Avance del plan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $record)
                                @php($advance = (int) round((float) $record->porcentaje_avance))
                                <tr class="border-b border-[var(--trz-linea)]">
                                    <th scope="row" class="whitespace-nowrap py-3.5 pr-2 text-left font-semibold text-[var(--trz-tinta)] sm:pr-4">{{ $record->term->codigo }}</th>
                                    <td class="px-2 py-3.5 font-semibold text-[var(--trz-tinta)] sm:px-4">{{ \App\Support\Labels::decimal($record->papa) }}</td>
                                    <td class="hidden px-4 py-3.5 text-[var(--trz-texto-2)] sm:table-cell">{{ $record->promedio_periodo !== null ? \App\Support\Labels::decimal($record->promedio_periodo) : '-' }}</td>
                                    <td class="px-2 py-3.5 text-[var(--trz-texto-2)] sm:px-4">{{ $record->creditos_cursados ?? '-' }}</td>
                                    <td class="py-3.5 pl-2 sm:pl-4">
                                        <div class="flex items-center gap-2 sm:gap-3">
                                            <span class="h-2 w-14 sm:w-[140px] shrink-0 overflow-hidden rounded-full bg-[var(--trz-linea)]" role="presentation"><span class="block h-full rounded-full bg-[var(--trz-hilo)]" style="width: {{ $advance }}%"></span></span>
                                            <span class="whitespace-nowrap text-sm font-semibold text-[var(--trz-tinta)] sm:w-12">{{ $advance }} %</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-section>
        @else
            <x-empty-state kind="historia" title="No hay registros académicos" text="Aún no hay registros académicos cargados a su nombre." />
        @endif
    </x-page>
</x-layouts.app>
