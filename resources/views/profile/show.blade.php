<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mi perfil · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Mi perfil" subtitle="Sus datos personales y de contacto, y su situación académica." />

        <div class="grid items-start gap-x-20 gap-y-12 lg:grid-cols-[minmax(0,1fr)_440px]">
            <form method="POST" action="{{ route('profile.update') }}" class="flex flex-col gap-6">
                @csrf
                @method('PUT')
                <div class="flex flex-col gap-1.5">
                    <h2 class="text-2xl font-semibold text-[var(--trz-tinta)] sm:text-[26px]">Información personal y de contacto</h2>
                    <p class="text-[15px] text-[var(--trz-texto-2)]">Cada cambio queda registrado en la bitácora. Los campos con * son obligatorios.</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form-field label="Nombre completo *" name="name" autocomplete="name" :value="$profile['name']" />
                    <x-form-field label="Correo electrónico *" name="email" type="email" autocomplete="email" :value="$profile['email']" />
                    <x-form-field label="Teléfono" name="telefono" autocomplete="tel" :value="$contact?->telefono" placeholder="Ej. 300 123 4567" />
                    <x-form-field label="Ciudad" name="ciudad" autocomplete="address-level2" :value="$contact?->ciudad" placeholder="Ej. Manizales" />
                </div>
                <x-form-field label="Dirección" name="direccion" autocomplete="street-address" :value="$contact?->direccion" />

                <dl class="grid gap-5 border-y border-[var(--trz-linea)] py-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <dt class="text-sm font-semibold text-[var(--trz-tinta)]">Rol</dt>
                        <dd class="text-base text-[var(--trz-texto-2)]">{{ $profile['role'] }}</dd>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <dt class="text-sm font-semibold text-[var(--trz-tinta)]">Estado de la cuenta</dt>
                        <dd><x-status-badge :text="$profile['active']" :tone="$profile['active'] === 'Activo' ? 'success' : 'neutral'" /></dd>
                    </div>
                </dl>

                <div><x-button variant="primary" type="submit" class="min-h-12 w-full sm:w-[220px]">Guardar cambios</x-button></div>
            </form>

            <aside class="flex flex-col gap-10">
                @if($student)
                    <x-section title="Información académica">
                        <dl class="flex flex-col border-t border-[var(--trz-linea)]">
                            @foreach([['Código', str_replace('Código ', '', $student['code'])], ['Cohorte', str_replace('Cohorte ', '', $student['cohort'])], ['Matrícula', $student['enrollment']], ['Tutor', str_replace('Tutor asignado: ', '', $student['tutor'])], ['PAPA actual', $student['papa']]] as [$label, $value])
                                <div class="flex items-center justify-between gap-4 border-b border-[var(--trz-linea)] py-[11px]">
                                    <dt class="text-sm text-[var(--trz-texto-2)]">{{ $label }}</dt>
                                    <dd class="text-right text-[15px] font-semibold text-[var(--trz-tinta)]">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-section>
                @endif

                <x-section title="Acciones rápidas">
                    <ul class="flex flex-col border-t border-[var(--trz-linea)]">
                        @foreach([['file-text', 'Mis solicitudes', '/solicitudes'], ['sparkles', 'Mis experiencias', '/experiencias'], ['graduation-cap', 'Mi trabajo de grado', '/trabajo-de-grado'], ['book-open', 'Mi historia académica', '/historia-academica'], ['briefcase', 'Mi práctica', '/practica']] as [$icon, $label, $href])
                            <li class="border-b border-[var(--trz-linea)]">
                                <a href="{{ $href }}" class="flex items-center gap-3.5 py-3.5 hover:bg-[var(--trz-hilo-fondo)]">
                                    <x-ui-icon :name="$icon" class="h-[22px] w-[22px] shrink-0 text-[var(--trz-hilo-texto)]" />
                                    <span class="flex-1 text-base font-semibold text-[var(--trz-tinta)]">{{ $label }}</span>
                                    <x-ui-icon name="arrow-right" class="h-[17px] w-[17px] shrink-0 text-[var(--trz-hilo-texto)]" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-section>
            </aside>
        </div>
    </x-page>
</x-layouts.app>
