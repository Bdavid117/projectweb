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

// Mapa de cohortes del panel. Filtros por tipo combinables (Y), búsqueda sin tildes, zoom (botones, Ctrl/⌘ + rueda, pellizco, doble clic y teclado),
// desplazamiento arrastrando, tarjeta con los datos de cada estudiante, resumen por cohorte, comparación con el periodo anterior, selección con
// recuadro (Mayús + arrastrar, o el botón «Recuadro» con el dedo), agrupación en burbujas cuando hay muchos puntos, vista de tabla ordenable,
// vistas guardadas y exportación a CSV.
// El zoom y el desplazamiento son tres variables CSS del marco (--k, --tx, --ty): cada punto, marca y burbuja se coloca con ellas, así que mover
// el mapa no obliga a tocar uno por uno. Toda la lógica (agrupar, seleccionar, ubicar, ordenar) sale de los datos de cada estudiante —porcentaje,
// fila y desplazamiento—, nunca de medir el DOM. Con una pantalla táctil el primer toque abre la tarjeta y el segundo entra a la ficha.
Alpine.data('cohortMap', (config) => {
    let active = null; // el punto con tarjeta abierta (un nodo del DOM: fuera del estado reactivo para no envolverlo)
    let hideTimer = null;
    let hintTimer = null;
    let findTimer = null;
    let frame = null;
    let suppressClick = false;
    const pointers = new Map();
    let drag = null;
    let pinch = null;
    let boxDrag = null;
    let items = []; // un registro por estudiante, leído una vez del DOM (los puntos traen sus datos en data-*)
    let found = null; // el estudiante ubicado con la búsqueda: nunca se esconde dentro de una burbuja
    let grouped = new Set(); // puntos escondidos por una burbuja
    let signature = '';
    const own = ['activo', 'en-riesgo', 'intercambio', 'egresado'];
    const CELL = 36; // lado, en píxeles de pantalla, de la rejilla que agrupa los puntos (algo más que una burbuja de 32 px: casi no se solapan)
    const DENSE = 60; // desde aquí los puntos dejan de recorrerse con Tab y se remite a la tabla
    const TABLE_MAX = 100;

    // «Andrés» y «andres» son lo mismo para quien busca.
    const fold = (value) => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

    // Una celda que empieza con = + - @ (o tabulación / retorno) la interpretaría Excel como fórmula: se antepone una comilla.
    const neutralize = (value) => {
        const text = String(value ?? '');
        return /^[=+\-@\t\r]/.test(text) ? `'${text}` : text;
    };
    const cell = (value) => `"${neutralize(value).replace(/"/g, '""')}"`;
    const decimal = (value) => value.toFixed(1).replace('.', ',');
    const dateKey = (text) => { const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(text || ''); return m ? Number(m[3] + m[2] + m[1]) : 0; };

    return {
        tipos: [...config.tipos],
        shown: config.shown,
        total: config.total,
        k: 1,
        tx: 0,
        ty: 0,
        max: 8,
        tip: null,
        card: { x: 0, y: 0 },
        hint: false,
        mac: /Mac/.test(navigator.platform || ''),
        coarse: false,
        reduced: false,
        W: 0,
        H: 0,
        view: 'mapa',
        trails: false,
        selecting: false,
        box: null,
        selected: [],
        clusters: [],
        dense: false,
        row: null,
        summary: null,
        note: '',
        query: '',
        results: [],
        open: false,
        cursor: 0,
        saving: false,
        saveError: '',
        viewName: '',
        saved: [],
        sortKey: 'pct',
        sortDir: 1,
        tableRows: [],
        tableTotal: 0,

        init() {
            const vp = this.$refs.viewport;
            this.coarse = window.matchMedia('(hover: none)').matches;
            this.reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.readItems();
            this.loadViews();

            // La rueda solo acerca con Ctrl/⌘ (un pellizco en el trackpad también la trae): sin ellas la página sigue desplazándose.
            vp.addEventListener('wheel', (e) => {
                if (!(e.ctrlKey || e.metaKey)) return this.showHint();
                e.preventDefault();
                const r = this.$refs.plot.getBoundingClientRect();
                this.zoomAt(Math.exp(-e.deltaY * (e.deltaMode === 1 ? 0.05 : 0.0025)), e.clientX - r.left, e.clientY - r.top);
            }, { passive: false });

            vp.addEventListener('pointerdown', (e) => {
                if (e.pointerType === 'mouse' && e.button !== 0) return;
                pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
                if (pointers.size >= 2) { boxDrag = null; this.box = null; }
                // Mayús + arrastrar (o el botón «Recuadro», que es lo único que hay con el dedo) dibuja un recuadro en vez de mover el mapa.
                const r = this.$refs.plot.getBoundingClientRect();
                const inside = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
                if (pointers.size === 1 && inside && (this.selecting || e.shiftKey)) {
                    boxDrag = { x: e.clientX - r.left, y: e.clientY - r.top, moved: false };
                    drag = null;
                    return;
                }
                drag = { x: e.clientX, y: e.clientY, tx: this.tx, ty: this.ty, moved: false };
                if (pointers.size < 2) pinch = null;
            });
            vp.addEventListener('pointermove', (e) => {
                if (!pointers.has(e.pointerId)) return;
                pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
                if (pointers.size === 2) return this.pinchMove();
                if (boxDrag) return this.dragBox(e, vp);
                if (!drag || this.k <= 1) return;
                const dx = e.clientX - drag.x;
                const dy = e.clientY - drag.y;
                if (!drag.moved && Math.hypot(dx, dy) < 4) return;
                if (!drag.moved) { drag.moved = true; this.hide(); vp.setPointerCapture(e.pointerId); }
                this.setView(this.k, drag.tx + dx, drag.ty + dy);
            });
            const up = (e) => {
                pointers.delete(e.pointerId);
                if (boxDrag) {
                    if (boxDrag.moved) { this.finishBox(); suppressClick = true; setTimeout(() => { suppressClick = false; }, 0); }
                    boxDrag = null;
                    this.box = null;
                }
                if (drag && drag.moved) { suppressClick = true; setTimeout(() => { suppressClick = false; }, 0); }
                if (pointers.size < 2) pinch = null;
                if (pointers.size === 0) drag = null;
            };
            vp.addEventListener('pointerup', up);
            vp.addEventListener('pointercancel', (e) => { boxDrag = null; this.box = null; up(e); });

            // Un arrastre no debe abrir la ficha del punto sobre el que terminó; en pantallas táctiles el primer toque solo abre la tarjeta.
            vp.addEventListener('click', (e) => {
                if (suppressClick) { e.preventDefault(); e.stopPropagation(); return; }
                const dot = e.target.closest('[data-dot]');
                if (dot && this.coarse && active !== dot) { e.preventDefault(); this.show(dot); }
            }, true);
            vp.addEventListener('dblclick', (e) => {
                if (e.target.closest('[data-dot], .cohort-cluster, .cohort-rowlabel')) return;
                const r = this.$refs.plot.getBoundingClientRect();
                if (this.k >= this.max - 0.001) return this.reset();
                this.zoomAt(2, e.clientX - r.left, e.clientY - r.top, true);
            });
            vp.addEventListener('keydown', (e) => this.onKey(e));

            // En una pantalla táctil el navegador dispara mouseover y foco antes del clic: se ignoran para que el primer toque solo abra la tarjeta.
            vp.addEventListener('mouseover', (e) => { const dot = e.target.closest('[data-dot]'); if (dot && !this.coarse) this.show(dot); });
            vp.addEventListener('mouseout', (e) => { if (e.target.closest('[data-dot]') && !this.coarse) this.hideSoon(); });
            vp.addEventListener('focusin', (e) => { const dot = e.target.closest('[data-dot]'); if (dot && (!this.coarse || dot.matches(':focus-visible'))) this.show(dot); });
            vp.addEventListener('focusout', (e) => { if (e.target.closest('[data-dot]')) this.hideSoon(); });
            document.addEventListener('click', (e) => { if (this.tip && !this.$root.contains(e.target)) this.hide(); });

            // Oculto (vista de tabla) el plano mide 0: no se toca la vista, o se perdería el desplazamiento.
            new ResizeObserver(() => {
                if (!this.$refs.plot.clientWidth) return;
                this.measure();
                this.setView(this.k, this.tx, this.ty);
            }).observe(this.$refs.plot);
            this.$watch('view', () => this.$nextTick(() => { if (this.view !== 'mapa') return; this.measure(); this.setView(this.k, this.tx, this.ty); }));
            this.measure();
            this.apply();
            this.paint();
            this.restore();
        },

        // Un registro por punto, con lo que hace falta para filtrar, agrupar, ordenar y exportar sin volver a medir el DOM.
        readItems() {
            items = [...this.$root.querySelectorAll('[data-dot]')].map((el) => {
                const d = el.dataset;
                const before = el.previousElementSibling;
                const papaNum = Number(String(d.papa).replace(',', '.'));
                const item = {
                    el,
                    trail: before && before.classList.contains('cohort-trail') ? before : null,
                    id: d.id, name: d.name, code: d.code, cohort: d.cohort, url: el.getAttribute('href') || '',
                    pct: Number(d.pct), expected: Number(d.expected || 0), behind: el.hasAttribute('data-behind'),
                    prev: d.prev === undefined ? null : Number(d.prev),
                    papa: d.papa, papaNum: Number.isNaN(papaNum) ? -1 : papaNum, papaLow: d.papaLow === '1',
                    state: d.state, alerts: Number(d.alerts || 0), note: d.note, last: d.last || '', lastKey: dateKey(d.last),
                    tags: (d.tags || '').split(' ').filter(Boolean), row: Number(d.row), shift: Number(d.shift), on: true,
                };
                item.haystack = fold(`${item.name} ${item.code} ${item.cohort}`);
                return item;
            });
        },

        // Al volver de una ficha con «Atrás» o con el enlace del panel, el mapa vuelve a donde se dejó (solo durante esta visita).
        save() {
            if (!this.W || !this.H) return;
            try {
                window.sessionStorage.setItem('trz-mapa-vista', JSON.stringify({ k: this.k, fx: this.tx / this.W, fy: this.ty / this.H }));
            } catch { /* sin almacenamiento (modo privado): el zoom simplemente no se recuerda */ }
        },

        restore() {
            try {
                const saved = JSON.parse(window.sessionStorage.getItem('trz-mapa-vista') || 'null');
                if (saved && saved.k > 1) this.setView(this.clamp(Number(saved.k), 1, this.max), saved.fx * this.W, saved.fy * this.H);
            } catch { /* valor dañado: se ignora */ }
        },

        measure() {
            this.W = this.$refs.plot.clientWidth;
            this.H = this.$refs.plot.clientHeight;
        },

        clamp(value, low, high) {
            return Math.min(high, Math.max(low, value));
        },

        // Posición de un estudiante en el plano SIN zoom (píxeles): de ahí salen el recuadro, las burbujas y el ajuste.
        spot(item) {
            return { x: (item.pct / 100) * this.W, y: (item.row + 0.5) * (this.H / config.rows) + item.shift };
        },

        // Escribe el zoom y el desplazamiento en el marco: de ahí leen su posición todos los puntos, filas y marcas del eje.
        paint() {
            const style = this.$root.style;
            style.setProperty('--k', this.k);
            style.setProperty('--tx', this.tx);
            style.setProperty('--ty', this.ty);
            this.$refs.viewport.toggleAttribute('data-zoomed', this.k > 1.001);
            this.save();
        },

        // Al terminar de moverse: la tarjeta sigue a su punto y las burbujas se recalculan para el zoom nuevo.
        settle() {
            this.place();
            this.regroup();
        },

        // El mapa nunca queda fuera de su marco: la vista se mantiene dentro del plano (0 a −(zoom − 1) × tamaño).
        setView(k, tx, ty, animate = false) {
            const target = { k, tx: this.clamp(tx, this.W * (1 - k), 0), ty: this.clamp(ty, this.H * (1 - k), 0) };
            cancelAnimationFrame(frame);
            if (!animate || this.reduced) {
                Object.assign(this, target);
                this.paint();
                this.settle();
                return;
            }
            const from = { k: this.k, tx: this.tx, ty: this.ty };
            const start = performance.now();
            const step = (now) => {
                const p = Math.min(1, (now - start) / 180);
                const e = 1 - Math.pow(1 - p, 3);
                this.k = from.k + (target.k - from.k) * e;
                this.tx = from.tx + (target.tx - from.tx) * e;
                this.ty = from.ty + (target.ty - from.ty) * e;
                this.paint();
                if (p < 1) frame = requestAnimationFrame(step); else this.settle();
            };
            frame = requestAnimationFrame(step);
        },

        zoomAt(factor, cx = this.W / 2, cy = this.H / 2, animate = false) {
            const k = this.clamp(this.k * factor, 1, this.max);
            const ratio = k / this.k;
            this.hide();
            this.setView(k, cx - (cx - this.tx) * ratio, cy - (cy - this.ty) * ratio, animate);
        },

        pinchMove() {
            const [a, b] = [...pointers.values()];
            const distance = Math.hypot(a.x - b.x, a.y - b.y);
            const r = this.$refs.plot.getBoundingClientRect();
            if (!pinch) pinch = { distance, k: this.k };
            this.zoomAt((pinch.k * distance / pinch.distance) / this.k, (a.x + b.x) / 2 - r.left, (a.y + b.y) / 2 - r.top);
        },

        reset() {
            this.hide();
            this.setView(1, 0, 0, true);
        },

        // Acerca la vista a los estudiantes dados (por omisión, los que el filtro deja visibles).
        fit(list = items.filter((item) => item.on)) {
            if (!list.length) return;
            const rowH = this.H / config.rows;
            const spots = list.map((item) => this.spot(item));
            const xs = spots.map((s) => s.x);
            const ys = spots.map((s) => s.y);
            const [x0, x1, y0, y1] = [Math.min(...xs), Math.max(...xs), Math.min(...ys), Math.max(...ys)];
            const pad = 28;
            const w = Math.max(x1 - x0, this.W * 0.12) + pad * 2;
            const h = Math.max(y1 - y0, rowH) + pad * 2;
            const k = this.clamp(Math.min(this.W / w, this.H / h), 1, this.max);
            this.hide();
            this.setView(k, this.W / 2 - ((x0 + x1) / 2) * k, this.H / 2 - ((y0 + y1) / 2) * k, true);
        },

        onKey(e) {
            const step = 48;
            const pan = (dx, dy) => this.k > 1.001 && this.setView(this.k, this.tx + dx, this.ty + dy);
            switch (e.key) {
                case '+': case '=': this.zoomAt(1.6, undefined, undefined, true); break;
                case '-': case '_': this.zoomAt(1 / 1.6, undefined, undefined, true); break;
                case '0': this.reset(); break;
                case 'ArrowLeft': pan(step, 0); break;
                case 'ArrowRight': pan(-step, 0); break;
                case 'ArrowUp': pan(0, step); break;
                case 'ArrowDown': pan(0, -step); break;
                case 'Escape': boxDrag = null; this.box = null; this.hide(); return;
                default: return;
            }
            // Las flechas solo se capturan con zoom: sin él siguen desplazando la página.
            if (e.key.startsWith('Arrow') && this.k <= 1.001) return;
            e.preventDefault();
        },

        // ---------- Filtros por tipo (se combinan: el estudiante debe cumplir todos) ----------
        isOn(key) {
            return key === 'todos' ? this.tipos.length === 0 : this.tipos.includes(key);
        },

        toggleTipo(key) {
            if (key === 'todos') return this.setTipos([]);
            this.setTipos(this.tipos.includes(key) ? this.tipos.filter((t) => t !== key) : [...this.tipos, key]);
        },

        setTipos(list) {
            this.tipos = list;
            this.$root.dataset.filter = list.length ? list.join(',') : 'todos';
            this.selected = [];
            this.apply();
            this.hide();
            const url = new URL(window.location.href);
            if (list.length) url.searchParams.set('tipo', list.join(',')); else url.searchParams.delete('tipo');
            window.history.replaceState(null, '', url);
            // La lista «Piden atención» del panel sigue al mapa.
            window.dispatchEvent(new CustomEvent('cohort-filter', { detail: { tipos: [...list] } }));
        },

        // Los estudiantes que no cumplen el filtro se atenúan (siguen dando contexto) y salen del orden de tabulación.
        apply() {
            let count = 0;
            for (const item of items) {
                item.on = this.tipos.every((t) => item.tags.includes(t));
                if (item.on) count++;
            }
            this.shown = count;
            this.dense = count > DENSE;
            for (const item of items) {
                item.el.toggleAttribute('data-dim', !item.on);
                if (item.trail) item.trail.toggleAttribute('data-dim', !item.on);
                if (item.on) item.el.removeAttribute('aria-hidden'); else item.el.setAttribute('aria-hidden', 'true');
                // Con cientos de puntos, recorrerlos con Tab es inútil: para eso está la tabla.
                if (item.on && !this.dense) item.el.removeAttribute('tabindex'); else item.el.setAttribute('tabindex', '-1');
            }
            this.markSelected();
            this.regroup();
            this.refreshTable();
            this.buildSummary();
        },

        // ---------- Ubicar a un estudiante ----------
        search() {
            const q = fold(this.query).trim();
            if (!q) { this.results = []; this.open = false; this.unfind(); return; }
            const words = q.split(/\s+/);
            this.results = items.filter((item) => words.every((w) => item.haystack.includes(w))).slice(0, 6)
                .map((item) => ({ id: item.id, name: item.name, code: item.code, cohort: item.cohort }));
            this.cursor = 0;
            this.open = true;
        },

        onSearchKey(e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                if (!this.results.length) return;
                e.preventDefault();
                this.open = true;
                this.cursor = this.clamp(this.cursor + (e.key === 'ArrowDown' ? 1 : -1), 0, this.results.length - 1);
            } else if (e.key === 'Enter') {
                if (this.open && this.results[this.cursor]) { e.preventDefault(); this.locate(this.results[this.cursor]); }
            } else if (e.key === 'Escape') {
                if (this.open) { this.open = false; } else { this.query = ''; this.search(); }
            }
        },

        closeSoon() {
            clearTimeout(findTimer);
            findTimer = setTimeout(() => { this.open = false; }, 120);
        },

        unfind() {
            if (!found) return;
            found.el.removeAttribute('data-found');
            found = null;
            this.regroup();
        },

        // Acerca la vista a ese estudiante, lo resalta y abre su tarjeta. Si el filtro lo tenía atenuado, se quita para poder verlo.
        async locate(result) {
            const item = items.find((i) => i.id === result.id);
            if (!item) return;
            this.query = item.name;
            this.open = false;
            this.unfind();
            if (!item.on) this.setTipos([]);
            if (this.view !== 'mapa') { this.view = 'mapa'; await this.$nextTick(); this.measure(); }
            found = item;
            item.el.setAttribute('data-found', '');
            this.regroup();
            const k = this.clamp(Math.max(this.k, 3), 1, this.max);
            const spot = this.spot(item);
            this.hide();
            this.setView(k, this.W / 2 - spot.x * k, this.H / 2 - spot.y * k, true);
            const finish = () => { this.show(item.el); this.note = `Ubicado: ${item.name}. Cohorte ${item.cohort}, ${item.pct} % del plan.`; };
            if (this.reduced) finish(); else setTimeout(finish, 200);
        },

        // ---------- Comparación con el periodo anterior ----------
        toggleTrails() {
            this.trails = !this.trails;
            this.$root.toggleAttribute('data-trails', this.trails);
            if (!this.trails) { this.note = 'Se ocultó el cambio frente al periodo anterior.'; return; }
            const moved = items.filter((i) => i.on && i.prev !== null && i.prev !== i.pct).length;
            this.note = moved
                ? `Se muestra el cambio de ${moved} ${moved === 1 ? 'estudiante' : 'estudiantes'} frente al periodo anterior: la flecha apunta al avance de hoy.`
                : 'Ningún estudiante visible tiene un periodo anterior con el cual compararse.';
        },

        // ---------- Selección con recuadro ----------
        toggleSelecting() {
            this.selecting = !this.selecting;
            this.$refs.viewport.toggleAttribute('data-selecting', this.selecting);
            this.note = this.selecting ? 'Selección con recuadro activada: arrastre sobre el mapa.' : 'Selección con recuadro desactivada.';
        },

        dragBox(e, vp) {
            const r = this.$refs.plot.getBoundingClientRect();
            const x = this.clamp(e.clientX - r.left, 0, r.width);
            const y = this.clamp(e.clientY - r.top, 0, r.height);
            if (!boxDrag.moved && Math.hypot(x - boxDrag.x, y - boxDrag.y) < 4) return;
            if (!boxDrag.moved) { boxDrag.moved = true; this.hide(); vp.setPointerCapture(e.pointerId); }
            this.box = { x: Math.min(boxDrag.x, x), y: Math.min(boxDrag.y, y), w: Math.abs(x - boxDrag.x), h: Math.abs(y - boxDrag.y) };
        },

        // Quién queda dentro del recuadro se calcula con la posición del modelo (también para los que una burbuja esconde).
        finishBox() {
            const b = this.box;
            if (!b || (b.w < 4 && b.h < 4)) return;
            this.selected = items.filter((item) => {
                if (!item.on) return false;
                const s = this.spot(item);
                const sx = this.tx + s.x * this.k;
                const sy = this.ty + s.y * this.k;
                return sx >= b.x && sx <= b.x + b.w && sy >= b.y && sy <= b.y + b.h;
            }).map((item) => item.id);
            this.markSelected();
            this.regroup();
            this.note = this.selected.length
                ? `${this.selected.length} ${this.selected.length === 1 ? 'estudiante seleccionado' : 'estudiantes seleccionados'}. Puede exportarlos a CSV.`
                : 'El recuadro no encerró a ningún estudiante.';
        },

        markSelected() {
            const ids = new Set(this.selected);
            for (const item of items) item.el.toggleAttribute('data-selected', ids.has(item.id));
        },

        selectedItems() {
            const ids = new Set(this.selected);
            return items.filter((item) => ids.has(item.id));
        },

        clearSelection() {
            this.selected = [];
            this.markSelected();
            this.regroup();
        },

        // ---------- Resumen por cohorte ----------
        selectRow(r) {
            if (this.row === r) return this.closeSummary();
            this.row = r;
            this.buildSummary();
            this.$nextTick(() => this.$refs.summary && this.$refs.summary.scrollIntoView({ block: 'nearest', behavior: this.reduced ? 'auto' : 'smooth' }));
            const here = items.filter((item) => item.row === r);
            const list = here.filter((item) => item.on);
            this.fit(list.length ? list : here);
        },

        closeSummary() {
            this.row = null;
            this.summary = null;
        },

        buildSummary() {
            if (this.row === null) return;
            const here = items.filter((item) => item.row === this.row);
            if (!here.length) { this.summary = null; return; }
            const papas = here.filter((item) => item.papaNum >= 0);
            const mean = (list, key) => list.reduce((sum, item) => sum + item[key], 0) / list.length;
            const count = (tag) => here.filter((item) => item.tags.includes(tag)).length;
            this.summary = {
                label: config.labels[this.row],
                stats: [
                    { label: 'Estudiantes', value: String(here.length) },
                    { label: 'Avance medio', value: `${Math.round(mean(here, 'pct'))} %` },
                    { label: 'Avance esperado', value: `${here[0].expected} %` },
                    { label: 'PAPA medio', value: papas.length ? decimal(mean(papas, 'papaNum')) : '—' },
                    { label: 'En riesgo', value: String(count('en-riesgo')) },
                    { label: 'Rezagados', value: String(count('rezagado')) },
                    { label: 'Con alerta', value: String(count('con-alerta')) },
                ],
            };
        },

        // ---------- Burbujas cuando hay muchos puntos ----------
        // Agrupa en una rejilla del PLANO (la celda se encoge al acercar), así arrastrar el mapa no regrupa ni parpadea: solo el zoom lo hace.
        regroup() {
            const next = new Map();
            const crowded = this.W > 0 && this.shown > config.clusterOver && this.k < this.max - 0.001;
            if (crowded) {
                const size = CELL / this.k;
                const ids = new Set(this.selected);
                for (const item of items) {
                    if (!item.on || item === found || item.el === active) continue;
                    const s = this.spot(item);
                    const key = `${Math.floor(s.x / size)}:${Math.floor(s.y / size)}`;
                    if (!next.has(key)) next.set(key, []);
                    next.get(key).push({ item, s });
                }
                for (const [key, list] of next) if (list.length < 2) next.delete(key);
                this.clusters = this.clusters.length || next.size ? this.buildClusters(next, ids) : this.clusters;
            } else if (this.clusters.length) {
                this.clusters = [];
                signature = '';
            }
            const hidden = new Set();
            for (const list of next.values()) for (const { item } of list) hidden.add(item.el);
            for (const el of grouped) if (!hidden.has(el)) el.removeAttribute('data-clustered');
            for (const el of hidden) if (!grouped.has(el)) el.setAttribute('data-clustered', '');
            grouped = hidden;
        },

        buildClusters(buckets, ids) {
            const rowH = this.H / config.rows;
            const built = [...buckets].map(([key, list]) => {
                const x = list.reduce((sum, m) => sum + m.s.x, 0) / list.length;
                const y = list.reduce((sum, m) => sum + m.s.y, 0) / list.length;
                return {
                    key, n: list.length, px: (x / this.W) * 100, r: y / rowH - 0.5, j: 0,
                    // Junto a los bordes del plano la burbuja se empuja hacia adentro lo justo para que no la recorte (su radio es 16 px).
                    nx: this.edgeNudge(x * this.k),
                    alert: list.some((m) => m.item.alerts > 0), selected: list.some((m) => ids.has(m.item.id)),
                    members: list.map((m) => m.item),
                };
            });
            const sig = built.map((c) => `${c.key}/${c.n}/${c.selected}/${c.nx}`).join('|');
            if (sig === signature) return this.clusters;
            signature = sig;
            return built;
        },

        // Cuánto mover una burbuja (px) para que quede entera dentro del plano; `x` es su distancia al borde izquierdo con el zoom aplicado.
        edgeNudge(x) {
            const r = 16;
            const width = this.W * this.k;
            if (x < r) return Math.round(r - x);
            if (x > width - r) return Math.round(width - r - x);
            return 0;
        },

        // Acerca la vista al centro de la burbuja hasta que se separe.
        zoomTo(cluster) {
            const k = this.clamp(this.k * 2.2, 1, this.max);
            const x = (cluster.px / 100) * this.W;
            const y = (cluster.r + 0.5) * (this.H / config.rows);
            this.hide();
            this.setView(k, this.W / 2 - x * k, this.H / 2 - y * k, true);
        },

        // ---------- Vista de tabla ----------
        sortBy(key) {
            this.sortDir = this.sortKey === key ? -this.sortDir : 1;
            this.sortKey = key;
            this.refreshTable();
        },

        refreshTable() {
            const key = this.sortKey;
            const text = (value) => String(value ?? '');
            const sorted = items.filter((item) => item.on).sort((a, b) => {
                const x = a[key];
                const y = b[key];
                const order = typeof x === 'number' && typeof y === 'number' ? x - y : text(x).localeCompare(text(y), 'es');
                return (order || a.name.localeCompare(b.name, 'es')) * (order ? this.sortDir : 1);
            });
            this.tableTotal = sorted.length;
            this.tableRows = sorted.slice(0, TABLE_MAX).map(({ id, name, code, cohort, pct, expected, papa, papaLow, state, alerts, last, url }) => ({ id, name, code, cohort, pct, expected, papa, papaLow, state, alerts, last, url }));
        },

        // ---------- Exportar ----------
        // Se genera en el navegador con lo que ya está en pantalla: no pasa por el servidor, así que no queda registro en la bitácora.
        exportCsv(list = items.filter((item) => item.on), name = 'estudiantes') {
            if (!list.length) { this.note = 'No hay estudiantes para exportar.'; return; }
            const head = ['Código', 'Nombre', 'Cohorte', 'Avance del plan (%)', 'Avance esperado (%)', 'PAPA', 'Estado', 'Alertas abiertas', 'Última tutoría', 'Marcas'];
            const rows = list.map((i) => [
                i.code, i.name, i.cohort, i.pct, i.expected, i.papa, i.state, i.alerts, i.last || 'Sin tutorías',
                i.tags.map((t) => config.kinds[t]).filter(Boolean).join(', '),
            ]);
            // BOM y «;» para que Excel en español lo abra con tildes y en columnas.
            const csv = '\uFEFF' + [head, ...rows].map((line) => line.map(cell).join(';')).join('\r\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
            link.download = `${name}-${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(link.href), 1000);
            this.note = `Se exportaron ${list.length} ${list.length === 1 ? 'estudiante' : 'estudiantes'} a ${link.download}.`;
        },

        // ---------- Vistas guardadas (solo en este navegador) ----------
        loadViews() {
            try {
                const valid = Object.keys(config.kinds);
                const list = JSON.parse(window.localStorage.getItem('trz-mapa-vistas') || '[]');
                this.saved = Array.isArray(list)
                    ? list.filter((v) => v && typeof v.name === 'string' && Array.isArray(v.tipos)).map((v) => ({ name: v.name, tipos: v.tipos.filter((t) => valid.includes(t)) }))
                    : [];
            } catch { this.saved = []; /* almacenamiento bloqueado o dañado: sin vistas guardadas */ }
        },

        startSaving() {
            this.saving = true;
            this.saveError = '';
            this.viewName = '';
            // Un campo oculto no recibe foco y el formulario tarda un cuadro en mostrarse: se espera a que sea visible (máx. ~10 cuadros).
            let tries = 0;
            const focusIt = () => {
                const input = this.$refs.viewName;
                if (input && input.offsetParent !== null) input.focus();
                else if (++tries < 10) requestAnimationFrame(focusIt);
            };
            requestAnimationFrame(focusIt);
        },

        saveView() {
            const name = this.viewName.trim();
            if (!name) { this.saveError = 'Escriba un nombre para la vista.'; return; }
            if (!this.tipos.length) { this.saveError = 'Elija primero uno o más tipos de estudiante: la vista recuerda esa combinación.'; return; }
            const next = [...this.saved.filter((v) => v.name !== name), { name, tipos: [...this.tipos] }].slice(-12);
            try {
                window.localStorage.setItem('trz-mapa-vistas', JSON.stringify(next));
            } catch { this.saveError = 'Este navegador no deja guardar vistas (¿navegación privada?).'; return; }
            this.saved = next;
            this.saving = false;
            this.note = `Vista «${name}» guardada en este navegador.`;
        },

        applyView(view) {
            this.setTipos([...view.tipos]);
            this.note = `Vista «${view.name}» aplicada.`;
        },

        removeView(view) {
            const next = this.saved.filter((v) => v.name !== view.name);
            try { window.localStorage.setItem('trz-mapa-vistas', JSON.stringify(next)); } catch { /* se quita de la pantalla aunque no se pueda persistir */ }
            this.saved = next;
            this.note = `Vista «${view.name}» quitada.`;
        },

        // ---------- Tarjeta de cada punto ----------
        show(dot) {
            clearTimeout(hideTimer);
            if (dot.hasAttribute('data-dim')) return;
            active = dot;
            const d = dot.dataset;
            this.tip = {
                url: dot.getAttribute('href') || '#',
                name: d.name, code: d.code, cohort: d.cohort, pct: Number(d.pct), papa: d.papa, papaLow: d.papaLow === '1',
                state: d.state, alerts: Number(d.alerts), note: d.note,
                expected: Number(d.expected || 0), behind: dot.hasAttribute('data-behind'),
                prev: d.prev === undefined ? null : Number(d.prev), last: d.last || '',
                extras: d.tags.split(' ').filter((t) => t && !own.includes(t) && t !== 'con-alerta').map((t) => config.kinds[t] || t),
            };
            this.$nextTick(() => this.place());
        },

        // La tarjeta va junto al punto y se voltea cuando no cabe a la derecha o abajo.
        place() {
            if (!this.tip || !active) return;
            const wrap = this.$refs.wrap.getBoundingClientRect();
            const dot = active.getBoundingClientRect();
            const card = this.$refs.card;
            const cw = card.offsetWidth || 260;
            const ch = card.offsetHeight || 190;
            const cx = dot.left - wrap.left + dot.width / 2;
            const cy = dot.top - wrap.top + dot.height / 2;
            let x = cx + 14;
            let y = cy + 14;
            if (x + cw > wrap.width) x = cx - cw - 14;
            if (y + ch > wrap.height + 24) y = cy - ch - 14;
            this.card = { x: Math.round(this.clamp(x, 0, Math.max(0, wrap.width - cw))), y: Math.round(Math.max(0, y)) };
        },

        hide() {
            clearTimeout(hideTimer);
            this.tip = null;
            active = null;
        },

        // Un respiro para llevar el mouse del punto a la tarjeta sin que se cierre.
        hideSoon() {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(() => this.hide(), 160);
        },

        showHint() {
            this.hint = true;
            clearTimeout(hintTimer);
            hintTimer = setTimeout(() => { this.hint = false; }, 1400);
        },
    };
});

// La lista «Piden atención» del panel sigue al filtro del mapa: muestra hasta 6 de las situaciones recientes que cumplen todos los tipos elegidos.
Alpine.data('attentionList', (initial = []) => ({
    tipos: initial,
    empty: false,

    init() {
        this.refresh();
        window.addEventListener('cohort-filter', (e) => { this.tipos = e.detail.tipos; this.refresh(); });
    },

    refresh() {
        let shown = 0;
        let matches = 0;
        this.$root.querySelectorAll('[data-attention]').forEach((li) => {
            const tags = (li.dataset.tags || '').split(' ');
            const match = this.tipos.every((t) => tags.includes(t));
            if (match) matches++;
            const visible = match && shown < 6;
            if (visible) shown++;
            li.hidden = !visible;
        });
        this.empty = matches === 0 && this.$root.querySelector('[data-attention]') !== null;
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
