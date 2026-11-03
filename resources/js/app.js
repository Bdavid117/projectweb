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

// Campana de notificaciones. La página ya trae el número y la lista (renderizados en el servidor), así que cargar una
// pantalla no hace ninguna petición extra; al abrir la campana se pide una copia fresca por si pasó tiempo desde la carga.
Alpine.data('notifications', (url, count = 0) => ({
    open: false,
    count,

    async toggle() {
        this.open = !this.open;
        if (this.open) await this.refresh();
    },

    async refresh() {
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const data = await response.json();
            this.count = data.count;
            this.$refs.list.innerHTML = data.html;
        } catch {
            // Sin red: se conserva lo que la página ya trajo.
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

// Envío en curso: al enviar un formulario POST sus botones quedan deshabilitados con un giro (no hay doble envío y se ve que
// algo ocurre). Un botón puede traer `data-busy-label` para cambiar su texto mientras tanto («Ingresando…»).
document.addEventListener('submit', (event) => {
    const form = event.target;
    // Un envío cancelado (p. ej. por una confirmación) no debe dejar nada bloqueado.
    if (event.defaultPrevented || !(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post') return;

    const buttons = [...form.elements].filter((el) => el instanceof HTMLButtonElement && el.type === 'submit');
    form.setAttribute('aria-busy', 'true');
    // Después de que el navegador arme los datos: un botón ya deshabilitado no aportaría su nombre y valor.
    setTimeout(() => buttons.forEach((button) => {
        const label = button.dataset.busyLabel;
        if (label) {
            button.dataset.idleLabel = button.textContent;
            button.textContent = label;
        }
        button.disabled = true;
        button.dataset.busy = '';
        button.setAttribute('aria-busy', 'true');
    }), 0);
});

// Al volver con «Atrás» el navegador restaura la página tal como se dejó, con los botones aún bloqueados.
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    document.querySelectorAll('form[aria-busy]').forEach((form) => form.removeAttribute('aria-busy'));
    document.querySelectorAll('[data-busy]').forEach((button) => {
        if (button.dataset.idleLabel) button.textContent = button.dataset.idleLabel;
        button.disabled = false;
        button.removeAttribute('data-busy');
        button.removeAttribute('aria-busy');
    });
});

// Tras un envío fallido (el servidor devuelve la misma pantalla con errores), el foco va al primer campo con error: quien
// usa lector de pantalla oye el mensaje (aria-describedby) y quien está en el móvil no tiene que buscarlo.
const focusFirstError = () => document.querySelector('[aria-invalid="true"]')?.focus();
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', focusFirstError);
else focusFirstError();

window.Alpine = Alpine;
Alpine.start();
