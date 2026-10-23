import Alpine from 'alpinejs';

Alpine.data('notifications', (url) => ({
    open: false,
    count: 0,
    html: '<p class="px-4 py-6 text-center text-sm text-[var(--text-muted)]">Cargando…</p>',

    async init() {
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const data = await response.json();
            this.count = data.count;
            this.html = data.html;
        } catch {
            this.html = '<p class="px-4 py-6 text-center text-sm text-[var(--text-muted)]">No se pudieron cargar las notificaciones.</p>';
        }
    },
}));

window.Alpine = Alpine;
Alpine.start();
