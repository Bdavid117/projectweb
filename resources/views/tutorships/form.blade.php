@php($editing = $tutorship !== null)
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $editing ? 'Editar tutoría' : 'Registrar tutoría' }} · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex items-center gap-[6px] text-sm text-[var(--text-secondary)]">
            <a href="/panel">Inicio</a>
            <x-ui-icon name="chevron-right" class="h-[13px] w-[13px]" />
            <a href="{{ route('students.index') }}">Estudiantes</a>
            <x-ui-icon name="chevron-right" class="h-[13px] w-[13px]" />
            <a href="{{ route('students.show', $student['id']) }}">{{ $student['name'] }}</a>
            <x-ui-icon name="chevron-right" class="h-[13px] w-[13px]" />
            <span class="text-[var(--text-primary)]">{{ $editing ? 'Editar tutoría' : 'Registrar tutoría' }}</span>
        </div>

        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">{{ $editing ? 'Editar tutoría' : 'Registrar tutoría' }}</h1>
            <p class="text-sm text-[var(--text-secondary)]">Queda asociada al estudiante y a su tutor, y no podrá editarse pasadas {{ $hours }} horas · RF-34</p>
        </div>

        <div class="flex flex-col gap-5 lg:flex-row">
            <div class="flex min-w-0 flex-1 flex-col gap-4">
                <form method="POST" action="{{ $editing ? route('tutorships.update', $tutorship) : route('tutorships.store', $student['id']) }}" class="flex flex-col gap-[18px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6">
                    @csrf
                    @if($editing)
                        @method('PUT')
                    @endif

                    <div class="flex items-center gap-3 rounded-[var(--radius-md)] bg-[var(--surface-page)] p-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-xs font-semibold text-[var(--color-primary-700)]">{{ $student['initials'] }}</div>
                        <div class="flex flex-col gap-[2px]">
                            <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $student['name'] }}</span>
                            <span class="text-xs text-[var(--text-muted)]">{{ $student['code'] }} · {{ $student['cohort'] }} · {{ $student['tutor'] }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="flex-1">
                            <x-form-field label="Fecha de la sesión *" name="fecha" type="date" icon="calendar" :value="$tutorship?->fecha?->format('Y-m-d') ?? now()->format('Y-m-d')" help="No puede ser posterior a hoy" />
                        </div>
                        <div class="flex-1">
                            <x-form-field label="Periodo académico" name="periodo" :value="$termLabel" :readonly="true" icon="lock" help="Se toma del calendario vigente" />
                        </div>
                    </div>

                    <x-form-field label="Motivo *" name="motivo" type="select" help="Seguimiento, orientación, revisión de avance, otro">
                        <option value="">Seleccione el motivo de la sesión</option>
                        @foreach($reasons as $reason)
                            <option value="{{ $reason }}" @selected(old('motivo', $tutorship?->motivo) === $reason)>{{ $reason }}</option>
                        @endforeach
                    </x-form-field>
                    <x-form-field label="Observaciones *" name="observaciones" type="textarea" :rows="6" :value="$tutorship?->observaciones" placeholder="Describa lo tratado en la sesión…" help="Mínimo 10 caracteres" />
                    <x-form-field label="Compromisos" name="compromisos" type="textarea" :rows="4" :value="$tutorship?->compromisos" placeholder="Acuerdos del estudiante y del tutor…" />

                    <div class="flex flex-col gap-[10px] sm:flex-row sm:items-center">
                        <div class="flex-1"></div>
                        <x-button variant="secondary" :href="route('students.show', $student['id'])">Cancelar</x-button>
                        <x-button variant="primary" type="submit">{{ $editing ? 'Guardar cambios' : 'Registrar tutoría' }}</x-button>
                    </div>
                </form>

                @if($editing)
                    <form method="POST" action="{{ route('tutorships.destroy', $tutorship) }}" class="flex justify-end">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('¿Eliminar esta tutoría?')" class="text-sm font-semibold text-[var(--color-danger)]">Eliminar tutoría</button>
                    </form>
                @endif
            </div>

            <div class="flex flex-col gap-4 lg:w-[320px]">
                <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[18px]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Historial reciente · RF-35</h2>
                    @forelse($history as $item)
                        <div class="flex flex-col gap-[2px] border-l-2 border-[var(--border-default)] pl-[10px]">
                            <span class="text-xs font-semibold text-[var(--text-primary)]">{{ $item['date'] }}</span>
                            <span class="text-xs text-[var(--text-muted)]">{{ $item['reason'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-[var(--text-muted)]">Sin tutorías registradas.</p>
                    @endforelse
                    <a href="{{ route('students.show', $student['id']) }}" class="text-sm font-semibold text-[var(--color-primary-600)]">Ver historial completo</a>
                </div>

                <div class="flex items-start gap-[10px] rounded-[var(--radius-lg)] bg-[var(--color-info-bg)] p-[14px]">
                    <x-ui-icon name="info" class="h-4 w-4 shrink-0 text-[var(--color-info)]" />
                    <p class="text-sm text-[var(--text-secondary)]">Podrá corregir esta sesión durante {{ $hours }} horas. Después queda inmutable para conservar la continuidad del acompañamiento.</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
