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
                                <p class="text-sm text-[var(--text-primary)]">{{ $internship->fecha_inicio ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Fecha de fin</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ $internship->fecha_fin ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Estado</label>
                                <x-status-badge :text="ucfirst($internship->estado ?? 'sin_definir')" :tone="'info'" />
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
                        @if($internship->observaciones)
                        <p class="text-sm text-[var(--text-secondary)]">{{ $internship->observaciones }}</p>
                        @else
                        <p class="text-sm text-[var(--text-muted)]">Sin observaciones registradas.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <x-lucide-briefcase class="h-16 w-16 text-[var(--text-muted)]" />
            <h2 class="text-lg font-semibold text-[var(--text-primary)] mt-4">No hay práctica registrada</h2>
            <p class="text-sm text-[var(--text-muted)] mt-2">Aún no se ha registrado información sobre tu práctica.</p>
        </div>
        @endif
    </div>
</x-layouts.app>