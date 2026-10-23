<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mi perfil · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Mi perfil</h1>
                <p class="text-sm text-[var(--text-secondary)]">Información personal y académica</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="px-4 py-[14px] border-b border-[var(--border-subtle)]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Información personal</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Nombre completo</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ $profile['name'] }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Correo electrónico</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ $profile['email'] }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Rol</label>
                                <p class="text-sm text-[var(--text-primary)] capitalize">{{ $profile['role'] }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Estado de cuenta</label>
                                <p class="text-sm text-[var(--text-primary)] {{ $profile['active'] === 'Activo' ? 'text-[var(--color-success)]' : 'text-[var(--color-danger)]' }}">{{ $profile['active'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @if($student)
                    <div class="mt-6 flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                        <div class="px-4 py-[14px] border-b border-[var(--border-subtle)]">
                            <h2 class="text-sm font-semibold text-[var(--text-primary)]">Información académica</h2>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach([$student['code'], $student['cohort'], $student['enrollment'], $student['tutor'], 'PAPA actual '.$student['papa']] as $line)
                                <p class="text-sm text-[var(--text-primary)]">{{ $line }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-1">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="px-4 py-[14px] border-b border-[var(--border-subtle)]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Acciones rápidas</h2>
                    </div>
                    <div class="p-4 flex flex-col gap-3">
                        <a href="/solicitudes" class="flex items-center gap-[9px] rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 py-[10px] text-sm font-medium text-[var(--color-primary-600)] hover:bg-[var(--surface-sunken)]">
                            <x-lucide-file-text class="h-4 w-4" />
                            Mis solicitudes
                        </a>
                        <a href="/experiencias" class="flex items-center gap-[9px] rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 py-[10px] text-sm font-medium text-[var(--color-primary-600)] hover:bg-[var(--surface-sunken)]">
                            <x-lucide-sparkles class="h-4 w-4" />
                            Mis experiencias
                        </a>
                        <a href="/trabajo-de-grado" class="flex items-center gap-[9px] rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 py-[10px] text-sm font-medium text-[var(--color-primary-600)] hover:bg-[var(--surface-sunken)]">
                            <x-lucide-book-open class="h-4 w-4" />
                            Mi trabajo de grado
                        </a>
                        <a href="/historia-academica" class="flex items-center gap-[9px] rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 py-[10px] text-sm font-medium text-[var(--color-primary-600)] hover:bg-[var(--surface-sunken)]">
                            <x-lucide-trending-up class="h-4 w-4" />
                            Mi historia académica
                        </a>
                        <a href="/practica" class="flex items-center gap-[9px] rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 py-[10px] text-sm font-medium text-[var(--color-primary-600)] hover:bg-[var(--surface-sunken)]">
                            <x-lucide-briefcase class="h-4 w-4" />
                            Mi práctica
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>