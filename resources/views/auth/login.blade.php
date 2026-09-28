<x-layouts.guest title="Iniciar sesión · Trayectoria Estudiantil">
    <div class="flex h-full w-full flex-col overflow-y-auto lg:flex-row lg:overflow-visible">
        <div
            class="flex shrink-0 flex-col justify-between gap-8 p-8 text-[var(--color-white)] lg:h-full lg:w-[560px] lg:justify-between lg:gap-0 lg:p-14"
            style="background-image: linear-gradient(168.743deg, #112F52 5.949%, #1E5290 94.051%)"
        >
            <div class="flex items-center gap-3">
                <div class="flex h-[38px] w-[38px] items-center justify-center rounded-[var(--radius-md)] bg-white/10">
                    <x-lucide-graduation-cap class="h-[22px] w-[22px] text-white" />
                </div>
                <span class="text-base font-semibold">Trayectoria Estudiantil</span>
            </div>

            <div class="flex flex-col gap-4">
                <h1 class="text-2xl font-semibold text-white">Una visión integral de la trayectoria de cada estudiante.</h1>
                <p class="text-sm text-[var(--color-primary-200)]">
                    Historia académica, experiencias formativas y acompañamiento, reunidos en un solo lugar y conservados en el tiempo.
                </p>
            </div>

            <p class="hidden text-xs text-[var(--color-primary-300)] lg:block">
                Programa de Administración de Sistemas Informáticos<br />
                Universidad Nacional de Colombia · Sede Manizales
            </p>
        </div>

        <div class="flex flex-1 flex-col items-center justify-center gap-5 bg-[var(--surface-page)] p-6 sm:h-full lg:p-14">
            <form method="POST" action="{{ route('login.store') }}" class="flex w-full max-w-[400px] flex-col gap-5 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6 sm:p-9">
                @csrf
                <div class="flex flex-col gap-[5px]">
                    <h2 class="text-xl font-semibold text-[var(--text-primary)]">Iniciar sesión</h2>
                    <p class="text-sm text-[var(--text-secondary)]">Use sus credenciales institucionales.</p>
                </div>

                <x-form-field label="Correo institucional" name="email" type="email" placeholder="usuario@unal.edu.co" icon="mail" />
                <x-form-field label="Contraseña" name="password" type="password" placeholder="••••••••••" icon="eye-off" />

                <x-button variant="primary" type="submit" class="w-full justify-center">Ingresar</x-button>

                <a href="#" class="text-sm font-medium text-[var(--color-primary-600)]">¿Olvidó su contraseña?</a>
            </form>

            <p class="w-full max-w-[400px] text-xs text-[var(--text-muted)]">
                Acceso por HTTPS · las contraseñas se almacenan con hash bcrypt (RNF-01, RNF-02)
            </p>
        </div>
    </div>
</x-layouts.guest>
