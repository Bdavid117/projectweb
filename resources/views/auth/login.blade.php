<x-layouts.guest title="Iniciar sesión · Trayectoria Estudiantil">
    <div class="flex h-full w-full flex-col overflow-y-auto lg:flex-row lg:overflow-hidden">
        <div class="relative isolate flex shrink-0 flex-col justify-between gap-8 overflow-hidden bg-[var(--color-primary-900)] bg-[radial-gradient(120%_80%_at_0%_0%,var(--color-primary-600)_0%,transparent_55%),radial-gradient(90%_60%_at_100%_100%,var(--color-primary-700)_0%,transparent_60%)] p-8 text-[var(--color-white)] lg:h-full lg:w-[600px] lg:gap-0 lg:p-14">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(rgba(255,255,255,0.09)_1px,transparent_1px)] bg-[size:26px_26px]"></div>

            <a href="{{ route('login.show') }}" class="flex items-center gap-3 self-start" aria-label="Trayectoria Estudiantil">
                <span class="flex h-12 w-12 items-center justify-center rounded-[var(--radius-lg)] bg-white shadow-lg"><x-logo class="h-9 w-9" /></span>
                <span class="text-base font-semibold tracking-[-0.2px]">Trayectoria Estudiantil</span>
            </a>

            <div class="flex flex-col gap-4">
                <h1 class="max-w-[30rem] text-3xl font-semibold leading-[1.2] tracking-[-0.5px] text-white lg:text-[34px]">Una visión integral de la trayectoria de cada estudiante.</h1>
                <p class="max-w-[28rem] text-sm leading-relaxed text-[var(--color-primary-200)]">
                    Historia académica, experiencias formativas y acompañamiento, reunidos en un solo lugar y conservados en el tiempo.
                </p>
            </div>

            <svg @if($errors->any()) data-static @endif class="hidden w-full max-w-[500px] overflow-visible lg:block" viewBox="0 0 500 230" fill="none" role="img" aria-label="Línea de tiempo: ingreso, historia académica, experiencias formativas y grado">
                <path class="trace-path" pathLength="1" d="M14 196 C 82 196, 104 128, 176 128 S 290 160, 330 98 S 420 30, 478 28" stroke="var(--color-primary-300)" stroke-width="3" stroke-linecap="round" />
                <g>
                    <circle class="trace-node trace-1" cx="14" cy="196" r="7" fill="var(--color-white)" />
                    <circle class="trace-node trace-2" cx="176" cy="128" r="7" fill="var(--color-white)" />
                    <circle class="trace-node trace-3" cx="330" cy="98" r="7" fill="var(--color-white)" />
                    <circle class="trace-pulse" cx="478" cy="28" r="11" fill="var(--color-accent-500)" />
                    <circle class="trace-node trace-4" cx="478" cy="28" r="11" fill="var(--color-accent-500)" />
                </g>
                <g class="text-[12px]" fill="var(--color-primary-100)" font-family="Inter, sans-serif" font-weight="500">
                    <text class="trace-label trace-1" x="14" y="222">Ingreso</text>
                    <text class="trace-label trace-2" x="176" y="106" text-anchor="middle">Historia académica</text>
                    <text class="trace-label trace-3" x="322" y="76" text-anchor="end">Experiencias</text>
                    <text class="trace-label trace-4" x="478" y="62" text-anchor="end">Grado</text>
                </g>
            </svg>

            <p class="hidden text-xs leading-relaxed text-[var(--color-primary-300)] lg:block">
                Programa de Administración de Sistemas Informáticos<br />
                Universidad Nacional de Colombia · Sede Manizales
            </p>
        </div>

        <div class="flex flex-1 flex-col items-center justify-center gap-5 bg-[var(--surface-page)] p-6 sm:h-full lg:overflow-y-auto lg:p-14">
            <form method="POST" action="{{ route('login.store') }}" x-data="{ sending: false }" @submit="sending = true" class="flex w-full max-w-[400px] flex-col gap-5 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6 shadow-sm sm:p-9">
                @csrf
                <div class="flex flex-col gap-[5px]">
                    <h2 class="text-xl font-semibold text-[var(--text-primary)]">Iniciar sesión</h2>
                    <p class="text-sm text-[var(--text-secondary)]">Use sus credenciales institucionales.</p>
                </div>

                <x-form-field label="Correo institucional" name="email" type="email" placeholder="usuario@unal.edu.co" icon="mail" :value="old('email')" />
                <x-form-field label="Contraseña" name="password" type="password" placeholder="••••••••••" />

                <x-button variant="primary" type="submit" class="w-full justify-center" x-bind:disabled="sending" x-bind:class="sending && 'opacity-70'">
                    <span x-show="!sending">Ingresar</span>
                    <span x-show="sending" x-cloak>Ingresando…</span>
                </x-button>

                <a href="#" class="text-sm font-medium text-[var(--color-primary-600)] hover:underline">¿Olvidó su contraseña?</a>
            </form>

            <p class="w-full max-w-[400px] text-xs text-[var(--text-muted)]">
                Acceso por HTTPS · las contraseñas se almacenan con hash bcrypt (RNF-01, RNF-02)
            </p>
        </div>
    </div>
</x-layouts.guest>
