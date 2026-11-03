<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $title }} · Trayectoria Estudiantil">
    <x-page>
        <x-page-header :title="$title" :subtitle="$subtitle">
            @if($canCreate)
                <x-slot:actions>
                    <x-button variant="primary" icon="file-plus" :href="route('requests.create')">Nueva solicitud</x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        <x-filter-bar :action="route('requests.index')">
            @unless($isStudent)
                <x-search-field name="q" :value="$filters['q']" placeholder="Buscar por estudiante o código" />
            @endunless
            <div class="w-full sm:w-[200px]"><x-filter-select name="tipo" all="Tipo: todos" :options="$typeOptions" :selected="$filters['tipo']" /></div>
            <div class="w-full sm:w-[200px]"><x-filter-select name="estado" all="Estado: todos" :options="$statusOptions" :selected="$filters['estado']" /></div>
            <div class="w-full sm:w-[200px]"><x-filter-select name="periodo" all="Periodo: todos" :options="$termOptions" :selected="$filters['periodo']" /></div>
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </x-filter-bar>

        @if($requests->isEmpty())
            <x-empty-state kind="solicitudes" title="No hay solicitudes que coincidan con los filtros." text="Quite algún filtro o pruebe con otro periodo.">
                @if($canCreate)<x-button variant="primary" icon="file-plus" :href="route('requests.create')">Nueva solicitud</x-button>@endif
            </x-empty-state>
        @else
            <table class="hidden w-full text-[15px] md:table">
                <caption class="sr-only">{{ $title }}</caption>
                <thead>
                    <tr class="border-b-[1.5px] border-[var(--trz-linea)] text-[13px] font-semibold text-[var(--trz-texto-2)]">
                        <th scope="col" class="py-2.5 pr-4 text-left font-semibold">Radicada</th>
                        @unless($isStudent)<th scope="col" class="px-4 py-2.5 text-left font-semibold">Estudiante</th>@endunless
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Tipo</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Periodo</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Estado</th>
                        <th scope="col" class="py-2.5 pl-4 text-right font-semibold"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $row)
                        <tr class="border-b border-[var(--trz-linea)] hover:bg-[var(--trz-hilo-fondo)]">
                            <td class="py-3.5 pr-4 text-[var(--trz-texto-2)]">{{ $row['date'] }}</td>
                            @unless($isStudent)<td class="px-4 py-3.5 font-semibold text-[var(--trz-tinta)]">{{ $row['student'] }}</td>@endunless
                            <td class="px-4 py-3.5 {{ $isStudent ? 'font-semibold text-[var(--trz-tinta)]' : 'text-[var(--trz-tinta)]' }}">{{ $row['type'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-[var(--trz-texto-2)]">{{ $row['period'] }}</td>
                            <td class="px-4 py-3.5"><x-status-badge :text="$row['status']" :tone="$row['tone']" /></td>
                            <td class="whitespace-nowrap py-3.5 pl-4 text-right"><x-link-arrow :href="$row['url']" class="justify-end">Ver detalle</x-link-arrow></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <ul class="flex flex-col md:hidden">
                @foreach($requests as $row)
                    <li class="border-b border-[var(--trz-linea)] first:border-t">
                        <a href="{{ $row['url'] }}" class="flex flex-col gap-1.5 py-3.5 hover:bg-[var(--trz-hilo-fondo)]">
                            <span class="flex items-center justify-between gap-3">
                                <span class="text-base font-semibold text-[var(--trz-tinta)]">{{ $row['type'] }}</span>
                                <span class="shrink-0 text-[13px] text-[var(--trz-texto-2)]">{{ $row['date'] }}</span>
                            </span>
                            <span class="flex items-center justify-between gap-3">
                                <span class="text-[13px] text-[var(--trz-texto-2)]">@unless($isStudent){{ $row['student'] }} · @endunless{{ $row['period'] }}</span>
                                <x-status-badge :text="$row['status']" :tone="$row['tone']" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-pagination :paginator="$requests" noun="solicitudes" />
    </x-page>
</x-layouts.app>
