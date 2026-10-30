# Trayectoria Estudiantil

Sistema de seguimiento de trayectorias estudiantiles para un área de coordinación académica. Construido con **Laravel 10** (PHP 8.1) y **Tailwind CSS v4**, a partir de un mockup de diseño en Pencil.

> **Estado actual del proyecto (30/10/2026):** todas las pantallas leen de PostgreSQL (26 tablas, modelos y seeders) con login real y 3 roles (estudiante, profesor y coordinación; el profesor también ejerce de tutor desde el grupo «Tutoría» de su menú). Solicitudes (radicar con soportes, revisión y decisión del comité), tutorías (ventana de edición de 24 horas) y experiencias formativas (el estudiante registra, Coordinación valida o rechaza) tienen CRUD con validación en español y permisos por rol. El portal del estudiante (`/perfil`, `/historia-academica`, `/trabajo-de-grado`, `/practica`) permite editar perfil, trabajo de grado y práctica con bitácora de cambios, y el estudiante decide quién ve su información en «Mi tutor». La administración (`/admin/usuarios|cargas|catalogos|bitacora`) sigue en **solo lectura**. El navbar es funcional (notificaciones, menú de usuario y menú lateral en móvil), hay logo propio, login rediseñado e íconos animados. Pruebas: 258 en verde. **Pendiente:** gestión de usuarios, catálogos y bitácora de auditoría (#37), carga masiva desde Excel (#27), perfil psicosocial restringido (#25), recuperación de contraseña (#46), alertas automáticas (#47), CI (#28), pruebas E2E (#29) y actualización de Laravel por avisos de seguridad (#45). El detalle está en las [issues](https://github.com/Bdavid117/projectweb/issues) y en el tablero del proyecto.

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

| Pantalla | Ruta | Roles |
|---|---|---|
| Login | `GET/POST /login` | todos |
| Panel del programa | `GET /panel` | profesor, coordinación (el estudiante tiene su propio panel) |
| Listado de estudiantes | `GET /estudiantes` | coordinación (todos); profesor (solo quienes aceptaron su solicitud de tutoría) |
| Ficha del estudiante | `GET /estudiantes/{id}` | ídem |
| Solicitudes (listado) | `GET /solicitudes` | todos (el estudiante ve las suyas y el profesor las de sus tutorados) |
| Nueva solicitud | `GET /solicitudes/nueva`, `POST /solicitudes` | estudiante, coordinación |
| Detalle y decisión | `GET /solicitudes/{id}`, `POST …/revision`, `POST …/decision` | ver: todos; decidir: coordinación |
| Mis asesorías | `GET /mis-asesorias` | profesor (grupo «Tutoría» del menú) |
| Solicitar tutoría | `GET/POST /tutoria/solicitudes`, `DELETE /tutoria/solicitudes/{id}` | profesor |
| Mi tutor | `GET /mi-tutor`, `POST …/solicitudes/{id}/aceptar`, `POST …/rechazar`, `DELETE …/vinculo` | estudiante |
| Registrar / editar tutoría | `GET /estudiantes/{id}/tutorias/crear`, `GET /tutorias/{id}/editar` | profesor con vínculo vigente, coordinación |
| Indicadores del programa | `GET /indicadores` | coordinación |

**Quién ve la información de un estudiante.** Coordinación ve a todos y cada estudiante ve la suya. Un profesor solo ve a quienes aceptaron su solicitud de tutoría: la envía desde «Solicitar tutoría», el estudiante la acepta o rechaza en «Mi tutor» y puede dejar de compartir cuando quiera. Sin vínculo, el profesor no abre la ficha, ni las solicitudes, experiencias o tutorías del estudiante, ni puede registrar tutorías; al revocarlo pierde el acceso de inmediato, también a las tutorías que él mismo escribió. El vínculo es del periodo vigente (al cambiar de periodo hay que solicitarlo de nuevo) y cada paso queda en `audit_logs`.

## Stack técnico

- **Backend:** Laravel 10, PHP 8.1
- **Frontend:** Blade + Tailwind CSS v4 (vía `@tailwindcss/vite`), iconos con [`mallardduck/blade-lucide-icons`](https://github.com/mallardduck/blade-lucide-icons)
- **Base de datos:** PostgreSQL (`pdo_pgsql`)
- **Build tool:** Vite
- **Tests:** PHPUnit (Feature + Unit)

## Requisitos

- PHP ^8.1 con Composer y la extensión `pdo_pgsql`
- PostgreSQL en ejecución (por ejemplo `brew install postgresql`)
- `upload_max_filesize` ≥ 5M y `post_max_size` ≥ 26M en `php.ini` (los soportes de solicitudes admiten hasta 5 archivos de 5 MB; con el valor por defecto de 2M el servidor rechaza los archivos grandes con un 413)
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

Usuarios demo (contraseña `password`): `coordinacion@unal.edu.co`, `profesor@unal.edu.co` (sin tutorados: sirve para probar «Solicitar tutoría»), `profesor2@unal.edu.co` (Carlos Pineda, con 5 tutorados) y `estudiante@unal.edu.co`. El antiguo `tutor@unal.edu.co` pasó a ser `profesor2@`: tras actualizar, ejecute `php artisan migrate:fresh --seed`.

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
- **Navegación** (`app/Support/Navigation.php`): helper estático que arma los grupos de navegación según el rol de la pantalla (coordinación, profesor con el grupo «Tutoría», estudiante), con el rol tomado de la sesión (`Navigation::forRole`).
- **Ruteo:** patrón una ruta → un método de controlador de propósito único → una vista Blade. Los controladores consultan Eloquent y arman los arrays que consumen las vistas; la lógica de solicitudes vive en `App\Services\RequestWorkflow` y la autorización en `App\Policies`. Las rutas están protegidas con los middleware `auth` y `role:`.

Una regla que no es obvia leyendo un solo archivo: cualquier clase de Tailwind que mapea una variante/tono/estado a estilos (`variant` de `x-button`, `tone` de `x-status-badge`, `value-class` de `x-indicator-card`) debe ser un string literal completo seleccionado desde un array/match de PHP — nunca construido concatenando una variable dentro del nombre de la clase, porque el build de Tailwind solo detecta clases que aparecen como strings literales en el código fuente.

El `style=` inline está prohibido salvo para valores por fila que el scanner estático de Tailwind no puede expresar como clase (alturas de barras de gráficos, anchos de progress bars, `stroke-dasharray`/`stroke-dashoffset` del anillo de progreso SVG del perfil de estudiante).

## Convención de commits

Se usa [Conventional Commits](https://www.conventionalcommits.org/) con scope obligatorio:

```
type(scope): descripción breve en español, en minúsculas
```

**Tipos:** `feat` (nuevas pantallas/componentes), `fix` (corrección de bugs), `test` (tests), `docs` (`CLAUDE.md`, README), `chore` (tooling/config sin cambio de comportamiento).

**Scopes comunes:** `add`, `setup`, `login`, `plan`, `responsive`, `nav` — son ejemplos, no una lista cerrada; se elige el scope que nombre el área realmente afectada.

## Documentación adicional

- [`CLAUDE.md`](./CLAUDE.md) — guía de arquitectura y convenciones para trabajar en el repo
