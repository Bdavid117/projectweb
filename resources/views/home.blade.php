<x-layouts.guest title="Inicio · Trayectoria Estudiantil">
    <div class="min-h-full bg-[var(--surface-page)]">
        <header class="border-b border-[var(--border-subtle)] bg-[var(--surface-card)]">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-[var(--radius-lg)] bg-[var(--color-primary-800)]">
                        <x-lucide-graduation-cap class="h-6 w-6 text-white" />
                    </span>
                    <span class="text-sm font-semibold text-[var(--text-primary)] sm:text-base">Trayectoria Estudiantil</span>
                </a>

                <x-button href="{{ route('login.show') }}" variant="secondary" icon="log-in">
                    Iniciar sesión
                </x-button>
            </div>
        </header>

        <main class="mx-auto flex max-w-7xl flex-col gap-10 px-5 py-12 sm:px-8 sm:py-16">
            <section class="overflow-hidden rounded-[var(--radius-xl)] bg-[var(--color-primary-800)] px-6 py-10 text-white sm:px-10 sm:py-14 lg:px-14">
                <div class="max-w-3xl">
                    <p class="mb-4 text-sm font-semibold uppercase tracking-[0.16em] text-[var(--color-primary-200)]">
                        Universidad Nacional de Colombia · Sede Manizales
                    </p>
                    <h1 class="text-3xl font-semibold leading-tight sm:text-4xl lg:text-5xl">
                        Acompañamos cada paso de tu trayectoria estudiantil.
                    </h1>
                    <p class="mt-5 max-w-2xl text-base leading-relaxed text-[var(--color-primary-100)] sm:text-lg">
                        Consulta la información académica, haz seguimiento a las solicitudes y encuentra en un solo lugar las herramientas del programa.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <x-button href="{{ route('login.show') }}" variant="primary" icon="arrow-right" class="bg-white text-[var(--color-primary-800)] hover:bg-[var(--color-primary-50)]">
                            Ingresar al sistema
                        </x-button>
                        <a href="{{ route('panel.index') }}" class="inline-flex items-center justify-center rounded-[var(--radius-md)] border border-white/30 px-[18px] py-[10px] text-sm font-semibold text-white transition-colors hover:bg-white/10">
                            Explorar el panel
                        </a>
                    </div>
                </div>
            </section>

            <section aria-labelledby="quick-links-title" class="flex flex-col gap-5">
                <div>
                    <h2 id="quick-links-title" class="text-xl font-semibold text-[var(--text-primary)] sm:text-2xl">Accesos principales</h2>
                    <p class="mt-2 text-sm text-[var(--text-secondary)] sm:text-base">Selecciona una sección para continuar.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <a href="{{ route('panel.index') }}" class="group flex min-h-44 flex-col gap-5 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5 transition hover:-translate-y-0.5 hover:border-[var(--color-primary-300)] hover:shadow-[var(--shadow-md)]">
                        <span class="flex h-11 w-11 items-center justify-center rounded-[var(--radius-md)] bg-[var(--color-primary-50)] text-[var(--color-primary-600)]">
                            <x-lucide-layout-dashboard class="h-5 w-5" />
                        </span>
                        <span class="flex flex-1 items-end justify-between gap-3">
                            <span>
                                <span class="block font-semibold text-[var(--text-primary)]">Panel del programa</span>
                                <span class="mt-1 block text-sm text-[var(--text-secondary)]">Resumen y seguimiento</span>
                            </span>
                            <x-lucide-arrow-up-right class="mb-1 h-4 w-4 shrink-0 text-[var(--text-muted)] transition group-hover:text-[var(--color-primary-600)]" />
                        </span>
                    </a>

                    <a href="{{ route('students.index') }}" class="group flex min-h-44 flex-col gap-5 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5 transition hover:-translate-y-0.5 hover:border-[var(--color-primary-300)] hover:shadow-[var(--shadow-md)]">
                        <span class="flex h-11 w-11 items-center justify-center rounded-[var(--radius-md)] bg-[var(--color-accent-50)] text-[var(--color-accent-600)]">
                            <x-lucide-users class="h-5 w-5" />
                        </span>
                        <span class="flex flex-1 items-end justify-between gap-3">
                            <span>
                                <span class="block font-semibold text-[var(--text-primary)]">Estudiantes</span>
                                <span class="mt-1 block text-sm text-[var(--text-secondary)]">Directorio y perfiles</span>
                            </span>
                            <x-lucide-arrow-up-right class="mb-1 h-4 w-4 shrink-0 text-[var(--text-muted)] transition group-hover:text-[var(--color-primary-600)]" />
                        </span>
                    </a>

                    <a href="{{ route('requests.create') }}" class="group flex min-h-44 flex-col gap-5 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5 transition hover:-translate-y-0.5 hover:border-[var(--color-primary-300)] hover:shadow-[var(--shadow-md)]">
                        <span class="flex h-11 w-11 items-center justify-center rounded-[var(--radius-md)] bg-[var(--color-warning-bg)] text-[var(--color-warning)]">
                            <x-lucide-file-text class="h-5 w-5" />
                        </span>
                        <span class="flex flex-1 items-end justify-between gap-3">
                            <span>
                                <span class="block font-semibold text-[var(--text-primary)]">Solicitudes</span>
                                <span class="mt-1 block text-sm text-[var(--text-secondary)]">Radica una solicitud</span>
                            </span>
                            <x-lucide-arrow-up-right class="mb-1 h-4 w-4 shrink-0 text-[var(--text-muted)] transition group-hover:text-[var(--color-primary-600)]" />
                        </span>
                    </a>

                    <a href="{{ route('indicators.index') }}" class="group flex min-h-44 flex-col gap-5 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5 transition hover:-translate-y-0.5 hover:border-[var(--color-primary-300)] hover:shadow-[var(--shadow-md)]">
                        <span class="flex h-11 w-11 items-center justify-center rounded-[var(--radius-md)] bg-[var(--color-info-bg)] text-[var(--color-info)]">
                            <x-lucide-bar-chart-3 class="h-5 w-5" />
                        </span>
                        <span class="flex flex-1 items-end justify-between gap-3">
                            <span>
                                <span class="block font-semibold text-[var(--text-primary)]">Indicadores</span>
                                <span class="mt-1 block text-sm text-[var(--text-secondary)]">Consulta datos del programa</span>
                            </span>
                            <x-lucide-arrow-up-right class="mb-1 h-4 w-4 shrink-0 text-[var(--text-muted)] transition group-hover:text-[var(--color-primary-600)]" />
                        </span>
                    </a>
                </div>
            </section>

            <x-footer />
        </main>
    </div>
</x-layouts.guest>
