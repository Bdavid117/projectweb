<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $isStudent ? 'Mis experiencias' : 'Experiencias' }} · Trayectoria Estudiantil">
    <x-page>
        <x-page-header :title="$isStudent ? 'Mis experiencias formativas' : 'Experiencias formativas'" :subtitle="$experiences->total().' registradas · las valida Coordinación'.(($canCreate || $canReview) ? '' : ' · solo lectura')">
            @if($canCreate)
                <x-slot:actions>
                    <x-button variant="primary" icon="plus" :href="route('experiencias.create')">Registrar experiencia</x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        @unless($canCreate || $canReview)
            <div class="flex items-center gap-3 rounded-[var(--radius-lg)] bg-[var(--trz-hilo-fondo)] px-4 py-3.5">
                <x-ui-icon name="eye" class="h-5 w-5 shrink-0 text-[var(--trz-hilo-texto)]" />
                <p class="text-[15px] leading-snug text-[var(--trz-tinta)]">Vista de solo lectura: validar o rechazar es de Coordinación.</p>
            </div>
        @endunless

        <x-filter-bar :action="route('experiencias.index')">
            <div class="w-full sm:w-[240px]"><x-filter-select name="estado" all="Estado: todos" :options="$statusOptions" :selected="$filters['estado']" /></div>
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </x-filter-bar>

        @if($experiences->isEmpty())
            <x-empty-state kind="experiencias" title="No hay experiencias que mostrar." :text="$canCreate ? 'Registre una y Coordinación la validará.' : null">
                @if($canCreate)<x-button variant="primary" icon="plus" :href="route('experiencias.create')">Registrar experiencia</x-button>@endif
            </x-empty-state>
        @else
            <ul class="flex flex-col border-t border-[var(--trz-linea)]">
                @foreach($experiences as $row)
                    @php
                        [$badgeText, $badgeTone] = match ($row['status']) {
                            'validada' => ['Validada', 'success'],
                            'rechazada' => ['Rechazada', 'danger'],
                            default => ['Pendiente', 'warning'],
                        };
                    @endphp
                    <li class="flex flex-col gap-3.5 border-b border-[var(--trz-linea)] py-[18px]" x-data="{ rejecting: false }">
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-3 sm:flex-nowrap">
                            <x-stream-mark kind="experiencia" :filled="$row['status'] === 'validada'" class="shrink-0" />
                            <div class="flex min-w-0 flex-1 basis-[60%] flex-col gap-1 sm:basis-auto">
                                <span class="text-lg font-semibold leading-snug text-[var(--trz-tinta)]">{{ $row['name'] }}</span>
                                <span class="text-sm leading-snug text-[var(--trz-texto-2)]">{{ $row['type'] }} · {{ $row['period'] }}@unless($isStudent) · {{ $row['student'] }}@endunless</span>
                                @if($row['reason'])
                                    <span class="mt-1 flex items-start gap-1.5 text-sm text-[var(--trz-alerta)]"><x-ui-icon name="message-square" class="mt-0.5 h-3.5 w-3.5 shrink-0" />Motivo del rechazo: {{ $row['reason'] }}</span>
                                @endif
                            </div>
                            <x-status-badge :text="$badgeText" :tone="$badgeTone" />
                            <div class="flex shrink-0 items-center gap-2">
                                @if($row['canValidate'])
                                    <form method="POST" action="{{ route('experiencias.validate', $row['id']) }}">
                                        @csrf
                                        <x-button variant="primary" type="submit" icon="check" class="min-h-10">Validar</x-button>
                                    </form>
                                @endif
                                @if($row['canReject'])
                                    <x-button variant="subtle" class="min-h-10 !text-[var(--trz-alerta)] hover:!bg-[var(--trz-alerta-fondo)]" ::aria-expanded="rejecting" @click="rejecting = !rejecting">Rechazar</x-button>
                                @endif
                                @if($row['canEdit'])
                                    <x-link-arrow :href="route('experiencias.edit', $row['id'])" icon="pencil" class="min-h-10">Editar</x-link-arrow>
                                @endif
                            </div>
                        </div>
                        @if($row['canReject'])
                            <form method="POST" action="{{ route('experiencias.reject', $row['id']) }}" x-show="rejecting" x-cloak x-transition.opacity class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)] p-5">
                                @csrf
                                <label for="motivo-{{ $row['id'] }}" class="text-sm font-semibold text-[var(--trz-tinta)]">Motivo del rechazo</label>
                                <input id="motivo-{{ $row['id'] }}" type="text" name="motivo" required maxlength="1000" placeholder="Explique al estudiante qué debe corregir" aria-label="Motivo del rechazo"
                                    class="h-[var(--control-height)] w-full rounded-[var(--radius-md)] border-[1.5px] border-[var(--trz-borde-control)] bg-[var(--trz-superficie)] px-3.5 text-base text-[var(--trz-tinta)] hover:border-[var(--trz-tinta)] focus:border-[var(--trz-hilo)] md:text-[15px]" />
                                <p class="text-[13px] text-[var(--trz-texto-2)]">El estudiante verá este motivo y podrá corregir la experiencia.</p>
                                <div class="flex flex-wrap gap-3">
                                    <x-button variant="danger" type="submit">Rechazar experiencia</x-button>
                                    <x-button variant="secondary" @click="rejecting = false">Cancelar</x-button>
                                </div>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        <x-pagination :paginator="$experiences" noun="experiencias" />
    </x-page>
</x-layouts.app>
