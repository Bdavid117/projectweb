<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Nueva solicitud · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex items-center gap-[6px] text-sm text-[var(--text-secondary)]">
            <a href="/panel">Inicio</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <a href="/solicitudes/nueva">Mis solicitudes</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <span class="text-[var(--text-primary)]">Nueva solicitud</span>
        </div>

        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Nueva solicitud al comité asesor</h1>
            <p class="text-sm text-[var(--text-secondary)]">Los campos marcados con * son obligatorios · RF-25</p>
        </div>

        <div class="flex flex-col gap-5 lg:flex-row">
            <form method="POST" action="{{ route('requests.store') }}" class="flex flex-1 flex-col gap-[18px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6">
                @csrf
                <div class="flex flex-col gap-4 sm:flex-row">
                    <div class="flex-1">
                        <x-form-field label="Tipo de solicitud *" name="tipo" type="select" help="Cancelación de asignatura, reingreso, homologación, cupo de créditos…">
                            <option value="">Seleccione…</option>
                            @foreach($requestTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </x-form-field>
                    </div>
                    <div class="sm:w-[300px]">
                        <x-form-field label="Periodo académico *" name="periodo" value="2026-2S" help="Se toma del periodo activo." />
                    </div>
                </div>

                <x-form-field
                    label="Descripción y justificación *"
                    name="descripcion"
                    type="textarea"
                    placeholder="Describa el motivo de la solicitud y los hechos que la sustentan…"
                    help="Mínimo 50 caracteres."
                />

                <div class="flex flex-col gap-[6px]">
                    <label class="text-xs font-semibold text-[var(--text-secondary)]">Soportes</label>
                    <div class="flex h-24 flex-col items-center justify-center gap-[5px] rounded-[var(--radius-md)] border border-dashed border-[var(--border-default)]">
                        <x-lucide-cloud-upload class="h-[22px] w-[22px] text-[var(--color-neutral-400)]" />
                        <p class="text-sm text-[var(--text-secondary)]">Arrastre los archivos aquí o haga clic para seleccionar</p>
                        <p class="text-xs text-[var(--text-muted)]">PDF, JPG o PNG · máximo 5 MB por archivo</p>
                    </div>
                    <div class="flex items-center gap-[10px] rounded-[var(--radius-md)] border border-[var(--border-subtle)] p-[10px]">
                        <x-lucide-file-text class="h-4 w-4 text-[var(--color-neutral-500)]" />
                        <span class="flex-1 text-sm text-[var(--text-primary)]">soporte-medico.pdf</span>
                        <span class="text-xs text-[var(--text-muted)]">1,2 MB</span>
                        <x-lucide-x class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
                    </div>
                </div>

                <div class="flex gap-[10px] rounded-[var(--radius-md)] bg-[var(--color-danger-bg)] p-3">
                    <x-lucide-circle-alert class="h-4 w-4 shrink-0 text-[var(--color-danger)]" />
                    <div class="flex flex-col gap-[2px]">
                        <p class="text-sm font-semibold text-[var(--color-danger)]">Falta un campo obligatorio</p>
                        <p class="text-xs text-[var(--text-secondary)]">Seleccione el tipo de solicitud antes de continuar. Los datos escritos se conservan (RNF-10).</p>
                    </div>
                </div>

                <div class="flex flex-col gap-[10px] sm:flex-row sm:items-center">
                    <div class="flex-1"></div>
                    <x-button variant="secondary">Cancelar</x-button>
                    <x-button variant="secondary">Guardar borrador</x-button>
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
