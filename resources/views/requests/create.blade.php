<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Nueva solicitud · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex items-center gap-[6px] text-sm text-[var(--text-secondary)]">
            <a href="/panel">Inicio</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <a href="{{ route('requests.index') }}">Mis solicitudes</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <span class="text-[var(--text-primary)]">Nueva solicitud</span>
        </div>

        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Nueva solicitud al comité asesor</h1>
            <p class="text-sm text-[var(--text-secondary)]">Los campos marcados con * son obligatorios · RF-25</p>
        </div>

        <div class="flex flex-col gap-5 lg:flex-row">
            <form method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data" class="flex flex-1 flex-col gap-[18px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6">
                @csrf
                @if($isStaff)
                    <x-form-field label="Código del estudiante *" name="codigo_estudiante" icon="user" placeholder="p. ej. 2020123" help="La solicitud se radica a nombre de este estudiante." />
                @endif

                <div class="flex flex-col gap-4 sm:flex-row">
                    <div class="flex-1">
                        <x-form-field label="Tipo de solicitud *" name="tipo" type="select" help="Cancelación de asignatura, reingreso, homologación, cupo de créditos…">
                            <option value="">Seleccione…</option>
                            @foreach($requestTypes as $id => $nombre)
                                <option value="{{ $id }}" @selected((string) old('tipo') === (string) $id)>{{ $nombre }}</option>
                            @endforeach
                        </x-form-field>
                        @if(count($existing))
                            <p class="mt-1 text-[11px] font-medium text-[var(--color-warning)]">Ya radicó este periodo: {{ implode(', ', $existing) }}</p>
                        @endif
                    </div>
                    <div class="sm:w-[300px]">
                        <x-form-field label="Periodo académico *" name="periodo" :value="$termLabel" :readonly="true" help="Se toma del periodo activo." />
                    </div>
                </div>

                <x-form-field
                    label="Descripción y justificación *"
                    name="descripcion"
                    type="textarea"
                    placeholder="Describa el motivo de la solicitud y los hechos que la sustentan…"
                    help="Mínimo 50 caracteres."
                />

                @php($fileErrors = collect($errors->get('soportes.*'))->flatten()->merge($errors->get('soportes'))->unique())
                <div class="flex flex-col gap-[6px]">
                    <label for="soportes" class="text-xs font-semibold text-[var(--text-secondary)]">Soportes</label>
                    <div class="flex flex-col items-center justify-center gap-[6px] rounded-[var(--radius-md)] border border-dashed border-[var(--border-default)] p-4">
                        <x-lucide-cloud-upload class="h-[22px] w-[22px] text-[var(--color-neutral-400)]" />
                        <p class="text-sm text-[var(--text-secondary)]">Seleccione los archivos de soporte</p>
                        <input id="soportes" name="soportes[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="text-sm text-[var(--text-secondary)]" />
                        <p class="text-xs text-[var(--text-muted)]">PDF, JPG o PNG · máximo 5 MB por archivo · hasta 5 archivos</p>
                    </div>
                    @foreach($fileErrors as $message)
                        <p class="text-[11px] font-medium text-[var(--color-danger)]">{{ $message }}</p>
                    @endforeach
                </div>

                @if($errors->any())
                    <div class="flex gap-[10px] rounded-[var(--radius-md)] bg-[var(--color-danger-bg)] p-3">
                        <x-lucide-circle-alert class="h-4 w-4 shrink-0 text-[var(--color-danger)]" />
                        <div class="flex flex-col gap-[2px]">
                            <p class="text-sm font-semibold text-[var(--color-danger)]">Revise los campos marcados</p>
                            <ul class="list-disc pl-4 text-xs text-[var(--text-secondary)]">
                                @foreach($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                            <p class="text-xs text-[var(--text-secondary)]">Los datos escritos se conservan (RNF-10).</p>
                        </div>
                    </div>
                @endif

                <div class="flex flex-col gap-[10px] sm:flex-row sm:items-center">
                    <div class="flex-1"></div>
                    <x-button variant="secondary" :href="route('requests.index')">Cancelar</x-button>
                    <x-button variant="primary" type="submit">Radicar solicitud</x-button>
                </div>
            </form>

            <div class="flex flex-col gap-4 lg:w-[320px]">
                <div class="flex flex-col gap-[14px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[18px]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">¿Qué pasa después?</h2>
                    @foreach($steps as $step)
                        <div class="flex gap-[11px]">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--color-primary-50)] text-xs font-semibold text-[var(--color-primary-700)]">
                                {{ $step['number'] }}
                            </div>
                            <div class="flex flex-col gap-[2px]">
                                <p class="text-sm font-medium text-[var(--text-primary)]">{{ $step['title'] }}</p>
                                <p class="text-xs text-[var(--text-secondary)]">{{ $step['detail'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex gap-[10px] rounded-[var(--radius-lg)] bg-[var(--color-info-bg)] p-[14px]">
                    <x-lucide-info class="h-4 w-4 shrink-0 text-[var(--color-info)]" />
                    <p class="text-sm text-[var(--text-secondary)]">Cada cambio de estado queda registrado con autor y fecha, de modo que la solicitud conserva su traza completa (RNF-07).</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
