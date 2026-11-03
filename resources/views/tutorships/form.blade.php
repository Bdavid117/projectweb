@php($editing = $tutorship !== null)
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $editing ? 'Editar tutoría' : 'Registrar tutoría' }} · Trayectoria Estudiantil">
    <x-page>
        <x-page-header :title="$editing ? 'Editar tutoría' : 'Registrar tutoría'" :subtitle="'Queda asociada al estudiante y a su tutor, y no podrá editarse pasadas '.$hours.' horas.'" :back="route('students.show', $student['id'])" back-label="{{ $student['name'] }}" />

        <div class="grid grid-cols-1 items-start gap-x-20 gap-y-12 lg:grid-cols-[minmax(0,1fr)_420px]">
            <div class="flex flex-col gap-6">
                <form method="POST" action="{{ $editing ? route('tutorships.update', $tutorship) : route('tutorships.store', $student['id']) }}" class="flex flex-col gap-6">
                    @csrf
                    @if($editing)
                        @method('PUT')
                    @endif

                    <div class="flex items-center gap-4 border-b border-[var(--trz-linea)] pb-5">
                        <span class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-full bg-[var(--trz-hilo-fondo)] text-base font-bold text-[var(--trz-hilo-texto)]" aria-hidden="true">{{ $student['initials'] }}</span>
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <span class="font-display text-[22px] font-semibold leading-tight text-[var(--trz-tinta)]">{{ $student['name'] }}</span>
                            <span class="text-[15px] text-[var(--trz-texto-2)]">{{ $student['code'] }} · {{ $student['cohort'] }} · {{ $student['tutor'] }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-form-field label="Fecha de la sesión *" name="fecha" type="date" icon="calendar" :value="$tutorship?->fecha?->format('Y-m-d') ?? now()->format('Y-m-d')" help="No puede ser posterior a hoy" />
                        <x-form-field label="Periodo académico" name="periodo" :value="$termLabel" :readonly="true" icon="lock" help="Se toma del calendario vigente" />
                    </div>

                    <x-form-field label="Motivo *" name="motivo" type="select" help="Seguimiento, orientación, revisión de avance, otro">
                        <option value="">Seleccione el motivo de la sesión</option>
                        @foreach($reasons as $reason)
                            <option value="{{ $reason }}" @selected(old('motivo', $tutorship?->motivo) === $reason)>{{ $reason }}</option>
                        @endforeach
                    </x-form-field>
                    <x-form-field label="Observaciones *" name="observaciones" type="textarea" :rows="6" :value="$tutorship?->observaciones" placeholder="Describa lo tratado en la sesión…" help="Mínimo 10 caracteres" />
                    <x-form-field label="Compromisos" name="compromisos" type="textarea" :rows="4" :value="$tutorship?->compromisos" placeholder="Acuerdos del estudiante y del tutor…" />

                    <div class="flex flex-wrap gap-3 pt-1">
                        <x-button variant="primary" type="submit" class="min-h-12">{{ $editing ? 'Guardar cambios' : 'Registrar tutoría' }}</x-button>
                        <x-button variant="secondary" :href="route('students.show', $student['id'])" class="min-h-12">Cancelar</x-button>
                    </div>
                </form>

                @if($editing)
                    <div class="border-t border-[var(--trz-linea)] pt-6">
                        <x-confirm title="¿Eliminar esta tutoría?" :text="'Se borrará la sesión del '.$tutorship->fecha->format('d/m/Y').' con '.$student['name'].'. Esta acción no se puede deshacer.'" confirm="Eliminar tutoría" trigger="Eliminar tutoría" :action="route('tutorships.destroy', $tutorship)" />
                    </div>
                @endif
            </div>

            <aside class="flex flex-col gap-8">
                <x-section title="Historial reciente">
                    @forelse($history as $item)
                        @if($loop->first)<ul class="flex flex-col gap-4">@endif
                        <li class="flex items-start gap-3.5">
                            <x-stream-mark kind="acompanamiento" class="mt-1.5" />
                            <div class="flex flex-col gap-0.5">
                                <span class="text-base font-semibold text-[var(--trz-tinta)]">{{ $item['date'] }}</span>
                                <span class="text-[15px] text-[var(--trz-texto-2)]">{{ $item['reason'] }}</span>
                            </div>
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <p class="text-[15px] text-[var(--trz-texto-2)]">Sin tutorías registradas.</p>
                    @endforelse
                    <x-link-arrow :href="route('students.show', $student['id'])">Ver historial completo</x-link-arrow>
                </x-section>

                <div class="flex items-start gap-3 rounded-[var(--radius-lg)] bg-[var(--trz-hilo-fondo)] p-4">
                    <x-ui-icon name="hourglass" class="mt-0.5 h-5 w-5 shrink-0 text-[var(--trz-hilo-texto)]" />
                    <p class="text-[15px] leading-snug text-[var(--trz-tinta)]">Podrá corregir esta sesión durante {{ $hours }} horas. Después queda inmutable para conservar la continuidad del acompañamiento.</p>
                </div>
            </aside>
        </div>
    </x-page>
</x-layouts.app>
