// Íconos animados (@animated-color-icons/lucide-wc): cada import registra su <animated-lucide-*>.
// Mantener sincronizado con config/animated-icons.php (lo verifica AnimatedIconsTest).
import '@animated-color-icons/lucide-wc/BarChart3.js';
import '@animated-color-icons/lucide-wc/Bell.js';
import '@animated-color-icons/lucide-wc/BookOpen.js';
import '@animated-color-icons/lucide-wc/Briefcase.js';
import '@animated-color-icons/lucide-wc/Calendar.js';
import '@animated-color-icons/lucide-wc/ChevronDown.js';
import '@animated-color-icons/lucide-wc/ChevronRight.js';
import '@animated-color-icons/lucide-wc/CircleAlert.js';
import '@animated-color-icons/lucide-wc/CircleCheck.js';
import '@animated-color-icons/lucide-wc/ClipboardList.js';
import '@animated-color-icons/lucide-wc/CloudUpload.js';
import '@animated-color-icons/lucide-wc/Download.js';
import '@animated-color-icons/lucide-wc/Eye.js';
import '@animated-color-icons/lucide-wc/EyeOff.js';
import '@animated-color-icons/lucide-wc/FilePlus.js';
import '@animated-color-icons/lucide-wc/FileText.js';
import '@animated-color-icons/lucide-wc/History.js';
import '@animated-color-icons/lucide-wc/Info.js';
import '@animated-color-icons/lucide-wc/LayoutDashboard.js';
import '@animated-color-icons/lucide-wc/List.js';
import '@animated-color-icons/lucide-wc/Lock.js';
import '@animated-color-icons/lucide-wc/LogOut.js';
import '@animated-color-icons/lucide-wc/Mail.js';
import '@animated-color-icons/lucide-wc/Menu.js';
import '@animated-color-icons/lucide-wc/MessageSquare.js';
import '@animated-color-icons/lucide-wc/Mic.js';
import '@animated-color-icons/lucide-wc/Plane.js';
import '@animated-color-icons/lucide-wc/Plus.js';
import '@animated-color-icons/lucide-wc/Search.js';
import '@animated-color-icons/lucide-wc/Shield.js';
import '@animated-color-icons/lucide-wc/Sparkles.js';
import '@animated-color-icons/lucide-wc/Star.js';
import '@animated-color-icons/lucide-wc/TrendingUp.js';
import '@animated-color-icons/lucide-wc/TriangleAlert.js';
import '@animated-color-icons/lucide-wc/Trophy.js';
import '@animated-color-icons/lucide-wc/Upload.js';
import '@animated-color-icons/lucide-wc/User.js';
import '@animated-color-icons/lucide-wc/UserPlus.js';
import '@animated-color-icons/lucide-wc/Users.js';
import '@animated-color-icons/lucide-wc/X.js';

// Los íconos solo reaccionan a su propio :hover (CSS dentro del Shadow DOM). Para animarlos cuando el puntero o el
// foco entra en el control que los contiene (enlace, botón, etiqueta o [data-icon-trigger]) se reutiliza su CSS
// cambiando :hover por el atributo `playing` del host.
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const sheets = new Map();

const playingSheet = (icon) => {
    if (!sheets.has(icon.localName)) {
        const css = [...icon.shadowRoot.querySelectorAll('style')]
            .map((style) => style.textContent)
            .join('\n')
            .replaceAll('.animated-lucide-icon:hover', ':host([playing]) .animated-lucide-icon')
            .replaceAll('.al-icon-wrapper:hover', ':host([playing]) .al-icon-wrapper');
        const sheet = new CSSStyleSheet();
        sheet.replaceSync(css);
        sheets.set(icon.localName, sheet);
    }

    return sheets.get(icon.localName);
};

const setPlaying = (event, on) => {
    const trigger = event.target.closest?.('a, button, label, [data-icon-trigger]');
    if (!trigger || (on && reducedMotion.matches)) return;
    if (event.relatedTarget && trigger.contains(event.relatedTarget)) return;

    trigger.querySelectorAll('[data-animated-icon]').forEach((icon) => {
        if (on && !icon.shadowRoot.adoptedStyleSheets.includes(playingSheet(icon))) {
            icon.shadowRoot.adoptedStyleSheets = [...icon.shadowRoot.adoptedStyleSheets, playingSheet(icon)];
        }
        icon.toggleAttribute('playing', on);
    });
};

document.addEventListener('pointerover', (event) => setPlaying(event, true));
document.addEventListener('pointerout', (event) => setPlaying(event, false));
document.addEventListener('focusin', (event) => setPlaying(event, true));
document.addEventListener('focusout', (event) => setPlaying(event, false));

// Reparación de @animated-color-icons/lucide-wc 1.0.0: arma los trazos con template.innerHTML (HTML), así que quedan como
// HTMLUnknownElement anidados y el navegador no los pinta. Todos sus trazos son path/rect/circle/line autocerrados, por lo
// que basta reconstruirlos como nodos SVG hermanos. Si una versión futura ya los crea bien, no hace nada.
// Además retira el <title> en inglés (tooltip no deseado): los íconos son decorativos.
const SVG_NS = 'http://www.w3.org/2000/svg';

const repair = (icon) => {
    const svg = icon.shadowRoot?.querySelector('svg');
    if (!svg) return;

    svg.querySelector('title')?.remove();

    const broken = [...svg.querySelectorAll('*')].filter((node) => node.namespaceURI !== SVG_NS);
    if (broken.length === 0) return;

    svg.replaceChildren(...broken.map((node) => {
        const shape = document.createElementNS(SVG_NS, node.localName);
        for (const { name, value } of node.attributes) shape.setAttribute(name, value);

        return shape;
    }));
};

const repairWithin = (root) => {
    if (root.matches?.('[data-animated-icon]')) repair(root);
    root.querySelectorAll?.('[data-animated-icon]').forEach(repair);
};

repairWithin(document);
new MutationObserver((mutations) => {
    mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) repairWithin(node);
    }));
}).observe(document.body, { childList: true, subtree: true });
