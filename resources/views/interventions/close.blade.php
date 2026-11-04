<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Cerrar intervención · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Cerrar intervención" subtitle="Registre el resultado del acompañamiento. Una vez cerrada, queda como parte de la historia del estudiante." :back="route('students.show', $student['id'])" back-label="{{ $student['name'] }}" />

        <div class="grid grid-cols-1 items-start gap-x-20 gap-y-12 lg:grid-cols-[minmax(0,1fr)_420px]">
            <form method="POST" action="{{ route('interventions.do-close', $intervention['id']) }}" class="flex flex-col gap-6">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-4 border-b border-[var(--trz-linea)] pb-5">
                    <span class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-full bg-[var(--trz-hilo-fondo)] text-base font-bold text-[var(--trz-hilo-texto)]" aria-hidden="true">{{ $student['initials'] }}</span>
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <span class="font-display text-[22px] font-semibold leading-tight text-[var(--trz-tinta)]">{{ $student['name'] }}</span>
                        <span class="text-[15px] text-[var(--trz-texto-2)]">{{ $student['code'] }} · {{ $student['cohort'] }}</span>
                    </div>
                </div>

                <x-form-field label="Resultado *" name="resultado" type="textarea" :rows="6" :value="old('resultado')" placeholder="Describa cómo concluyó el acompañamiento…" help="Mínimo 10 caracteres" />

                <div class="flex flex-wrap gap-3 pt-1">
                    <x-button variant="primary" type="submit" class="min-h-12">Cerrar intervención</x-button>
                    <x-button variant="secondary" :href="route('students.show', $student['id'])" class="min-h-12">Cancelar</x-button>
                </div>
            </form>

            <aside class="flex flex-col gap-6">
                <x-section title="Intervención">
                    <dl class="flex flex-col border-t border-[var(--trz-linea)]">
                        <div class="flex items-center justify-between gap-4 border-b border-[var(--trz-linea)] py-3">
                            <dt class="text-sm text-[var(--trz-texto-2)]">Origen</dt>
                            <dd class="text-[15px] font-semibold text-[var(--trz-tinta)]">{{ $intervention['origen'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-b border-[var(--trz-linea)] py-3">
                            <dt class="text-sm text-[var(--trz-texto-2)]">Fecha</dt>
                            <dd class="text-[15px] font-semibold text-[var(--trz-tinta)]">{{ $intervention['date'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-b border-[var(--trz-linea)] py-3">
                            <dt class="text-sm text-[var(--trz-texto-2)]">Responsable</dt>
                            <dd class="text-[15px] font-semibold text-[var(--trz-tinta)]">{{ $intervention['responsable'] }}</dd>
                        </div>
                    </dl>
                    <p class="text-[15px] leading-relaxed text-[var(--trz-texto-2)]">{{ $intervention['description'] }}</p>
                </x-section>
            </aside>
        </div>
    </x-page>
</x-layouts.app>
