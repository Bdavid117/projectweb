<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Catálogos · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Catálogos" subtitle="Listas maestras que alimentan los formularios del sistema." />

        <div class="flex items-center gap-3 rounded-[var(--radius-lg)] bg-[var(--trz-hilo-fondo)] px-4 py-3.5">
            <x-ui-icon name="info" class="h-5 w-5 shrink-0 text-[var(--trz-hilo-texto)]" />
            <p class="text-[15px] leading-snug text-[var(--trz-tinta)]">Solo lectura por ahora. Dar de alta, editar y dar de baja cada catálogo está planeado.</p>
        </div>

        <div class="grid items-start gap-x-16 gap-y-8 lg:grid-cols-[220px_minmax(0,1fr)]">
            <nav aria-label="Catálogos" class="flex gap-2 overflow-x-auto pb-1 lg:sticky lg:top-24 lg:flex-col lg:gap-0 lg:overflow-visible lg:border-l-[3px] lg:border-[var(--trz-linea)] lg:pb-0">
                @foreach($catalogs as $catalog)
                    <a href="#{{ \Illuminate\Support\Str::slug($catalog['title']) }}" class="whitespace-nowrap rounded-full border-[1.5px] border-[var(--trz-borde-control)] px-4 py-2 text-[15px] font-semibold text-[var(--trz-tinta)] hover:bg-[var(--trz-hilo-fondo)] lg:rounded-none lg:border-0 lg:px-4 lg:py-2.5 lg:font-medium lg:text-[var(--trz-texto-2)]">{{ $catalog['title'] }}</a>
                @endforeach
            </nav>

            <div class="flex min-w-0 flex-col gap-14">
                @foreach($catalogs as $catalog)
                    <section id="{{ \Illuminate\Support\Str::slug($catalog['title']) }}" class="flex flex-col gap-3.5" aria-labelledby="h-{{ \Illuminate\Support\Str::slug($catalog['title']) }}">
                        <div class="flex items-baseline gap-3">
                            <h2 id="h-{{ \Illuminate\Support\Str::slug($catalog['title']) }}" class="text-2xl font-semibold text-[var(--trz-tinta)] sm:text-[26px]">{{ $catalog['title'] }}</h2>
                            <span class="text-sm text-[var(--trz-texto-2)]">{{ count($catalog['rows']) }} {{ count($catalog['rows']) === 1 ? 'registro' : 'registros' }}</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[440px] text-[15px]">
                                <thead>
                                    <tr class="border-b-[1.5px] border-[var(--trz-linea)] text-[13px] font-semibold text-[var(--trz-texto-2)]">
                                        @foreach($catalog['columns'] as $column)
                                            <th scope="col" class="py-2.5 pr-4 text-left font-semibold">{{ $column }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($catalog['rows'] as $row)
                                        <tr class="border-b border-[var(--trz-linea)]">
                                            @foreach($row as $cell)
                                                @if($loop->first)
                                                    <th scope="row" class="py-3.5 pr-4 text-left font-semibold text-[var(--trz-tinta)]">{{ $cell }}</th>
                                                @else
                                                    <td class="py-3.5 pr-4 text-[var(--trz-texto-2)]">{{ $cell }}</td>
                                                @endif
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr><td colspan="{{ count($catalog['columns']) }}" class="py-5 text-[var(--trz-texto-2)]">Sin registros.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </x-page>
</x-layouts.app>
