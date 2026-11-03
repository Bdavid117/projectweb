<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mi trabajo de grado · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Mi trabajo de grado" subtitle="Información de su tesis o proyecto de grado." />

        @if($thesis)
            @php
                $sentence = 'Su trabajo de grado es de modalidad '.\App\Support\Labels::thesisModality($thesis->modalidad)
                    .($thesis->director ? ', con '.$thesis->director->name.' como director' : '')
                    .($thesis->term ? ' y está '.mb_strtolower($status[0]).' desde '.$thesis->term->codigo : '').'.';
            @endphp
            <x-frase>{{ $sentence }}</x-frase>

            <div class="grid grid-cols-1 items-start gap-x-20 gap-y-12 lg:grid-cols-[minmax(0,1fr)_460px]">
                <div class="flex flex-col gap-10">
                    <x-section title="Detalles del trabajo de grado">
                        <dl class="flex flex-col border-t border-[var(--trz-linea)]">
                            @foreach([['Título', $thesis->titulo ?? 'Sin título'], ['Director', $thesis->director?->name ?? 'No asignado'], ['Modalidad', ucfirst(\App\Support\Labels::thesisModality($thesis->modalidad))]] as [$label, $value])
                                <div class="flex flex-col gap-1 border-b border-[var(--trz-linea)] py-3.5 sm:flex-row sm:items-start sm:gap-6">
                                    <dt class="text-sm text-[var(--trz-texto-2)] sm:w-[170px] sm:shrink-0 sm:pt-0.5">{{ $label }}</dt>
                                    <dd class="text-base font-semibold text-[var(--trz-tinta)]">{{ $value }}</dd>
                                </div>
                            @endforeach
                            <div class="flex flex-col gap-1 border-b border-[var(--trz-linea)] py-3 sm:flex-row sm:items-center sm:gap-6">
                                <dt class="text-sm text-[var(--trz-texto-2)] sm:w-[170px] sm:shrink-0">Estado</dt>
                                <dd><x-status-badge :text="$status[0]" :tone="$status[1]" /></dd>
                            </div>
                        </dl>
                    </x-section>

                    <x-section title="Información adicional">
                        <dl class="flex flex-col border-t border-[var(--trz-linea)]">
                            <div class="flex flex-col gap-1 border-b border-[var(--trz-linea)] py-3.5 sm:flex-row sm:items-start sm:gap-6">
                                <dt class="text-sm text-[var(--trz-texto-2)] sm:w-[170px] sm:shrink-0 sm:pt-0.5">Codirector</dt>
                                <dd class="text-base {{ $thesis->codirector ? 'font-semibold text-[var(--trz-tinta)]' : 'text-[var(--trz-texto-2)]' }}">{{ $thesis->codirector?->name ?? 'No asignado' }}</dd>
                            </div>
                            <div class="flex flex-col gap-1 border-b border-[var(--trz-linea)] py-3.5 sm:flex-row sm:items-start sm:gap-6">
                                <dt class="text-sm text-[var(--trz-texto-2)] sm:w-[170px] sm:shrink-0 sm:pt-0.5">Periodo de inicio</dt>
                                <dd class="text-base font-semibold text-[var(--trz-tinta)]">{{ $thesis->term?->codigo ?? '-' }}</dd>
                            </div>
                            <div class="flex flex-col gap-1 border-b border-[var(--trz-linea)] py-3.5 sm:flex-row sm:items-start sm:gap-6">
                                <dt class="text-sm text-[var(--trz-texto-2)] sm:w-[170px] sm:shrink-0 sm:pt-0.5">Repositorio</dt>
                                <dd class="min-w-0 text-base">
                                    @if($thesis->repositorio_url)
                                        <a href="{{ $thesis->repositorio_url }}" rel="noopener noreferrer" target="_blank" class="break-all font-semibold text-[var(--trz-hilo-texto)] hover:underline">{{ $thesis->repositorio_url }}</a>
                                    @else
                                        <span class="text-[var(--trz-texto-2)]">Sin repositorio registrado.</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </x-section>
                </div>

                <form method="POST" action="{{ route('thesis.update') }}" class="flex flex-col gap-5 rounded-[var(--radius-xl)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)] p-7">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-col gap-1.5">
                        <h2 class="text-[22px] sm:text-2xl font-semibold text-[var(--trz-tinta)]">Actualizar mi trabajo de grado</h2>
                        <p class="text-sm leading-snug text-[var(--trz-texto-2)]">Puede cambiar el título, el repositorio y el codirector.</p>
                    </div>
                    <x-form-field label="Título *" name="titulo" :value="$thesis->titulo" />
                    <x-form-field label="Repositorio" name="repositorio_url" type="url" :value="$thesis->repositorio_url" placeholder="https://…" />
                    <x-form-field label="Codirector" name="codirector_id" type="select">
                        <option value="">Sin codirector</option>
                        @foreach($teachers as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('codirector_id', $thesis->codirector_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </x-form-field>
                    <x-button variant="primary" type="submit" class="min-h-12 w-full">Guardar cambios</x-button>
                </form>
            </div>
        @else
            <x-empty-state kind="tesis" title="No hay trabajo de grado registrado" text="Aún no se ha registrado información sobre su trabajo de grado." />
        @endif
    </x-page>
</x-layouts.app>
