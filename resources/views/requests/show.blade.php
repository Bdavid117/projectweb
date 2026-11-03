@php
    $isStudent = auth()->user()?->role === \App\Enums\Role::Estudiante;
@endphp

<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Solicitud {{ $solicitud['number'] }} · Trayectoria Estudiantil">
    <x-page>
        <header class="flex flex-col gap-5">
            <x-back-link :href="route('requests.index')">{{ $isStudent ? 'Mis solicitudes' : 'Solicitudes' }}</x-back-link>
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between md:gap-12">
                <div class="flex min-w-0 flex-col gap-2.5">
                    <h1 class="text-[32px] font-semibold leading-[1.08] text-[var(--trz-tinta)] sm:text-[42px]">Solicitud {{ $solicitud['number'] }}</h1>
                    <p class="max-w-[72ch] text-base leading-[1.45] text-[var(--trz-texto-2)] sm:text-lg">
                        {{ $solicitud['type'] }}. Radicada el {{ $solicitud['filedAt'] }}
                        @unless($isStudent) por {{ $solicitud['student'] }} (código {{ $solicitud['code'] }})@endunless, periodo {{ $solicitud['term'] }}.
                    </p>
                </div>
                <div class="shrink-0"><x-status-badge :text="$solicitud['status']" :tone="$solicitud['tone']" /></div>
            </div>
        </header>

        @if($errors->has('estado'))
            <x-flash tone="danger" :message="$errors->first('estado')" />
        @endif

        <div class="grid items-start gap-x-20 gap-y-12 lg:grid-cols-[minmax(0,1fr)_420px]">
            <div class="flex flex-col gap-11">
                <x-section title="Descripción de la solicitud">
                    <p class="whitespace-pre-line text-[17px] leading-[1.6] text-[var(--trz-tinta)]">{{ $solicitud['description'] }}</p>
                </x-section>

                <x-section :title="'Soportes adjuntos ('.count($attachments).')'">
                    @forelse($attachments as $file)
                        @if($loop->first)<ul class="flex flex-col border-t border-[var(--trz-linea)]">@endif
                        <li class="flex items-center gap-3.5 border-b border-[var(--trz-linea)] py-3.5">
                            <x-ui-icon name="file-text" class="h-[22px] w-[22px] shrink-0 text-[var(--trz-texto-2)]" />
                            <span class="flex min-w-0 flex-1 flex-col">
                                <span class="truncate text-base font-semibold text-[var(--trz-tinta)]">{{ $file['name'] }}</span>
                                <span class="text-[13px] text-[var(--trz-texto-2)]">{{ $file['size'] }}</span>
                            </span>
                            <a href="{{ $file['url'] }}" class="inline-flex shrink-0 items-center gap-1.5 text-[15px] font-semibold text-[var(--trz-hilo-texto)] hover:underline"><x-ui-icon name="download" class="h-[17px] w-[17px]" />Descargar</a>
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <p class="border-y border-[var(--trz-linea)] py-4 text-[15px] text-[var(--trz-texto-2)]">Esta solicitud no tiene soportes adjuntos.</p>
                    @endforelse
                </x-section>

                <x-section title="Recorrido de la solicitud">
                    <x-thread :steps="$trail" />
                </x-section>
            </div>

            <div class="flex flex-col gap-9">
                @if($studentCard)
                    <x-section title="Estudiante">
                        <dl class="flex flex-col border-t border-[var(--trz-linea)]">
                            @foreach([['Nombre', $studentCard['name']], ['Código', str_replace('Código ', '', $studentCard['code'])], ['Cohorte', str_replace('Cohorte ', '', $studentCard['cohort'])], ['PAPA actual', $studentCard['papa']], ['Tutor', str_replace('Tutor asignado: ', '', $studentCard['tutor'])]] as [$label, $value])
                                <div class="flex items-center justify-between gap-4 border-b border-[var(--trz-linea)] py-[11px]">
                                    <dt class="text-sm text-[var(--trz-texto-2)]">{{ $label }}</dt>
                                    <dd class="text-right text-[15px] font-semibold text-[var(--trz-tinta)]">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <x-link-arrow :href="route('students.show', $studentCard['id'])">Ver ficha del estudiante</x-link-arrow>
                    </x-section>
                @endif

                @if($repeatedWarning)
                    <div class="flex items-start gap-3 rounded-[var(--radius-lg)] bg-[var(--trz-hito-fondo)] p-4">
                        <x-ui-icon name="triangle-alert" class="mt-0.5 h-5 w-5 shrink-0 text-[var(--trz-hito-texto)]" />
                        <p class="text-[15px] leading-snug text-[var(--trz-tinta)]">{{ $repeatedWarning }}</p>
                    </div>
                @endif

                @if($decision)
                    <x-section title="Decisión del comité">
                        <dl class="flex flex-col border-t border-[var(--trz-linea)]">
                            <div class="flex items-center justify-between gap-4 border-b border-[var(--trz-linea)] py-2.5">
                                <dt class="text-sm text-[var(--trz-texto-2)]">Decisión</dt>
                                <dd><x-status-badge :text="$decision['text']" :tone="$decision['tone']" /></dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-b border-[var(--trz-linea)] py-3">
                                <dt class="text-sm text-[var(--trz-texto-2)]">Acta</dt>
                                <dd class="text-[15px] font-semibold text-[var(--trz-tinta)]">{{ $decision['acta'] ?? '—' }}</dd>
                            </div>
                        </dl>
                        @if($decision['observations'])
                            <p class="text-[15px] leading-relaxed text-[var(--trz-tinta)]">{{ $decision['observations'] }}</p>
                        @endif
                    </x-section>
                @endif

                @if($canReview)
                    <form method="POST" action="{{ route('requests.review', $solicitud['id']) }}">
                        @csrf
                        <x-section title="Revisión del comité" note="La solicitud está radicada. Pásela a revisión para poder registrar la decisión.">
                            <x-button variant="primary" type="submit" class="min-h-12 w-full">Pasar a revisión</x-button>
                        </x-section>
                    </form>
                @endif

                @if($canDecide)
                    <form method="POST" action="{{ route('requests.decision', $solicitud['id']) }}" class="flex flex-col gap-5">
                        @csrf
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-2xl font-semibold text-[var(--trz-tinta)] sm:text-[26px]">Registrar decisión</h2>
                            <x-status-badge text="Solo Coordinación" tone="restricted" />
                        </div>
                        <x-form-field label="Decisión *" name="decision" type="select" help="Aprobada, rechazada o aplazada">
                            <option value="">Seleccione…</option>
                            @foreach($decisionOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('decision') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-form-field>
                        <x-form-field label="Número de acta *" name="numero_acta" placeholder="p. ej. 12-2026" help="Acta del comité o del consejo" />
                        <x-form-field label="Observaciones" name="observaciones" type="textarea" :rows="4" placeholder="Motivo de la decisión…" />
                        <x-button variant="primary" type="submit" class="min-h-12 w-full">Registrar decisión</x-button>
                    </form>
                @endif
            </div>
        </div>
    </x-page>
</x-layouts.app>
