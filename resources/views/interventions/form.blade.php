<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Registrar intervención · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Registrar intervención" subtitle="Una intervención de acompañamiento, con su origen y su resultado." :back="route('students.show', $student['id'])" back-label="{{ $student['name'] }}" />

        <div class="grid grid-cols-1 items-start gap-x-20 gap-y-12 lg:grid-cols-[minmax(0,1fr)_420px]">
            <form method="POST" action="{{ route('interventions.store', $student['id']) }}" class="flex flex-col gap-6">
                @csrf
                <input type="hidden" name="origen_id" value="{{ old('origen_id', $prefill['origen_id']) }}" />

                <div class="flex items-center gap-4 border-b border-[var(--trz-linea)] pb-5">
                    <span class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-full bg-[var(--trz-hilo-fondo)] text-base font-bold text-[var(--trz-hilo-texto)]" aria-hidden="true">{{ $student['initials'] }}</span>
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <span class="font-display text-[22px] font-semibold leading-tight text-[var(--trz-tinta)]">{{ $student['name'] }}</span>
                        <span class="text-[15px] text-[var(--trz-texto-2)]">{{ $student['code'] }} · {{ $student['cohort'] }} · {{ $student['tutor'] }}</span>
                    </div>
                </div>

                @if($prefill['hint'])
                    <div class="flex items-start gap-3 rounded-[var(--radius-lg)] bg-[var(--trz-hilo-fondo)] p-4">
                        <x-ui-icon name="file-text" class="mt-0.5 h-5 w-5 shrink-0 text-[var(--trz-hilo-texto)]" />
                        <p class="text-[15px] leading-snug text-[var(--trz-tinta)]">Enlazada con su origen — <span class="font-semibold">{{ $prefill['hint'] }}</span></p>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form-field label="Origen *" name="origen" type="select" help="Solicitud, alerta, tutoría u observación de un profesor">
                        <option value="">Seleccione el origen</option>
                        @foreach($origenes as $value => $label)
                            <option value="{{ $value }}" @selected(old('origen', $prefill['origen']) === $value)>{{ $label }}</option>
                        @endforeach
                    </x-form-field>
                    <x-form-field label="Fecha *" name="fecha" type="date" icon="calendar" :value="old('fecha', now()->format('Y-m-d'))" help="No puede ser posterior a hoy" />
                </div>

                <x-form-field label="Descripción *" name="descripcion" type="textarea" :rows="6" :value="old('descripcion')" placeholder="Describa la intervención de acompañamiento…" help="Mínimo 10 caracteres" />

                <div class="flex flex-wrap gap-3 pt-1">
                    <x-button variant="primary" type="submit" class="min-h-12">Registrar intervención</x-button>
                    <x-button variant="secondary" :href="route('students.show', $student['id'])" class="min-h-12">Cancelar</x-button>
                </div>
            </form>

            <aside class="flex flex-col gap-8">
                <div class="flex items-start gap-3 rounded-[var(--radius-lg)] bg-[var(--trz-hilo-fondo)] p-4">
                    <x-ui-icon name="clipboard-list" class="mt-0.5 h-5 w-5 shrink-0 text-[var(--trz-hilo-texto)]" />
                    <p class="text-[15px] leading-snug text-[var(--trz-tinta)]">La intervención queda <span class="font-semibold">abierta</span>. Cuando concluya el acompañamiento, ciérrela registrando su resultado desde la ficha del estudiante.</p>
                </div>
            </aside>
        </div>
    </x-page>
</x-layouts.app>
