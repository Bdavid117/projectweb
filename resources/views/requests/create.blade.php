<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Nueva solicitud · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Nueva solicitud al comité asesor" subtitle="Los campos marcados con * son obligatorios." :back="route('requests.index')" back-label="{{ $isStaff ? 'Solicitudes' : 'Mis solicitudes' }}" />

        @if($errors->any())
            <div role="alert" class="flex items-start gap-3.5 rounded-[var(--radius-lg)] border-[1.5px] border-[var(--trz-alerta)] bg-[var(--trz-alerta-fondo)] px-[18px] py-4">
                <x-ui-icon name="circle-alert" class="mt-0.5 h-[22px] w-[22px] shrink-0 text-[var(--trz-alerta)]" />
                <div class="flex flex-col gap-1">
                    <p class="text-base font-bold text-[var(--trz-alerta)]">Revise los campos marcados</p>
                    <ul class="flex flex-col gap-0.5 text-[15px] text-[var(--trz-tinta)]">
                        @foreach($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                    <p class="text-sm text-[var(--trz-texto-2)]">Los datos escritos se conservan.</p>
                </div>
            </div>
        @endif

        <div class="grid items-start gap-x-20 gap-y-12 lg:grid-cols-[minmax(0,1fr)_420px]">
            <form method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data" class="flex flex-col gap-6">
                @csrf
                @if($isStaff)
                    <x-form-field label="Código del estudiante *" name="codigo_estudiante" icon="user" placeholder="p. ej. 2020123" help="La solicitud se radica a nombre de este estudiante." />
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <x-form-field label="Tipo de solicitud *" name="tipo" type="select" help="Cancelación de asignatura, reingreso, homologación, cupo de créditos…">
                            <option value="">Seleccione…</option>
                            @foreach($requestTypes as $id => $nombre)
                                <option value="{{ $id }}" @selected((string) old('tipo') === (string) $id)>{{ $nombre }}</option>
                            @endforeach
                        </x-form-field>
                        @if(count($existing))
                            <p class="flex items-start gap-2 text-[13px] font-semibold text-[var(--trz-hito-texto)]"><x-ui-icon name="triangle-alert" class="mt-0.5 h-3.5 w-3.5 shrink-0" />Ya radicó este periodo: {{ implode(', ', $existing) }}</p>
                        @endif
                    </div>
                    <x-form-field label="Periodo académico *" name="periodo" :value="$termLabel" :readonly="true" help="Se toma del periodo activo." />
                </div>

                <div x-data="{ chars: {{ mb_strlen((string) old('descripcion', '')) }} }" class="flex flex-col gap-1.5">
                    <div @input="chars = $event.target.value.length">
                        <x-form-field label="Descripción y justificación *" name="descripcion" type="textarea" :rows="6" placeholder="Describa el motivo de la solicitud y los hechos que la sustentan…" help="Mínimo 50 caracteres." />
                    </div>
                    <p class="text-right text-[13px] text-[var(--trz-texto-2)]"><span x-text="chars">0</span> / 1000</p>
                </div>

                @php($fileErrors = collect($errors->get('soportes.*'))->flatten()->merge($errors->get('soportes'))->unique())
                <div class="flex flex-col gap-2" x-data="{ files: [] }">
                    <label for="soportes" class="text-sm font-semibold text-[var(--trz-tinta)]">Soportes</label>
                    <label for="soportes" class="relative flex cursor-pointer flex-col items-center gap-1.5 rounded-[var(--radius-lg)] border-[1.5px] border-[var(--trz-borde-control)] bg-[var(--trz-superficie)] px-5 py-8 text-center hover:border-[var(--trz-tinta)] focus-within:border-[var(--trz-hilo)]">
                        <x-ui-icon name="cloud-upload" class="h-7 w-7 text-[var(--trz-hilo-texto)]" />
                        <span class="text-base font-semibold text-[var(--trz-tinta)]">Seleccione los archivos de soporte</span>
                        <input id="soportes" name="soportes[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                            @change="files = [...$event.target.files].map(f => ({ name: f.name, size: f.size < 1048576 ? Math.max(1, Math.round(f.size / 1024)) + ' KB' : (f.size / 1048576).toFixed(1).replace('.', ',') + ' MB' }))" />
                        <span class="text-sm text-[var(--trz-texto-2)]">PDF, JPG o PNG · máximo 5 MB por archivo · hasta 5 archivos</span>
                    </label>
                    <ul x-show="files.length" x-cloak class="flex flex-col border-t border-[var(--trz-linea)]" aria-live="polite">
                        <template x-for="file in files" :key="file.name">
                            <li class="flex items-center gap-3 border-b border-[var(--trz-linea)] py-2.5">
                                <x-ui-icon name="file-text" class="h-5 w-5 shrink-0 text-[var(--trz-texto-2)]" />
                                <span class="min-w-0 flex-1 truncate text-[15px] font-semibold text-[var(--trz-tinta)]" x-text="file.name"></span>
                                <span class="text-[13px] text-[var(--trz-texto-2)]" x-text="file.size"></span>
                            </li>
                        </template>
                    </ul>
                    @foreach($fileErrors as $message)
                        <p class="flex items-center gap-1.5 text-[13px] font-semibold text-[var(--trz-alerta)]"><x-ui-icon name="circle-alert" class="h-3.5 w-3.5" />{{ $message }}</p>
                    @endforeach
                </div>

                <div class="flex flex-wrap gap-3 pt-1" x-data="{ sending: false }" @submit.window="sending = true">
                    <x-button variant="primary" type="submit" class="min-h-12" x-bind:disabled="sending"><span x-show="!sending">Radicar solicitud</span><span x-show="sending" x-cloak class="inline-flex items-center gap-2"><x-ui-icon name="loader-circle" class="h-[17px] w-[17px] animate-spin" />Radicando…</span></x-button>
                    <x-button variant="secondary" :href="route('requests.index')" class="min-h-12">Cancelar</x-button>
                </div>
            </form>

            <aside class="flex flex-col gap-6" aria-labelledby="despues">
                <h2 id="despues" class="text-2xl font-semibold text-[var(--trz-tinta)] sm:text-[26px]">¿Qué pasa después?</h2>
                <x-thread :steps="collect($steps)->values()->map(fn ($s, $i) => ['title' => $s['number'].'. '.$s['title'], 'detail' => $s['detail'], 'done' => $i === 0])->all()" />
                <p class="text-sm leading-relaxed text-[var(--trz-texto-2)]">Cada cambio de estado queda registrado con autor y fecha, de modo que la solicitud conserva su traza completa.</p>
            </aside>
        </div>
    </x-page>
</x-layouts.app>
