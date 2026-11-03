<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Bitácora · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Bitácora" subtitle="Registro de cambios de estado en solicitudes, con responsable y fecha." />

        @if($entries->isEmpty())
            <x-empty-state kind="bitacora" title="Aún no hay actividad registrada." text="Los cambios de estado de las solicitudes aparecerán aquí con responsable y fecha." />
        @else
            <table class="hidden w-full text-[15px] md:table">
                <caption class="sr-only">Bitácora de cambios de estado</caption>
                <thead>
                    <tr class="border-b-[1.5px] border-[var(--trz-linea)] text-[13px] font-semibold text-[var(--trz-texto-2)]">
                        <th scope="col" class="py-2.5 pr-4 text-left font-semibold">Fecha</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Usuario</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Registro</th>
                        <th scope="col" class="py-2.5 pl-4 text-left font-semibold">Cambio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        <tr class="border-b border-[var(--trz-linea)]">
                            <td class="py-3.5 pr-4 text-[var(--trz-texto-2)]">{{ $entry['when'] }}</td>
                            <td class="px-4 py-3.5 text-[var(--trz-texto-2)]">{{ $entry['who'] }}</td>
                            <td class="px-4 py-3.5"><a href="{{ $entry['url'] }}" class="font-semibold text-[var(--trz-tinta)] hover:text-[var(--trz-hilo-texto)] hover:underline">{{ $entry['what'] }}</a></td>
                            <td class="py-3.5 pl-4">
                                <span class="inline-flex items-center gap-2 text-sm"><span class="text-[var(--trz-texto-2)]">{{ $entry['from'] }}</span><x-ui-icon name="arrow-right" class="h-3.5 w-3.5 text-[var(--trz-texto-3)]" /><span class="font-semibold text-[var(--trz-tinta)]">{{ $entry['to'] }}</span></span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <ul class="flex flex-col md:hidden">
                @foreach($entries as $entry)
                    <li class="flex flex-col gap-1 border-b border-[var(--trz-linea)] py-3.5 first:border-t">
                        <a href="{{ $entry['url'] }}" class="text-[15px] font-semibold text-[var(--trz-tinta)] hover:underline max-md:-my-3 max-md:py-3">{{ $entry['what'] }}</a>
                        <span class="text-[13px] text-[var(--trz-texto-2)]">{{ $entry['when'] }} · {{ $entry['who'] }}</span>
                        <span class="inline-flex items-center gap-2 text-[13px]"><span class="text-[var(--trz-texto-2)]">{{ $entry['from'] }}</span><x-ui-icon name="arrow-right" class="h-3.5 w-3.5 text-[var(--trz-texto-3)]" /><span class="font-bold text-[var(--trz-tinta)]">{{ $entry['to'] }}</span></span>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-pagination :paginator="$entries" noun="registros" />
    </x-page>
</x-layouts.app>
