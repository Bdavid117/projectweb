# Trayectoria Estudiantil

Sistema de seguimiento de trayectorias estudiantiles para un área de coordinación académica. Construido con **Laravel 10** (PHP 8.1) y **Tailwind CSS v4**, a partir de un mockup de diseño en Pencil.

> **Estado actual del proyecto:** hay base de datos PostgreSQL (25 tablas, modelos y seeders) y login real con 4 roles (estudiante, profesor, tutor, coordinación) con permisos por ruta. Las 6 pantallas todavía muestran datos mock definidos en los controladores, salvo el usuario y el menú, que salen de la sesión; conectarlas a la base de datos es la siguiente etapa.

## Índice

- [Pantallas](#pantallas)
- [Stack técnico](#stack-técnico)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Comandos](#comandos)
- [Arquitectura](#arquitectura)
- [Convención de commits](#convención-de-commits)
- [Documentación adicional](#documentación-adicional)

## Pantallas

| Pantalla | Ruta | Controlador |
|---|---|---|
| Login | `GET/POST /login` | `LoginController` |
| Panel de coordinación | `GET /panel` | `PanelController` |
| Listado de estudiantes | `GET /estudiantes` | `StudentController@index` |
| Perfil de estudiante | `GET /estudiantes/{id}` | `StudentController@show` |
| Nueva solicitud | `GET/POST /solicitudes/nueva` | `RequestController` |
| Indicadores del programa | `GET /indicadores` | `IndicatorController` |

`GET /estudiantes/{id}` ignora intencionalmente el `{id}` y siempre renderiza el mismo perfil ilustrativo — es una decisión de alcance, no un bug.

## Stack técnico

- **Backend:** Laravel 10, PHP 8.1
- **Frontend:** Blade + Tailwind CSS v4 (vía `@tailwindcss/vite`), iconos con [`mallardduck/blade-lucide-icons`](https://github.com/mallardduck/blade-lucide-icons)
- **Base de datos:** PostgreSQL (`pdo_pgsql`)
- **Build tool:** Vite
- **Tests:** PHPUnit (Feature + Unit)

## Requisitos

- PHP ^8.1 con Composer y la extensión `pdo_pgsql`
- PostgreSQL en ejecución (por ejemplo `brew install postgresql`)
- Node.js con npm
- Copia local de `mokup/trayectoria-estudiantil.pen` y `mokup/design-tokens.css` si necesitas consultar el diseño original (la carpeta `mokup/` está en `.gitignore`, no forma parte del repositorio)

## Instalación

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# Bases de datos (la de pruebas es obligatoria: `php artisan test` falla sin ella)
createdb trayectoria_estudiantil
createdb trayectoria_estudiantil_test
# Ajusta DB_USERNAME (y DB_PASSWORD si aplica) en .env; en macOS con Homebrew suele ser tu usuario del sistema
php artisan migrate --seed
```

Usuarios demo (contraseña `password`): `coordinacion@unal.edu.co`, `tutor@unal.edu.co`, `profesor@unal.edu.co`, `estudiante@unal.edu.co`.

Levanta el servidor y, en otra terminal, el servidor de desarrollo de Vite:

```bash
php artisan serve
npm run dev
```

## Comandos

```bash
# Dependencias PHP / tests
composer install
php artisan migrate:fresh --seed      # recrea la base con datos demo
php artisan test                       # suite completa (usa la base trayectoria_estudiantil_test)
php artisan test --filter=LoginTest    # una sola clase de test
php artisan test tests/Feature/StudentTest.php::test_student_show_renders_profile_regardless_of_id

# Frontend (Tailwind v4 vía @tailwindcss/vite)
npm install
npm run dev                            # servidor de Vite, necesario para que @vite() resuelva sin build
npm run build                          # build de producción -> public/build

# Servidor local
php artisan serve
```

> Los tests de feature llaman a `$this->withoutVite()` en `tests/TestCase.php::setUp()`, así que `php artisan test` funciona sin necesidad de correr `npm run build` antes.

## Arquitectura

- **Design tokens:** todos los colores, espaciados, radios y tipografías viven como custom properties CSS en un bloque `:root` al inicio de `resources/css/app.css` (copiado de `mokup/design-tokens.css`). Las vistas Blade los referencian con valores arbitrarios de Tailwind (`bg-[var(--color-primary-600)]`, `text-[var(--text-secondary)]`, etc.) — nunca hex hardcodeado.
- **Componentes compartidos** (`resources/views/components/`): `x-button`, `x-nav-item`, `x-indicator-card`, `x-status-badge`, `x-form-field`, réplica 1:1 de los componentes reutilizables del diseño en Pencil.
- **Layouts** como componentes Blade anónimos (no `@extends`/`@yield`): `<x-layouts.app>` para el shell con topbar + sidebar, `<x-layouts.guest>` para el panel de login.
- **Navegación** (`app/Support/Navigation.php`): helper estático que arma los grupos de navegación según el rol de la pantalla (coordinador, tutor, estudiante), con el rol tomado de la sesión (`Navigation::forRole`).
- **Ruteo:** patrón una ruta → un método de controlador de propósito único → una vista Blade. Los controladores guardan los datos mock como arrays; los modelos y migraciones ya existen, pero las pantallas aún no los usan. Las rutas están protegidas con los middleware `auth` y `role:`.

Una regla que no es obvia leyendo un solo archivo: cualquier clase de Tailwind que mapea una variante/tono/estado a estilos (`variant` de `x-button`, `tone` de `x-status-badge`, `value-class` de `x-indicator-card`) debe ser un string literal completo seleccionado desde un array/match de PHP — nunca construido concatenando una variable dentro del nombre de la clase, porque el build de Tailwind solo detecta clases que aparecen como strings literales en el código fuente.

El `style=` inline está prohibido salvo para valores por fila que el scanner estático de Tailwind no puede expresar como clase (alturas de barras de gráficos, anchos de progress bars, `stroke-dasharray`/`stroke-dashoffset` del anillo de progreso SVG del perfil de estudiante).

## Convención de commits

Se usa [Conventional Commits](https://www.conventionalcommits.org/) con scope obligatorio:

```
type(scope): descripción breve en español, en minúsculas
```

**Tipos:** `feat` (nuevas pantallas/componentes), `fix` (corrección de bugs), `test` (tests), `docs` (specs/plan/`CLAUDE.md`), `chore` (tooling/config sin cambio de comportamiento).

**Scopes comunes:** `add`, `setup`, `login`, `plan`, `responsive`, `nav` — son ejemplos, no una lista cerrada; se elige el scope que nombre el área realmente afectada.

## Documentación adicional

- [`CLAUDE.md`](./CLAUDE.md) — guía de arquitectura y convenciones para trabajar en el repo
- [`docs/superpowers/specs/2026-09-23-trayectoria-estudiantil-ui-design.md`](./docs/superpowers/specs/2026-09-23-trayectoria-estudiantil-ui-design.md) — spec de diseño
- [`docs/superpowers/plans/2026-09-23-trayectoria-estudiantil-ui.md`](./docs/superpowers/plans/2026-09-23-trayectoria-estudiantil-ui.md) — plan de implementación
