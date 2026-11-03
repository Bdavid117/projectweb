<x-layouts.guest title="Iniciar sesión · Trayectoria Estudiantil">
    <div class="mx-auto flex min-h-full w-full max-w-[1440px] flex-col gap-6 px-6 py-7 sm:gap-10 sm:px-10 sm:py-10 lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(400px,480px)] lg:items-center lg:gap-24 lg:px-24 lg:py-16">
        <section class="flex flex-col gap-5 sm:gap-8 lg:min-h-[640px] lg:justify-between lg:gap-12">
            <a href="{{ route('login.show') }}" class="flex w-fit items-center gap-3" aria-label="Trayectoria Estudiantil">
                <x-logo class="h-[50px] w-auto" />
                <span class="font-display text-xl font-bold text-[var(--trz-tinta)]">Trayectoria Estudiantil</span>
            </a>

            <h1 class="max-w-[16ch] text-[28px] font-semibold leading-[1.1] text-[var(--trz-tinta)] sm:text-5xl lg:text-[56px]">Cada estudiante deja un camino. Aquí se conserva.</h1>

            {{-- Tres corrientes que se juntan en un solo tronco. El trazo se dibuja una vez; con movimiento reducido aparece ya dibujado. --}}
            <svg @if($errors->any()) data-static @endif class="hidden w-full max-w-[760px] lg:block" viewBox="0 0 760 300" fill="none" role="img" aria-label="Tres corrientes, historia académica, experiencias y acompañamiento, que se unen en una sola trayectoria">
                <g stroke-width="7" stroke-linecap="round" fill="none">
                    <path class="trace-path trace-1 [stroke:var(--trz-hilo)]" pathLength="1" d="M16 44 H150 C270 44 330 150 440 150" />
                    <path class="trace-path trace-2 [stroke:var(--trz-hito)]" pathLength="1" d="M16 152 C150 152 190 118 260 118 C340 118 370 150 440 150" />
                    <path class="trace-path trace-3 [stroke:var(--trz-brote)]" pathLength="1" d="M16 262 H150 C270 262 330 150 440 150" />
                    <path class="trace-path trace-4 [stroke:var(--trz-tinta)]" pathLength="1" d="M440 150 H650" />
                </g>
                <circle class="trace-node trace-1 fill-[var(--trz-hilo)]" cx="16" cy="44" r="11" />
                {{-- La rotación va en un <g>: la animación CSS del nodo usa `transform` y, si el rombo la llevara en su propio
                     atributo, la animación la reemplazaría y quedaría un cuadrado. --}}
                <g transform="rotate(45 16 152)"><rect class="trace-node trace-2 fill-[var(--trz-hito)] [stroke:var(--trz-hito-texto)]" stroke-width="2.5" x="6" y="142" width="20" height="20" rx="3" /></g>
                <rect class="trace-node trace-3 fill-[var(--trz-brote)]" x="5" y="251" width="22" height="22" rx="4" />
                <circle class="trace-node trace-4 fill-[var(--trz-hito)] [stroke:var(--trz-tinta)]" stroke-width="9" cx="690" cy="150" r="26" />
                <g class="fill-[var(--trz-texto-2)] font-sans text-[15px] font-semibold">
                    <text class="trace-label trace-1" x="16" y="16">Historia académica</text>
                    <text class="trace-label trace-2" x="16" y="108">Experiencias</text>
                    <text class="trace-label trace-3" x="16" y="226">Acompañamiento</text>
                    <text class="trace-label trace-4" x="690" y="208" text-anchor="middle">Una sola trayectoria</text>
                </g>
            </svg>

            {{-- Móvil y tablet: el mismo motivo y la misma animación, a otra escala. Escalar el SVG de escritorio (760 px) dejaría
                 las etiquetas en 6 px, así que este tiene su propia geometría con letra de 15 unidades. --}}
            <svg @if($errors->any()) data-static @endif class="h-auto w-full max-w-[340px] max-sm:[@media(max-height:760px)]:max-w-[260px] sm:max-w-[440px] lg:hidden" viewBox="0 0 340 150" fill="none" role="img" aria-label="Tres corrientes, historia académica, experiencias y acompañamiento, que se unen en una sola trayectoria">
                <g stroke-width="5" stroke-linecap="round" fill="none">
                    <path class="trace-path trace-1 [stroke:var(--trz-hilo)]" pathLength="1" d="M12 32 H110 C150 32 160 84 200 84" />
                    <path class="trace-path trace-2 [stroke:var(--trz-hito)]" pathLength="1" d="M12 84 C60 84 90 74 130 74 C165 74 175 84 200 84" />
                    <path class="trace-path trace-3 [stroke:var(--trz-brote)]" pathLength="1" d="M12 136 H110 C150 136 160 84 200 84" />
                    <path class="trace-path trace-4 [stroke:var(--trz-tinta)]" pathLength="1" d="M200 84 H292" />
                </g>
                <circle class="trace-node trace-1 fill-[var(--trz-hilo)]" cx="12" cy="32" r="8" />
                <g transform="rotate(45 12 84)"><rect class="trace-node trace-2 fill-[var(--trz-hito)] [stroke:var(--trz-hito-texto)]" stroke-width="2" x="5" y="77" width="14" height="14" rx="2.5" /></g>
                <rect class="trace-node trace-3 fill-[var(--trz-brote)]" x="4" y="128" width="16" height="16" rx="3" />
                <circle class="trace-node trace-4 fill-[var(--trz-hito)] [stroke:var(--trz-tinta)]" stroke-width="6" cx="306" cy="84" r="14" />
                <g class="fill-[var(--trz-texto-2)] font-sans text-[15px] font-semibold">
                    <text class="trace-label trace-1" x="4" y="16">Historia académica</text>
                    <text class="trace-label trace-2" x="4" y="62">Experiencias</text>
                    <text class="trace-label trace-3" x="4" y="122">Acompañamiento</text>
                    <text class="trace-label trace-4" x="336" y="126" text-anchor="end">Una sola trayectoria</text>
                </g>
            </svg>

            <p class="hidden max-w-[56ch] text-sm leading-relaxed text-[var(--trz-texto-2)] lg:block">
                Programa de Administración de Sistemas Informáticos. Universidad Nacional de Colombia, Sede Manizales.
            </p>
        </section>

        <section class="flex w-full max-w-[480px] flex-col gap-5 lg:max-w-none">
            <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5" novalidate>
                @csrf
                <div class="flex flex-col gap-2">
                    <h2 class="text-[28px] font-semibold leading-tight text-[var(--trz-tinta)] sm:text-[32px]">Iniciar sesión</h2>
                    <p class="text-base text-[var(--trz-texto-2)]">Use sus credenciales institucionales.</p>
                </div>

                <x-form-field label="Correo institucional" name="email" type="email" placeholder="usuario@unal.edu.co" icon="mail" autocomplete="username" :value="old('email')" />
                <x-form-field label="Contraseña" name="password" type="password" placeholder="••••••••••" autocomplete="current-password" />

                <x-button variant="primary" type="submit" class="mt-1 w-full min-h-12" data-busy-label="Ingresando…">Ingresar</x-button>

                <a href="#" class="inline-flex w-fit items-center text-[15px] font-semibold text-[var(--trz-hilo-texto)] hover:underline max-md:-my-2.5 max-md:min-h-11">¿Olvidó su contraseña?</a>
            </form>

            <p class="text-[13px] text-[var(--trz-texto-2)]">Las contraseñas se almacenan cifradas.</p>
        </section>
    </div>
</x-layouts.guest>
