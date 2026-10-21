<!-- Experiences form view (create/edit) -->
<x-layouts.app>

@section('content')

<div class="max-w-2xl mx-auto py-8">

    <x-card>
        <x-card-header>
            @if($experience)
                <h2 class="text-xl font-semibold">Editar experiencia</h2>
            @else
                <h2 class="text-xl font-semibold">Registrar nueva experiencia</h2>
            @endif
        </x-card-header>

        <x-card-content>
            <form method="POST" action="{{ $experience ? route('experiencias.update', $experience->id) : route('experiencias.store') }}">
                @method($experience ? 'PUT' : 'POST')
                @csrf

                <div class="grid gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-muted mb-1">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre', $experience?->nombre) }}" required class="w-full rounded border-input bg-background p-2"
                            placeholder="Ej. Práctica profesional">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted mb-1">Descripción <span class="text-red-500">*</span></label>
                        <textarea name="descripcion" rows="3" required class="w-full rounded border-input bg-background p-2"
                            placeholder="Describa la experiencia formativa">{{ old('descripcion', $experience?->descripcion) }}</textarea>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-muted mb-1">Fecha de inicio <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', $experience?->fecha_inicio->format('Y-m-d') ?? '') }}" required class="w-full rounded border-input bg-background p-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted mb-1">Fecha de fin <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha_fin" value="{{ old('fecha_fin', $experience?->fecha_fin->format('Y-m-d') ?? '') }}" required class="w-full rounded border-input bg-background p-2">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-muted mb-1">Alcance</label>
                        <input type="text" name="alcance" value="{{ old('alcance', $experience?->alcance) }}" class="w-full rounded border-input bg-background p-2"
                            placeholder="Ej. Curricular, Extracurricular">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted mb-1">Institución</label>
                        <input type="text" name="institucion" value="{{ old('institucion', $experience?->institucion) }}" class="w-full rounded border-input bg-background p-2"
                            placeholder="Ej. Universidad Nacional">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-muted mb-1">Convenio</label>
                        <input type="text" name="convenio" value="{{ old('convenio', $experience?->convenio) }}" class="w-full rounded border-input bg-background p-2"
                            placeholder="Número de convenio">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted mb-1">Docente</label>
                        <select name="docente_id" class="w-full rounded border-input bg-background p-2">
                            <option value="">-- Seleccione --</option>
                            @foreach(User::where('role', 'profesor')->get() as $user)
                                <option value="{{ $user->id }}" {{ $user->id == $experience?->docente_id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Rol</label>
                    <input type="text" name="rol" value="{{ old('rol', $experience?->rol) }}" class="w-full rounded border-input bg-background p-2"
                        placeholder="Ej. Tutor, Evaluador">
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('experiencias.index') }}" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="btn btn-primary">{{ $experience ? 'Actualizar' : 'Registrar' }}</button>
                </div>
            </form>
        </x-card-content>
    </x-card>

</div>

@stop