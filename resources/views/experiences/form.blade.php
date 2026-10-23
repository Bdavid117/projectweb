@php($editing = $experience !== null)
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $editing ? 'Editar experiencia' : 'Registrar experiencia' }} · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex items-center gap-[6px] text-sm text-[var(--text-secondary)]">
            <a href="/panel">Inicio</a>
            <x-ui-icon name="chevron-right" class="h-[13px] w-[13px]" />
            <a href="{{ route('experiencias.index') }}">Experiencias</a>
            <x-ui-icon name="chevron-right" class="h-[13px] w-[13px]" />
            <span class="text-[var(--text-primary)]">{{ $editing ? 'Editar experiencia' : 'Registrar experiencia' }}</span>
        </div>

        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">{{ $editing ? 'Editar experiencia' : 'Registrar experiencia' }}</h1>
            <p class="text-sm text-[var(--text-secondary)]">Quedará pendiente hasta que Coordinación la valide; una experiencia validada ya no puede modificarla el estudiante.</p>
        </div>

        <form method="POST" action="{{ $editing ? route('experiencias.update', $experience) : route('experiencias.store') }}" class="flex max-w-3xl flex-col gap-[18px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6">
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

            <div class="flex flex-col gap-4 sm:flex-row">
                <div class="flex-1"><x-form-field label="Fecha de inicio *" name="fecha_inicio" type="date" :value="$experience?->fecha_inicio?->format('Y-m-d')" /></div>
                <div class="flex-1"><x-form-field label="Fecha de fin" name="fecha_fin" type="date" :value="$experience?->fecha_fin?->format('Y-m-d')" help="Déjela vacía si sigue en curso" /></div>
            </div>
            <div class="flex flex-col gap-4 sm:flex-row">
                <div class="flex-1"><x-form-field label="Alcance" name="alcance" :value="$experience?->alcance" placeholder="Ej. Nacional" /></div>
                <div class="flex-1"><x-form-field label="Institución" name="institucion" :value="$experience?->institucion" placeholder="Ej. Universidad Nacional" /></div>
            </div>
            <div class="flex flex-col gap-4 sm:flex-row">
                <div class="flex-1"><x-form-field label="Convenio" name="convenio" :value="$experience?->convenio" /></div>
                <div class="flex-1"><x-form-field label="Rol del estudiante" name="rol" :value="$experience?->rol" placeholder="Ej. Auxiliar de investigación" /></div>
            </div>
            <x-form-field label="Docente responsable" name="docente_id" type="select">
                <option value="">Sin docente asociado</option>
                @foreach($teachers as $id => $name)
                    <option value="{{ $id }}" @selected((string) old('docente_id', $experience?->docente_id) === (string) $id)>{{ $name }}</option>
                @endforeach
            </x-form-field>

            <div class="flex flex-col gap-[10px] sm:flex-row sm:items-center">
                <div class="flex-1"></div>
                <x-button variant="secondary" :href="route('experiencias.index')">Cancelar</x-button>
                <x-button variant="primary" type="submit">{{ $editing ? 'Guardar cambios' : 'Registrar experiencia' }}</x-button>
            </div>
        </form>

        @if($editing)
            <form method="POST" action="{{ route('experiencias.destroy', $experience) }}" class="flex max-w-3xl justify-end">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="return confirm('¿Eliminar esta experiencia?')" class="text-sm font-semibold text-[var(--color-danger)]">Eliminar experiencia</button>
            </form>
        @endif
    </div>
</x-layouts.app>
