<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mi práctica · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Mi práctica</h1>
                <p class="text-sm text-[var(--text-secondary)]">Información de tu pasantía o práctica profesional</p>
            </div>
        </div>

        @if($internship)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="lg:col-span-1">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="px-4 py-[14px] border-b border-[var(--border-subtle)]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Detalles de la práctica</h2>
                    </div>
                    <div class="p-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Empresa</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ optional($internship->company)->nombre ?? 'No asignada' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Asesor</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ optional($internship->asesor)->name ?? 'No asignado' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Fecha de inicio</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ \App\Support\Labels::date($internship->fecha_inicio) }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Fecha de fin</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ $internship->fecha_fin ? \App\Support\Labels::date($internship->fecha_fin) : 'En curso' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Estado</label>
                                <x-status-badge :text="$finished ? 'Finalizada' : 'En curso'" :tone="$finished ? 'success' : 'info'" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-1">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="px-4 py-[14px] border-b border-[var(--border-subtle)]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Información adicional</h2>
                    </div>
                    <div class="p-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Jefe inmediato</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ $internship->jefe_nombre ? $internship->jefe_nombre.($internship->jefe_cargo ? ' · '.$internship->jefe_cargo : '') : 'No registrado' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Actividades</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ $internship->actividades ?? 'Sin actividades registradas.' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Apreciación del jefe</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ $internship->apreciacion_jefe ?? 'Pendiente' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Apreciación del asesor</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ $internship->apreciacion_asesor ?? 'Pendiente' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('internship.update') }}" class="flex max-w-3xl flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
            @csrf
            @method('PUT')
            <div class="border-b border-[var(--border-subtle)] px-4 py-[14px]">
                <h2 class="text-sm font-semibold text-[var(--text-primary)]">Actualizar mi práctica</h2>
                <p class="text-xs text-[var(--text-muted)]">Puedes cambiar el jefe inmediato y las actividades · RF-22</p>
            </div>
            <div class="flex flex-col gap-4 p-4">
                <div class="flex flex-col gap-4 sm:flex-row">
                    <div class="flex-1"><x-form-field label="Jefe inmediato *" name="jefe_nombre" :value="$internship->jefe_nombre" /></div>
                    <div class="flex-1"><x-form-field label="Cargo del jefe" name="jefe_cargo" :value="$internship->jefe_cargo" /></div>
                </div>
                <x-form-field label="Actividades" name="actividades" type="textarea" :rows="5" :value="$internship->actividades" placeholder="Describe las actividades que realizas…" />
                <div class="flex justify-end">
                    <x-button variant="primary" type="submit">Guardar cambios</x-button>
                </div>
            </div>
        </form>
        @else
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <x-ui-icon name="briefcase" class="h-16 w-16 text-[var(--text-muted)]" />
            <h2 class="text-lg font-semibold text-[var(--text-primary)] mt-4">No hay práctica registrada</h2>
            <p class="text-sm text-[var(--text-muted)] mt-2">Aún no se ha registrado información sobre tu práctica.</p>
        </div>
        @endif
    </div>
</x-layouts.app>