<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mi trabajo de grado · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Mi trabajo de grado</h1>
                <p class="text-sm text-[var(--text-secondary)]">Información de tu tesis o proyecto de grado</p>
            </div>
        </div>

        @if($thesis)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="lg:col-span-1">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="px-4 py-[14px] border-b border-[var(--border-subtle)]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Detalles del trabajo de grado</h2>
                    </div>
                    <div class="p-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Título</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ $thesis->titulo ?? 'Sin título' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Director</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ $thesis->director?->name ?? 'No asignado' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Modalidad</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ ucfirst(\App\Support\Labels::thesisModality($thesis->modalidad)) }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Estado</label>
                                <x-status-badge :text="$status[0]" :tone="$status[1]" />
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
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Codirector</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ $thesis->codirector?->name ?? 'No asignado' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Periodo de inicio</label>
                                <p class="text-sm text-[var(--text-primary)]">{{ $thesis->term?->codigo ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">Repositorio</label>
                                @if($thesis->repositorio_url)
                                    <a href="{{ $thesis->repositorio_url }}" rel="noopener noreferrer" target="_blank" class="break-all text-sm font-semibold text-[var(--color-primary-600)]">{{ $thesis->repositorio_url }}</a>
                                @else
                                    <p class="text-sm text-[var(--text-muted)]">Sin repositorio registrado.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('thesis.update') }}" class="flex max-w-3xl flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
            @csrf
            @method('PUT')
            <div class="border-b border-[var(--border-subtle)] px-4 py-[14px]">
                <h2 class="text-sm font-semibold text-[var(--text-primary)]">Actualizar mi trabajo de grado</h2>
                <p class="text-xs text-[var(--text-muted)]">Puedes cambiar el título, el repositorio y el codirector · RF-19</p>
            </div>
            <div class="flex flex-col gap-4 p-4">
                <x-form-field label="Título *" name="titulo" :value="$thesis->titulo" />
                <x-form-field label="Repositorio" name="repositorio_url" type="url" :value="$thesis->repositorio_url" placeholder="https://…" />
                <x-form-field label="Codirector" name="codirector_id" type="select">
                    <option value="">Sin codirector</option>
                    @foreach($teachers as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('codirector_id', $thesis->codirector_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </x-form-field>
                <div class="flex justify-end">
                    <x-button variant="primary" type="submit">Guardar cambios</x-button>
                </div>
            </div>
        </form>
        @else
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <x-ui-icon name="book-open" class="h-16 w-16 text-[var(--text-muted)]" />
            <h2 class="text-lg font-semibold text-[var(--text-primary)] mt-4">No hay trabajo de grado registrado</h2>
            <p class="text-sm text-[var(--text-muted)] mt-2">Aún no se ha registrado información sobre tu trabajo de grado.</p>
        </div>
        @endif
    </div>
</x-layouts.app>