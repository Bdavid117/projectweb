<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mi tutor · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Mi tutor" :subtitle="'Usted decide quién puede ver su información como tutor · Periodo '.$termLabel" />

        @if($errors->has('tutor'))
            <x-flash tone="danger" :message="$errors->first('tutor')" />
        @endif

        @if(! $hasStudent)
            <x-empty-state kind="tutor" title="Su usuario no tiene una ficha de estudiante" text="Su usuario no tiene una ficha de estudiante, así que no puede compartir su información." />
        @else
            <x-frase>
                @if($current)
                    {{ $current['name'] }} ve su información desde el {{ $current['since'] }}.
                @else
                    Hoy no comparte su información con ningún tutor.
                @endif
                @if(count($pending) > 0)
                    Tiene {{ count($pending) === 1 ? '1 solicitud esperando' : count($pending).' solicitudes esperando' }} su respuesta.
                @endif
            </x-frase>

            <x-section title="Solicitudes pendientes" note="Si acepta, esa persona podrá ver su ficha, solicitudes, experiencias y tutorías, y registrar tutorías.">
                @forelse($pending as $row)
                    @if($loop->first)<ul class="flex flex-col border-y border-[var(--trz-linea)]">@endif
                    <li class="flex flex-col gap-4 py-[18px] sm:flex-row sm:items-center sm:justify-between sm:gap-6 {{ $loop->last ? '' : 'border-b border-[var(--trz-linea)]' }}">
                        <div class="flex min-w-0 items-start gap-4">
                            <x-stream-mark kind="acompanamiento" class="mt-1.5" />
                            <div class="flex min-w-0 flex-col gap-1">
                                <span class="text-lg font-semibold text-[var(--trz-tinta)]">{{ $row['tutor'] }}</span>
                                <span class="text-sm text-[var(--trz-texto-2)]">Enviada el {{ $row['sentAt'] }}</span>
                                @if($row['message'])
                                    <p class="text-[15px] text-[var(--trz-tinta)]">«{{ $row['message'] }}»</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-3">
                            <form method="POST" action="{{ $row['acceptUrl'] }}">
                                @csrf
                                <x-button variant="primary" type="submit" icon="circle-check">Aceptar</x-button>
                            </form>
                            <form method="POST" action="{{ $row['rejectUrl'] }}">
                                @csrf
                                <x-button variant="secondary" type="submit">Rechazar</x-button>
                            </form>
                        </div>
                    </li>
                    @if($loop->last)</ul>@endif
                @empty
                    <p class="border-y border-[var(--trz-linea)] py-5 text-[15px] text-[var(--trz-texto-2)]">No tiene solicitudes pendientes.</p>
                @endforelse
            </x-section>

            <x-section title="Tutor actual">
                @if($current)
                    <div class="flex flex-col gap-4 border-y border-[var(--trz-linea)] py-[18px] sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                        <div class="flex items-start gap-4">
                            <x-stream-mark kind="acompanamiento" class="mt-1.5" />
                            <div class="flex flex-col gap-1">
                                <span class="text-lg font-semibold text-[var(--trz-tinta)]">{{ $current['name'] }}</span>
                                <span class="text-sm text-[var(--trz-texto-2)]">Comparte su información desde el {{ $current['since'] }}</span>
                            </div>
                        </div>
                        <x-confirm title="¿Dejar de compartir su información?" :text="$current['name'].' perderá el acceso a su ficha, solicitudes, experiencias y tutorías de inmediato.'" confirm="Dejar de compartir" trigger="Dejar de compartir" trigger-icon="user-x" trigger-variant="secondary" :action="route('my-tutor.revoke')" />
                    </div>
                @else
                    <x-empty-state kind="tutor" title="No comparte su información con ningún tutor en este periodo." text="Cuando un profesor le envíe una solicitud, podrá aceptarla o rechazarla aquí." />
                @endif
            </x-section>

            @if(count($history) > 0)
                <x-section title="Historial">
                    <ul class="flex flex-col border-t border-[var(--trz-linea)]">
                        @foreach($history as $row)
                            <li class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--trz-linea)] py-3.5">
                                <span class="min-w-0 flex-1 text-base font-semibold text-[var(--trz-tinta)]">{{ $row['tutor'] }}</span>
                                <span class="text-[15px] text-[var(--trz-texto-2)]">{{ $row['date'] }}</span>
                                <x-status-badge :text="$row['status']" :tone="$row['tone']" />
                            </li>
                        @endforeach
                    </ul>
                </x-section>
            @endif
        @endif
    </x-page>
</x-layouts.app>
