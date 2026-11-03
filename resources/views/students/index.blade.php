<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Estudiantes · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Estudiantes" :subtitle="$activeCount.' '.($activeCount === 1 ? 'estudiante activo' : 'estudiantes activos').' '.($scopedToTutor ? 'entre sus tutorados' : 'en el programa').'.'">
            @if($canFilterByTutor)
                <x-slot:actions>
                    <x-button variant="subtle" icon="download" disabled aria-disabled="true" title="Aún no disponible">Exportar</x-button>
                    <x-button variant="primary" icon="upload" disabled aria-disabled="true" title="Aún no disponible">Cargar Excel</x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        <x-filter-bar :action="route('students.index')">
            <x-search-field name="q" :value="$filters['q']" placeholder="Buscar por nombre, código o documento" />
            <div class="w-full sm:w-[190px]"><x-filter-select name="cohorte" all="Cohorte: todas" :options="$cohortOptions" :selected="$filters['cohorte']" /></div>
            <div class="w-full sm:w-[190px]"><x-filter-select name="estado" all="Estado: todos" :options="$statusOptions" :selected="$filters['estado']" /></div>
            @if($canFilterByTutor)
                <div class="w-full sm:w-[190px]"><x-filter-select name="tutor" all="Tutor: todos" :options="$tutorOptions" :selected="$filters['tutor']" /></div>
            @endif
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </x-filter-bar>

        @if($students->isEmpty())
            @if($scopedToTutor && ! $hasFilters)
                <x-empty-state kind="solicitar" title="Aún no tiene tutorados">
                    <x-slot:body>Pida acompañar a un estudiante en <a href="{{ route('tutor-requests.index') }}" class="font-semibold text-[var(--trz-hilo-texto)] hover:underline">Solicitar tutoría</a>; lo verá aquí cuando acepte.</x-slot:body>
                </x-empty-state>
            @else
                <x-empty-state kind="historia" title="No hay estudiantes que coincidan con los filtros." text="Pruebe con otro nombre, código o cohorte, o quite algún filtro." />
            @endif
        @else
            <table class="hidden w-full text-[15px] md:table">
                <caption class="sr-only">Estudiantes</caption>
                <thead>
                    <tr class="border-b-[1.5px] border-[var(--trz-linea)] text-[13px] font-semibold text-[var(--trz-texto-2)]">
                        <th scope="col" class="py-2.5 pr-4 text-left font-semibold">Estudiante</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Cohorte</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">PAPA</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Avance del plan</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Estado</th>
                        <th scope="col" class="py-2.5 pl-4 text-right font-semibold"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                        <tr class="border-b border-[var(--trz-linea)] hover:bg-[var(--trz-hilo-fondo)]">
                            <th scope="row" class="py-3.5 pr-4 text-left font-normal">
                                <a href="{{ route('students.show', $student['id']) }}" class="block text-base font-semibold text-[var(--trz-tinta)] hover:text-[var(--trz-hilo-texto)] hover:underline">{{ $student['name'] }}</a>
                                <span class="text-[13px] text-[var(--trz-texto-2)]">{{ $student['code'] }}</span>
                            </th>
                            <td class="px-4 py-3.5 text-[var(--trz-tinta)]">{{ $student['cohort'] }}</td>
                            <td class="px-4 py-3.5">
                                @if($student['papaLow'])
                                    <span class="inline-flex items-center gap-1.5 font-bold text-[var(--trz-alerta)]"><x-ui-icon name="triangle-alert" class="h-[15px] w-[15px]" /><span class="sr-only">PAPA bajo el mínimo:</span>{{ $student['papa'] }}</span>
                                @else
                                    <span class="text-[var(--trz-tinta)]">{{ $student['papa'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="h-2 w-[110px] overflow-hidden rounded-full bg-[var(--trz-linea)]" role="presentation"><span class="block h-full rounded-full bg-[var(--trz-hilo)]" style="width: {{ $student['advance'] }}%"></span></span>
                                    <span class="w-10 text-sm font-semibold text-[var(--trz-tinta)]">{{ $student['advance'] }} %</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5"><x-status-badge :text="$student['status']" :tone="$student['tone']" /></td>
                            <td class="py-3.5 pl-4 text-right"><x-link-arrow :href="route('students.show', $student['id'])" class="justify-end">Ver ficha</x-link-arrow></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <ul class="flex flex-col md:hidden">
                @foreach($students as $student)
                    <li class="border-b border-[var(--trz-linea)] first:border-t">
                        <a href="{{ route('students.show', $student['id']) }}" class="flex flex-col gap-2 py-3.5 hover:bg-[var(--trz-hilo-fondo)]">
                            <span class="flex items-center justify-between gap-3">
                                <span class="text-base font-semibold text-[var(--trz-tinta)]">{{ $student['name'] }}</span>
                                @if($student['papaLow'])
                                    <span class="inline-flex items-center gap-1 text-[15px] font-bold text-[var(--trz-alerta)]"><x-ui-icon name="triangle-alert" class="h-3.5 w-3.5" />{{ $student['papa'] }}</span>
                                @else
                                    <span class="text-[15px] font-bold text-[var(--trz-tinta)]">{{ $student['papa'] }}</span>
                                @endif
                            </span>
                            <span class="flex items-center justify-between gap-3">
                                <span class="text-[13px] text-[var(--trz-texto-2)]">{{ $student['code'] }} · {{ $student['cohort'] }} · avance {{ $student['advance'] }} %</span>
                                <x-status-badge :text="$student['status']" :tone="$student['tone']" />
                            </span>
                            <span class="h-1.5 overflow-hidden rounded-full bg-[var(--trz-linea)]" role="presentation"><span class="block h-full rounded-full bg-[var(--trz-hilo)]" style="width: {{ $student['advance'] }}%"></span></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-pagination :paginator="$students" noun="estudiantes" />
    </x-page>
</x-layouts.app>
