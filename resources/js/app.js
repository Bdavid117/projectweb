import Alpine from 'alpinejs';
import './icons';

// Tema de color: automático (sistema), claro u oscuro. La preferencia queda en este navegador (localStorage).
Alpine.data('theme', () => ({
    mode: 'auto',

    init() {
        try {
            const saved = localStorage.getItem('tema');
            this.mode = saved === 'dark' || saved === 'light' ? saved : 'auto';
        } catch {
            this.mode = 'auto';
        }
    },

    set(mode) {
        this.mode = mode;
        const root = document.documentElement;
        try {
            if (mode === 'auto') {
                localStorage.removeItem('tema');
                delete root.dataset.theme;
            } else {
                localStorage.setItem('tema', mode);
                root.dataset.theme = mode;
            }
        } catch {
            // Sin almacenamiento (modo privado): el cambio vale solo para esta página.
            if (mode === 'auto') delete root.dataset.theme;
            else root.dataset.theme = mode;
        }
    },
}));

Alpine.data('notifications', (url) => ({
    open: false,
    count: 0,
    html: '<p class="px-4 py-6 text-center text-sm text-[var(--trz-texto-3)]">Cargando…</p>',

    async init() {
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const data = await response.json();
            this.count = data.count;
            this.html = data.html;
        } catch {
            this.html = '<p class="px-4 py-6 text-center text-sm text-[var(--trz-texto-3)]">No se pudieron cargar las notificaciones.</p>';
        }
    },
}));

// Pestañas accesibles (RNF-12): roles tab/tablist/tabpanel con navegación por teclado.
// `labels` (clave → etiqueta) alimenta el menú «Más» en móvil.
Alpine.data('tabs', (keys, labels = {}, initial = null) => ({
    keys,
    labels,
    active: initial ?? keys[0],

    activeLabel() {
        return this.labels[this.active] ?? '';
    },

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
