import Alpine from 'alpinejs';
import './icons';

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

// Pestañas accesibles (RNF-12): roles tab/tablist/tabpanel con navegación por teclado.
Alpine.data('tabs', (keys, initial = null) => ({
    keys,
    active: initial ?? keys[0],

    select(key) {
        this.active = key;
    },

    onKey(event, key) {
        const i = this.keys.indexOf(key);
        let next = null;

        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') next = (i + 1) % this.keys.length;
        else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') next = (i - 1 + this.keys.length) % this.keys.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = this.keys.length - 1;

        if (next === null) return;

        event.preventDefault();
        this.active = this.keys[next];
        this.$nextTick(() => this.$refs['tab-' + this.keys[next]]?.focus());
    },
}));

window.Alpine = Alpine;
Alpine.start();
