<footer class="rounded-[var(--radius-xl)] bg-[var(--color-primary-900)] px-6 py-7 text-white sm:px-8 sm:py-8">
    <div class="grid gap-8 sm:grid-cols-2 sm:items-start">
        <div class="flex max-w-md flex-col gap-4">
            <a href="{{ route('home') }}" class="flex w-fit items-center gap-3 rounded-[var(--radius-md)]">
                <span class="flex h-10 w-10 items-center justify-center rounded-[var(--radius-md)] bg-white/10">
                    <x-lucide-graduation-cap class="h-5 w-5 text-white" />
                </span>
                <span class="text-sm font-semibold sm:text-base">Trayectoria Estudiantil</span>
            </a>
            <p class="text-sm leading-relaxed text-[var(--color-primary-200)]">
                Programa de Administración de Sistemas Informáticos · Universidad Nacional de Colombia, Sede Manizales.
            </p>
        </div>

        <nav aria-label="Enlaces del pie de página" class="flex flex-col gap-3 sm:items-end">
            <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--color-primary-300)]">Accesos rápidos</h2>
            <div class="flex flex-wrap gap-x-5 gap-y-3 text-sm sm:justify-end">
                <a href="{{ route('panel.index') }}" class="rounded-sm text-[var(--color-primary-100)] transition-colors hover:text-white">Panel</a>
                <a href="{{ route('students.index') }}" class="rounded-sm text-[var(--color-primary-100)] transition-colors hover:text-white">Estudiantes</a>
                <a href="{{ route('requests.create') }}" class="rounded-sm text-[var(--color-primary-100)] transition-colors hover:text-white">Solicitudes</a>
                <a href="{{ route('indicators.index') }}" class="rounded-sm text-[var(--color-primary-100)] transition-colors hover:text-white">Indicadores</a>
            </div>
        </nav>
    </div>

    <div class="mt-7 flex flex-col gap-2 border-t border-white/15 pt-4 text-xs text-[var(--color-primary-300)] sm:flex-row sm:items-center sm:justify-between">
        <p>© {{ date('Y') }} Trayectoria Estudiantil. Todos los derechos reservados.</p>
        <a href="{{ route('login.show') }}" class="w-fit rounded-sm transition-colors hover:text-white">Acceso institucional</a>
    </div>
</footer>