@php($editing = $experience !== null)
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $editing ? 'Editar experiencia' : 'Registrar experiencia' }} · Trayectoria Estudiantil">
    <x-page>
        <x-page-header :title="$editing ? 'Editar experiencia' : 'Registrar experiencia'" subtitle="Quedará pendiente hasta que Coordinación la valide; una experiencia validada ya no puede modificarla el estudiante. Si la rechazan, al corregirla vuelve a quedar pendiente." :back="route('experiencias.index')" back-label="Experiencias" />

        @if($experience?->isRejected())
            <div role="alert" class="flex items-start gap-3.5 rounded-[var(--radius-lg)] border-[1.5px] border-[var(--trz-alerta)] bg-[var(--trz-alerta-fondo)] px-[18px] py-4">
                <x-ui-icon name="circle-x" class="mt-0.5 h-[22px] w-[22px] shrink-0 text-[var(--trz-alerta)]" />
                <div class="flex flex-col gap-0.5">
                    <p class="text-base font-bold text-[var(--trz-alerta)]">Rechazada por Coordinación</p>
                    <p class="text-[15px] text-[var(--trz-tinta)]">{{ $experience->motivo_rechazo }}</p>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 items-start gap-x-20 gap-y-12 lg:grid-cols-[minmax(0,1fr)_420px]">
            <div class="flex flex-col gap-6">
                <form method="POST" action="{{ $editing ? route('experiencias.update', $experience) : route('experiencias.store') }}" class="flex flex-col gap-6"
                    x-data="{ typeId: @js((string) old('experience_type_id', $experience?->experience_type_id)), categories: @js($typeCategories), byCategory: @js($fieldsByCategory), fields() { return this.byCategory[this.categories[this.typeId]] ?? {}; }, has(f) { return f in this.fields(); }, required(f) { return this.fields()[f] === true; } }"
                    @change="if ($event.target.name === 'experience_type_id') typeId = $event.target.value">
                    @csrf
                    @if($editing)
                        @method('PUT')
                    @endif

                    <x-form-field label="Tipo de experiencia *" name="experience_type_id" type="select">
                        <option value="">Seleccione el tipo</option>
                        @foreach($types as $category => $group)
                            <optgroup label="{{ $category }}">
                                @foreach($group as $type)
                                    <option value="{{ $type->id }}" @selected((string) old('experience_type_id', $experience?->experience_type_id) === (string) $type->id)>{{ $type->nombre }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </x-form-field>
                    <x-form-field label="Nombre *" name="nombre" :value="$experience?->nombre" placeholder="Ej. Semillero de investigación SIGMA" />
                    <x-form-field label="Descripción" name="descripcion" type="textarea" :rows="4" :value="$experience?->descripcion" placeholder="Describa la experiencia formativa…" />

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-form-field label="Fecha de inicio *" name="fecha_inicio" type="date" :value="$experience?->fecha_inicio?->format('Y-m-d')" />
                        <x-form-field label="Fecha de fin" name="fecha_fin" type="date" :value="$experience?->fecha_fin?->format('Y-m-d')" help="Déjela vacía si sigue en curso" />
                    </div>

                    <div x-show="Object.keys(fields()).length" x-cloak class="flex flex-col gap-5 border-t border-[var(--trz-linea)] pt-6">
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-[22px] font-semibold text-[var(--trz-tinta)]">Datos adicionales de este tipo</h2>
                            <p class="text-sm leading-relaxed text-[var(--trz-texto-2)]">Según el tipo elegido se piden estos datos adicionales; los marcados como obligatorios deben completarse.</p>
                        </div>
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div x-show="has('alcance')" x-cloak class="flex flex-col gap-1.5">
                                <x-form-field label="Alcance" name="alcance" :value="$experience?->alcance" placeholder="Ej. Nacional" />
                                <p x-show="required('alcance')" x-cloak class="text-[13px] text-[var(--trz-texto-2)]">Obligatorio para este tipo</p>
                            </div>
                            <div x-show="has('institucion')" x-cloak class="flex flex-col gap-1.5">
                                <x-form-field label="Institución" name="institucion" :value="$experience?->institucion" placeholder="Ej. Universidad Nacional" />
                                <p x-show="required('institucion')" x-cloak class="text-[13px] text-[var(--trz-texto-2)]">Obligatorio para este tipo</p>
                            </div>
                            <div x-show="has('convenio')" x-cloak class="flex flex-col gap-1.5">
                                <x-form-field label="Convenio" name="convenio" :value="$experience?->convenio" />
                            </div>
                            <div x-show="has('rol')" x-cloak class="flex flex-col gap-1.5">
                                <x-form-field label="Rol del estudiante" name="rol" :value="$experience?->rol" placeholder="Ej. Auxiliar de investigación" />
                                <p x-show="required('rol')" x-cloak class="text-[13px] text-[var(--trz-texto-2)]">Obligatorio para este tipo</p>
                            </div>
                            <div x-show="has('docente_id')" x-cloak class="flex flex-col gap-1.5">
                                <x-form-field label="Docente responsable" name="docente_id" type="select">
                                    <option value="">Sin docente asociado</option>
                                    @foreach($teachers as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('docente_id', $experience?->docente_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </x-form-field>
                                <p x-show="required('docente_id')" x-cloak class="text-[13px] text-[var(--trz-texto-2)]">Obligatorio para este tipo</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-1">
                        <x-button variant="primary" type="submit" class="min-h-12">{{ $editing ? 'Guardar cambios' : 'Registrar experiencia' }}</x-button>
                        <x-button variant="secondary" :href="route('experiencias.index')" class="min-h-12">Cancelar</x-button>
                    </div>
                </form>

                @if($editing)
                    <div class="border-t border-[var(--trz-linea)] pt-6">
                        <x-confirm title="¿Eliminar esta experiencia?" text="Se borrará «{{ $experience->nombre }}» de su historial. Esta acción no se puede deshacer." confirm="Eliminar experiencia" trigger="Eliminar experiencia" :action="route('experiencias.destroy', $experience)" />
                    </div>
                @endif
            </div>

            <aside class="flex flex-col gap-5" aria-labelledby="estados">
                <h2 id="estados" class="text-[22px] font-semibold text-[var(--trz-tinta)] sm:text-[26px]">Qué pasa con la experiencia</h2>
                @foreach([['warning', 'Pendiente', $editing && $experience?->isRejected() ? 'Vuelve a la cola de Coordinación al guardar.' : 'Queda a la espera de Coordinación. Puede editarla.'], ['success', 'Validada', 'Coordinación la aprueba y ya no puede modificarla.'], ['danger', 'Rechazada', 'Verá el motivo. Al corregirla vuelve a quedar pendiente.']] as [$tone, $label, $detail])
                    <div class="flex flex-col gap-2 border-b border-[var(--trz-linea)] pb-4">
                        <div><x-status-badge :text="$label" :tone="$tone" /></div>
                        <p class="text-[15px] leading-snug text-[var(--trz-texto-2)]">{{ $detail }}</p>
                    </div>
                @endforeach
            </aside>
        </div>
    </x-page>
</x-layouts.app>
