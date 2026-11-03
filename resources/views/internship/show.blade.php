<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mi práctica · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Mi práctica" subtitle="Información de su pasantía o práctica profesional." />

        @if($internship)
            <x-frase>
                {{ $finished ? 'Hizo' : 'Hace' }} su práctica en {{ optional($internship->company)->nombre ?? 'una empresa sin asignar' }} desde el {{ \App\Support\Labels::date($internship->fecha_inicio) }}@if($internship->asesor), con asesoría de {{ $internship->asesor->name }}@endif.
                @if($internship->apreciacion_jefe === null || $internship->apreciacion_asesor === null)
                    {{ ($internship->apreciacion_jefe === null && $internship->apreciacion_asesor === null) ? 'Las dos apreciaciones siguen pendientes.' : 'Falta una apreciación.' }}
                @endif
            </x-frase>

            <div class="grid grid-cols-1 items-start gap-x-20 gap-y-12 lg:grid-cols-[minmax(0,1fr)_460px]">
                <div class="flex flex-col gap-10">
                    <x-section title="Detalles de la práctica">
                        <dl class="flex flex-col border-t border-[var(--trz-linea)]">
                            @foreach([['Empresa', optional($internship->company)->nombre ?? 'No asignada'], ['Asesor', optional($internship->asesor)->name ?? 'No asignado'], ['Fecha de inicio', \App\Support\Labels::date($internship->fecha_inicio)], ['Fecha de fin', $internship->fecha_fin ? \App\Support\Labels::date($internship->fecha_fin) : 'En curso']] as [$label, $value])
                                <div class="flex flex-col gap-1 border-b border-[var(--trz-linea)] py-3.5 sm:flex-row sm:items-start sm:gap-6">
                                    <dt class="text-sm text-[var(--trz-texto-2)] sm:w-[190px] sm:shrink-0 sm:pt-0.5">{{ $label }}</dt>
                                    <dd class="text-base font-semibold text-[var(--trz-tinta)]">{{ $value }}</dd>
                                </div>
                            @endforeach
                            <div class="flex flex-col gap-1 border-b border-[var(--trz-linea)] py-3 sm:flex-row sm:items-center sm:gap-6">
                                <dt class="text-sm text-[var(--trz-texto-2)] sm:w-[190px] sm:shrink-0">Estado</dt>
                                <dd><x-status-badge :text="$finished ? 'Finalizada' : 'En curso'" :tone="$finished ? 'success' : 'info'" :icon="$finished ? null : 'loader-circle'" /></dd>
                            </div>
                        </dl>
                    </x-section>

                    <x-section title="Información adicional">
                        <dl class="flex flex-col border-t border-[var(--trz-linea)]">
                            <div class="flex flex-col gap-1 border-b border-[var(--trz-linea)] py-3.5 sm:flex-row sm:items-start sm:gap-6">
                                <dt class="text-sm text-[var(--trz-texto-2)] sm:w-[190px] sm:shrink-0 sm:pt-0.5">Jefe inmediato</dt>
                                <dd class="text-base font-semibold text-[var(--trz-tinta)]">{{ $internship->jefe_nombre ? $internship->jefe_nombre.($internship->jefe_cargo ? ' · '.$internship->jefe_cargo : '') : 'No registrado' }}</dd>
                            </div>
                            <div class="flex flex-col gap-1 border-b border-[var(--trz-linea)] py-3.5 sm:flex-row sm:items-start sm:gap-6">
                                <dt class="text-sm text-[var(--trz-texto-2)] sm:w-[190px] sm:shrink-0 sm:pt-0.5">Actividades</dt>
                                <dd class="text-base leading-relaxed text-[var(--trz-tinta)]">{{ $internship->actividades ?? 'Sin actividades registradas.' }}</dd>
                            </div>
                            @foreach([['Apreciación del jefe', $internship->apreciacion_jefe], ['Apreciación del asesor', $internship->apreciacion_asesor]] as [$label, $value])
                                <div class="flex flex-col gap-1 border-b border-[var(--trz-linea)] py-3 sm:flex-row sm:items-start sm:gap-6">
                                    <dt class="text-sm text-[var(--trz-texto-2)] sm:w-[190px] sm:shrink-0 sm:pt-1.5">{{ $label }}</dt>
                                    <dd class="text-base leading-relaxed text-[var(--trz-tinta)]">@if($value === null)<x-status-badge text="Pendiente" tone="warning" />@else{{ $value }}@endif</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-section>
                </div>

                <form method="POST" action="{{ route('internship.update') }}" class="flex flex-col gap-5 rounded-[var(--radius-xl)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)] p-7">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-col gap-1.5">
                        <h2 class="text-[22px] sm:text-2xl font-semibold text-[var(--trz-tinta)]">Actualizar mi práctica</h2>
                        <p class="text-sm leading-snug text-[var(--trz-texto-2)]">Puede cambiar el jefe inmediato y las actividades.</p>
                    </div>
                    <x-form-field label="Jefe inmediato *" name="jefe_nombre" :value="$internship->jefe_nombre" />
                    <x-form-field label="Cargo del jefe" name="jefe_cargo" :value="$internship->jefe_cargo" />
                    <x-form-field label="Actividades" name="actividades" type="textarea" :rows="5" :value="$internship->actividades" placeholder="Describa las actividades que realiza…" />
                    <x-button variant="primary" type="submit" class="min-h-12 w-full">Guardar cambios</x-button>
                </form>
            </div>
        @else
            <x-empty-state kind="practica" title="No hay práctica registrada" text="Aún no se ha registrado información sobre su práctica." />
        @endif
    </x-page>
</x-layouts.app>
