<footer class="border-t border-[var(--color-primary-600)]/10 bg-white px-6 py-4">
    <div class="flex flex-col gap-2 text-sm text-[var(--text-secondary)] sm:flex-row sm:items-center sm:justify-between">
        <p>© {{ date('Y') }} Trayectoria Estudiantil · Área de Coordinación Académica</p>
        <nav class="flex gap-4">
            <a href="{{ url('/panel') }}" class="hover:text-[var(--color-primary-600)]">Panel</a>
            <a href="{{ url('/indicadores') }}" class="hover:text-[var(--color-primary-600)]">Indicadores</a>
            <a href="{{ url('/solicitudes/nueva') }}" class="hover:text-[var(--color-primary-600)]">Nueva solicitud</a>
        </nav>
    </div>
</footer>