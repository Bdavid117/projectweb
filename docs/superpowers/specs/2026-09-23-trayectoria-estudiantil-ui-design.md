# Trayectoria Estudiantil — Implementación visual del mockup en Laravel

**Fecha:** 2026-09-23
**Estado:** Aprobado para planificación

## Contexto

El proyecto es una app Laravel recién inicializada (sin Tailwind, sin auth, sin
controladores más allá del stub por defecto). En `mokup/` existe un diseño
completo hecho en Pencil (`trayectoria-estudiantil.pen`) para un sistema de
"Trayectoria Estudiantil" (proyecto académico, curso "Programación con
Tecnologías Web 2026-2S"), dirigido a un rol de coordinación académica.

El `.pen` contiene:
- 9 frames de nivel superior: `00 · Sistema de diseño`, `01 · Componentes`,
  `10 · Inicio de sesión`, `11 · Panel · Coordinación`,
  `12 · Estudiantes · Listado`, `13 · Ficha del estudiante`,
  `14 · Solicitud · Formulario`, `15 · Indicadores del programa`,
  `20 · Vistas móviles · 360 px`.
- 7 componentes reutilizables: Botón primario, Botón secundario, Botón sutil,
  Item de navegación, Tarjeta de indicador, Etiqueta de estado, Campo de
  formulario.
- El frame `20 · Vistas móviles` contiene 3 variantes responsivas a 360px:
  Listado de estudiantes, Ficha del estudiante, Nueva solicitud.
- `design-tokens.css` (fuente de verdad de tokens) mapea 1:1 con las
  variables internas del `.pen` (p. ej. `$primary-600` ↔
  `--color-primary-600`, `$text-on-brand` ↔ `--text-on-brand`).
- `pantallas-tailwind.html` y `exports/*.png` son referencias visuales
  (export directo con posiciones absolutas por píxel vía CDN de Tailwind) —
  se usan solo como referencia de contenido/copy/iconografía, no como base
  de implementación.

## Objetivo

Convertir las 6 pantallas funcionales del diseño (excluyendo los frames
`00 · Sistema de diseño` y `01 · Componentes`, que son documentación interna
del design system) en vistas Blade reales con Tailwind CSS v4, con layout
responsivo real (flex/grid, no posicionamiento absoluto fijo), navegables en
el navegador. Sin autenticación real ni persistencia en base de datos —
alcance puramente visual/presentacional para esta entrega.

## Fuera de alcance

- Autenticación real, hashing de contraseñas, sesiones persistentes.
- Modelos, migraciones, base de datos.
- Frames `00 · Sistema de diseño` y `01 · Componentes` como pantallas propias
  (su contenido se usa como referencia para tokens y componentes, no se
  renderiza como página).
- Validación de formularios en el servidor.

## Arquitectura

### Tooling

- Instalar Tailwind CSS v4 vía el plugin oficial de Vite:
  `npm i -D tailwindcss @tailwindcss/vite`, registrado en `vite.config.js`.
- `resources/css/app.css`:
  - `@import "tailwindcss";` al inicio.
  - Bloque `:root { ... }` con todo el contenido de
    `mokup/design-tokens.css` (colores, tipografía, espaciado, radios,
    sombras, breakpoints, dimensiones de layout) — copiado tal cual, son la
    fuente de verdad.
  - `@layer base` con las utilidades de familia tipográfica
    (`.font-sans` si aplica) y `html, body { height: 100% }`.
- Fuente Inter cargada por `<link>` de Google Fonts en el `<head>` del layout
  base (igual que en el `.pen`/export).
- Todas las clases de color/espaciado/radio se escriben como valores
  arbitrarios referenciando variables: `bg-[var(--color-primary-600)]`,
  `text-[var(--text-primary)]`, `rounded-[var(--radius-md)]`, etc. Nunca hex
  directo ni estilos inline.

### Layouts Blade

- `resources/views/layouts/guest.blade.php`: layout minimal de dos paneles
  (marca + acceso) usado solo por Login.
- `resources/views/layouts/app.blade.php`: layout con Barra superior
  (`--topbar-height`, 60px) + Menú lateral (`--sidebar-width`, 260px) +
  slot de Contenido. Usado por Panel, Listado, Ficha, Formulario e
  Indicadores.

### Componentes reutilizables

En `resources/views/components/`, uno por cada componente reutilizable del
`.pen`, extraído con `Get(componentId, {depth: N})` vía el MCP de Pencil
para capturar props/estructura exacta:

- `x-button` — variantes primary/secondary/subtle (prop `variant`), acepta
  slot para la etiqueta.
- `x-nav-item` — ítem de navegación del menú lateral (icono + etiqueta +
  estado activo).
- `x-indicator-card` — tarjeta de indicador (usada en Panel e Indicadores).
- `x-status-badge` — etiqueta de estado (usada en Listado y Ficha).
- `x-form-field` — campo de formulario con label, control y mensaje de
  ayuda/error (usado en Login y Formulario de solicitud).

### Iconos

El diseño usa 25 íconos distintos del set estándar de Lucide, referenciados
por nombre en el `.pen` (`icon: "download"`, `library: "lucide"`, etc.):
`graduation-cap`, `calendar`, `bell`, `chevron-down`, `upload`,
`message-square`, `file-text`, `circle-check`, `sparkles`, `file-plus`,
`user-plus`, `search`, `chevron-right`, `book-open`, `plane`, `mic`,
`briefcase`, `lock`, `cloud-upload`, `x`, `circle-alert`, `info`, `menu`,
`download`, `users`.

Dado que son 25 íconos del set estándar (no artes personalizados), se usa el
paquete Composer `mallardduck/blade-lucide-icons`, que provee un componente
`<x-lucide-{nombre} class="..." />` por cada ícono de Lucide. Color se
controla con `text-[var(--color-...)]` (el paquete usa `currentColor` como
`stroke`/`fill`). Esto evita mantener 25 SVGs a mano y sigue siendo un set
1:1 con el diseño.

## Rutas y controladores

Todas simples, un método cada una, sin validación real, datos de ejemplo
como arrays PHP definidos en el propio controlador (reemplazables después
por consultas reales):

| Ruta | Método | Controlador | Pantalla |
|---|---|---|---|
| `GET /login` | `show` | `LoginController` | 10 · Inicio de sesión |
| `POST /login` | `store` | `LoginController` | redirige a `/panel`, sin validar credenciales |
| `GET /panel` | `index` | `PanelController` | 11 · Panel · Coordinación |
| `GET /estudiantes` | `index` | `StudentController` | 12 · Estudiantes · Listado |
| `GET /estudiantes/{id}` | `show` | `StudentController` | 13 · Ficha del estudiante |
| `GET /solicitudes/nueva` | `create` | `RequestController` | 14 · Solicitud · Formulario |
| `GET /indicadores` | `index` | `IndicatorController` | 15 · Indicadores del programa |

`GET /` se deja apuntando a la vista `welcome` actual (sin cambios) — no
forma parte de este alcance.

Los datos de ejemplo (nombres de estudiantes, estados, cifras de
indicadores) se toman del contenido visible en el `.pen`/capturas cuando
exista, y se completan con valores plausibles y coherentes cuando el diseño
no fije un valor concreto.

## Responsividad

Las pantallas `12 · Estudiantes · Listado`, `13 · Ficha del estudiante` y
`14 · Solicitud · Formulario` tienen mockups móviles explícitos a 360px en
el frame `20 · Vistas móviles`; esos frames se usan como referencia exacta
para el comportamiento en viewport pequeño (breakpoint base, sin prefijo, o
`sm:` para desktop según convenga). Para `11 · Panel · Coordinación` y
`15 · Indicadores del programa` (sin mockup móvil explícito) se aplica el
mismo patrón responsivo del sistema (menú lateral colapsable, tarjetas de
indicador apilables en columna) de forma consistente con las demás
pantallas.

## Verificación

Por cada pantalla, tras implementarla:
- Levantar `npm run dev` + servidor Laravel (`php artisan serve`) y revisar
  en navegador.
- Comparar contra el `.pen` (o su captura PNG en `mokup/exports/`) en
  desktop (1440px) y móvil (360px cuando exista mockup): layout no
  colapsado, sin contenido cortado, contraste de texto suficiente,
  `fill_container`/`fit_content` traducidos correctamente a `flex-1`/`w-fit`
  etc., sin overflow horizontal.
- Confirmar que no hay estilos inline ni colores hardcodeados fuera de los
  tokens.

## Riesgos / decisiones abiertas

- Los datos de ejemplo no vienen 100% especificados en el diseño para todas
  las filas de tablas/listas; se completan con criterio manteniendo
  coherencia con lo que sí está visible (nombres, estados, cifras de
  indicadores).
- Si algún nombre de ícono del `.pen` no existe en el paquete
  `blade-lucide-icons` instalado, se verifica el nombre equivalente más
  cercano en la librería Lucide (pueden variar guiones/alias entre
  versiones) antes de sustituirlo.
