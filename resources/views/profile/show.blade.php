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
                <form method="POST" action="{{ route('profile.update') }}" class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    @csrf
                    @method('PUT')
                    <div class="px-4 py-[14px] border-b border-[var(--border-subtle)]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Información personal y de contacto</h2>
                        <p class="text-xs text-[var(--text-muted)]">Cada cambio queda registrado en la bitácora · RF-06</p>
                    </div>
                    <div class="flex flex-col gap-4 p-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-form-field label="Nombre completo *" name="name" :value="$profile['name']" />
                            <x-form-field label="Correo electrónico *" name="email" type="email" :value="$profile['email']" />
                            <x-form-field label="Teléfono" name="telefono" :value="$contact?->telefono" placeholder="Ej. 300 123 4567" />
                            <x-form-field label="Ciudad" name="ciudad" :value="$contact?->ciudad" placeholder="Ej. Manizales" />
                        </div>
                        <x-form-field label="Dirección" name="direccion" :value="$contact?->direccion" />
                        <div class="grid grid-cols-1 gap-4 border-t border-[var(--border-subtle)] pt-4 sm:grid-cols-2">
                            <div>
                                <span class="block text-xs font-medium uppercase tracking-wider text-[var(--text-muted)]">Rol</span>
                                <p class="text-sm capitalize text-[var(--text-primary)]">{{ $profile['role'] }}</p>
                            </div>
                            <div>
                                <span class="block text-xs font-medium uppercase tracking-wider text-[var(--text-muted)]">Estado de cuenta</span>
                                <p class="text-sm {{ $profile['active'] === 'Activo' ? 'text-[var(--color-success)]' : 'text-[var(--color-danger)]' }}">{{ $profile['active'] }}</p>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <x-button variant="primary" type="submit">Guardar cambios</x-button>
                        </div>
                    </div>
                </form>
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
                            <x-ui-icon name="file-text" class="h-4 w-4" />
                            Mis solicitudes
                        </a>
                        <a href="/experiencias" class="flex items-center gap-[9px] rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 py-[10px] text-sm font-medium text-[var(--color-primary-600)] hover:bg-[var(--surface-sunken)]">
                            <x-ui-icon name="sparkles" class="h-4 w-4" />
                            Mis experiencias
                        </a>
                        <a href="/trabajo-de-grado" class="flex items-center gap-[9px] rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 py-[10px] text-sm font-medium text-[var(--color-primary-600)] hover:bg-[var(--surface-sunken)]">
                            <x-ui-icon name="book-open" class="h-4 w-4" />
                            Mi trabajo de grado
                        </a>
                        <a href="/historia-academica" class="flex items-center gap-[9px] rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 py-[10px] text-sm font-medium text-[var(--color-primary-600)] hover:bg-[var(--surface-sunken)]">
                            <x-ui-icon name="trending-up" class="h-4 w-4" />
                            Mi historia académica
                        </a>
                        <a href="/practica" class="flex items-center gap-[9px] rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 py-[10px] text-sm font-medium text-[var(--color-primary-600)] hover:bg-[var(--surface-sunken)]">
                            <x-ui-icon name="briefcase" class="h-4 w-4" />
                            Mi práctica
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>