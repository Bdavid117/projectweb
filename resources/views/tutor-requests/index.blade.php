<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Solicitar tutoría · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Solicitar tutoría" subtitle="Para ver la información de un estudiante y registrar tutorías, el estudiante debe aceptar su solicitud." />

        @if($errors->has('student_id') || $errors->has('mensaje'))
            <x-flash tone="danger" :message="$errors->first('student_id') ?: $errors->first('mensaje')" />
        @endif

        <x-section title="Buscar estudiante" :note="$searched ? null : 'Escriba al menos '.$minSearch.' letras del nombre o del código. Solo verá el nombre, el código y la cohorte hasta que el estudiante acepte.'">
            <x-filter-bar :action="route('tutor-requests.index')">
                <x-search-field name="q" :value="$search" placeholder="Nombre o código" label="Nombre o código del estudiante" />
                <x-button variant="primary" type="submit">Buscar</x-button>
            </x-filter-bar>

            @if($searched)
                <div class="flex flex-col">
                    @forelse($results as $row)
                        @if($loop->first)<h3 class="pb-1 text-xl font-semibold text-[var(--trz-tinta)]">{{ count($results) }} {{ count($results) === 1 ? 'resultado' : 'resultados' }} para «{{ $search }}»</h3><ul class="flex flex-col border-t border-[var(--trz-linea)]">@endif
                        <li class="flex flex-col gap-4 border-b border-[var(--trz-linea)] py-[18px] lg:flex-row lg:items-center lg:gap-6">
                            <div class="flex min-w-0 flex-col gap-0.5 lg:w-[380px] lg:shrink-0">
                                <span class="text-lg font-semibold text-[var(--trz-tinta)]">{{ $row['name'] }}</span>
                                <span class="text-sm text-[var(--trz-texto-2)]">Código {{ $row['code'] }} · Cohorte {{ $row['cohort'] }}</span>
                            </div>
                            @if($row['state'] === 'available')
                                <form method="POST" action="{{ route('tutor-requests.store') }}" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                                    @csrf
                                    <input type="hidden" name="student_id" value="{{ $row['id'] }}" />
                                    <input type="text" name="mensaje" maxlength="500" placeholder="Mensaje para el estudiante (opcional)" aria-label="Mensaje para {{ $row['name'] }}"
                                        class="h-[var(--control-height)] w-full flex-1 rounded-[var(--radius-md)] border-[1.5px] border-[var(--trz-borde-control)] bg-[var(--trz-superficie)] px-3.5 text-base text-[var(--trz-tinta)] placeholder:text-[var(--trz-texto-3)] hover:border-[var(--trz-tinta)] focus:border-[var(--trz-hilo)] md:text-[15px]" />
                                    <x-button variant="primary" type="submit" icon="user-plus">Enviar solicitud</x-button>
                                </form>
                            @elseif($row['state'] === 'pending')
                                <div class="lg:ml-auto"><x-status-badge text="Solicitud pendiente" tone="warning" /></div>
                            @else
                                <div class="lg:ml-auto"><x-status-badge text="Ya la acompaña" tone="success" /></div>
                            @endif
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <x-empty-state kind="solicitar" title="No se encontró ningún estudiante con «{{ $search }}»." text="Revise la ortografía o pruebe con el código." />
                    @endforelse
                </div>
            @endif
        </x-section>

        <x-section title="Mis solicitudes enviadas">
            @forelse($sent as $row)
                @if($loop->first)<ul class="flex flex-col border-t border-[var(--trz-linea)]">@endif
                <li class="flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-[var(--trz-linea)] py-4">
                    <div class="flex min-w-[200px] flex-1 flex-col gap-0.5">
                        <span class="text-base font-semibold text-[var(--trz-tinta)]">{{ $row['student'] }}</span>
                        <span class="text-sm text-[var(--trz-texto-2)]">Código {{ $row['code'] }} · enviada el {{ $row['sentAt'] }}@if($row['answeredAt'] !== '—') · respondida el {{ $row['answeredAt'] }}@endif</span>
                    </div>
                    <x-status-badge :text="$row['status']" :tone="$row['tone']" />
                    @if($row['canCancel'])
                        <form method="POST" action="{{ route('tutor-requests.destroy', $row['id']) }}">
                            @csrf
                            @method('DELETE')
                            <x-button variant="subtle" type="submit" class="min-h-10 !text-[var(--trz-alerta)] hover:!bg-[var(--trz-alerta-fondo)]">Retirar</x-button>
                        </form>
                    @elseif($row['profileUrl'])
                        <x-link-arrow :href="$row['profileUrl']">Ver ficha</x-link-arrow>
                    @endif
                </li>
                @if($loop->last)</ul>@endif
            @empty
                <x-empty-state kind="solicitar" title="Aún no ha enviado solicitudes." text="Busque a un estudiante para empezar." />
            @endforelse
            <x-pagination :paginator="$sent" noun="solicitudes" />
        </x-section>
    </x-page>
</x-layouts.app>
