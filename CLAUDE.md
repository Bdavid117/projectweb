# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Laravel 10 (PHP 8.1) app: "Trayectoria Estudiantil" — a student-pathway
tracking system for an academic coordination area. The UI (6 screens: login,
coordinator panel, student roster, student profile, request form, program
indicators) was built from a Pencil design mockup at
`mokup/trayectoria-estudiantil.pen` (source of truth for the design — see
`mokup/design-tokens.css` for the token values and `mokup/exports/*.png` for
reference screenshots; both are gitignored, not part of the repo).

**Current scope**: PostgreSQL (`trayectoria_estudiantil`; tests run against
`trayectoria_estudiantil_test`) with the 26 domain tables from the analysis
document (`mokup/Documento-Analisis-y-Diseno.pdf` §5.3), one Eloquent model per
table, and seeders (`php artisan migrate:fresh --seed`; demo users
`coordinacion@`, `tutor@`, `profesor@`, `estudiante@unal.edu.co`, password
`password`). Real session login with 4 roles (`App\Enums\Role`, route middleware
`role:...`). All screens read from PostgreSQL: students (list, profile), the panel and
indicators (`App\Support\ProgramMetrics`), and the requests and tutorships modules
have full CRUD with Spanish validation, role/ownership policies and an audit trail.
Still out of scope: `/mi-*`, `/experiencias`, `/admin/*`, automatic alerts, Excel
import/export.

## Commands

```bash
# One-time setup (PostgreSQL must be running; php artisan test fails without the _test DB)
createdb trayectoria_estudiantil && createdb trayectoria_estudiantil_test
cp .env.example .env && php artisan key:generate   # then set DB_USERNAME in .env
php artisan migrate --seed

# PHP dependencies / tests
composer install
php artisan migrate:fresh --seed      # recreate the DB with demo data
php artisan test                       # full suite (RefreshDatabase on the _test DB)
php artisan test --filter=LoginTest    # single test class
php artisan test tests/Feature/StudentTest.php::test_student_show_renders_profile_regardless_of_id

# Frontend build (Tailwind v4 via @tailwindcss/vite)
npm install
npm run dev                            # Vite dev server, needed for @vite() to resolve without a build
npm run build                          # production build -> public/build

# Local server
php artisan serve
```

Feature tests call `$this->withoutVite()` in `tests/TestCase.php::setUp()`,
so `php artisan test` works even without a prior `npm run build` — no need
to build assets just to run the suite. Every test that touches a DB-backed
controller uses `RefreshDatabase`; most seed with `$this->seed(DatabaseSeeder::class)` and sign in
with `$this->signInSeeded(Role::X)`. `actingAsRole()` builds an unsaved user (fine for empty-database
checks). Attachments are tested with `Storage::fake()`, time-dependent code with `Carbon::setTestNow()`.
`TestCase::setUpTraits()` aborts if the test database name doesn't end in `_test`.

## Architecture

**Design tokens**: all colors/spacing/radii/typography live as CSS custom
properties in a `:root` block at the top of `resources/css/app.css` (copied
from `mokup/design-tokens.css`). Every class in Blade views references them
via arbitrary values — `bg-[var(--color-primary-600)]`,
`text-[var(--text-secondary)]`, `rounded-[var(--radius-md)]` — never
hardcoded hex. Icons come from `mallardduck/blade-lucide-icons`
(`<x-lucide-{name} />`, or `<x-dynamic-component :component="'lucide-'.$icon" />`
when the icon name is a runtime variable), colored via `text-[var(--...)]`
since Lucide icons use `currentColor`.

**One hard rule that isn't obvious from reading a single file**: any
Tailwind class that maps a variant/tone/status to styling (`x-button`'s
`variant`, `x-status-badge`'s `tone`, `x-indicator-card`'s `value-class`)
must be a complete literal string selected from a PHP array/match — never
built by concatenating a variable into the middle of a class name (e.g.
`"bg-[var(--color-" . $tone . ")]"`). Tailwind's build only picks up classes
that appear as literal strings in source files; a concatenated one compiles
to nothing, silently, with no error.

**Inline `style=` is banned everywhere except one case**: per-row
data-driven values Tailwind's static scanner can't express as a class —
chart bar heights/widths, progress-bar widths, and the student profile's
SVG progress-ring `stroke-dasharray`/`stroke-dashoffset` (those are SVG
presentation attributes set via Blade interpolation, not a CSS `style=`
string). Everything else must be a Tailwind class.

**Shared components** (`resources/views/components/`): `x-button`
(variant: primary/secondary/subtle), `x-nav-item`, `x-indicator-card`,
`x-status-badge`, `x-form-field` (type: text/email/password/select/textarea)
mirror the Pencil design's reusable components 1:1.

**Layouts** are anonymous Blade components, not `@extends`/`@yield`:
`<x-layouts.app :user :nav-groups title>` wraps `x-topbar` + `x-sidebar` +
a content slot, used by every screen except login; `<x-layouts.guest>` is
the two-panel login shell. The 260px sidebar is `hidden` below the `sm`
breakpoint and replaced by a (currently non-functional — no JS in scope)
hamburger icon in the topbar, since three screens have explicit 360px
mockups that assume no fixed sidebar.

**Navigation** (`app/Support/Navigation.php`): `Navigation::forRole($role, $active)` returns
the nav-groups array `x-sidebar` expects, already filtered by role (Indicadores and the
Administración block only for Coordinación; the tutor extras only for Tutor; the student keeps
the portal menu from the mockup). Never link to a route the role can't open.

**Data flow**: one route → one thin controller method → one Blade view. Controllers query
Eloquent and build the same arrays the views always consumed. Business logic lives in
`App\Services\RequestWorkflow` (file / start review / decide, with status history) and
`App\Support\{ProgramMetrics,StudentProfile,Labels,Input}`; authorization in
`App\Policies\{CommitteeRequest,Tutorship}Policy` (ownership and the 24 h edit window) on top of
the `role:` route middleware; limits and thresholds in `config/program.php`. Read search/filter
inputs with `Support\Input::text()/id()` (not `$request->string()`, which throws on `q[]=x`).

**Routes by role** (`routes/web.php`): `/panel` (staff; students are redirected to `/solicitudes`),
`/estudiantes` and `/estudiantes/{id}` (profesor, tutor, coordinación), `/solicitudes` (all; students
see only their own), `/solicitudes/nueva` and `POST /solicitudes` (estudiante, coordinación),
`/solicitudes/{id}`, `/revision` and `/decision` (the last two only coordinación),
`/mis-asesorias` (tutor), `/estudiantes/{id}/tutorias/crear` and `/tutorias/{id}/editar` (tutor,
coordinación; a tutor only for students assigned to them in the current term; edit/delete only by
the author within 24 h). Tutorship notes on the profile and tutorship activity/alerts on the panel are
visible only to tutor (own students) and coordinación — never to profesor; the student menu has no
Administración block, `/indicadores` (coordinación).

**Pencil canvas** (`mokup/trayectoria-estudiantil.pen`) is organized in lanes per role
(Fundamentos, Acceso, Coordinación C1–C5, Tutor T1–T3, Estudiante E1, Móvil). Put new screens at the
end of their role's lane, 1600 px after the previous one, and name the frame with the lane code and
the route.

## Commit convention

Commits use Conventional Commits with a required scope:
`type(scope): descripción breve en español, en minúsculas`.

Types in use so far: `feat` (new screens/components), `fix` (bug fixes),
`test` (test-only additions), `docs` (`CLAUDE.md`, README),
`chore` (tooling/config with no source-code behavior change, e.g.
`.gitignore`). Common scopes: `add` (new screen/feature),
`setup` (tooling/build config), `login`, `plan`, `responsive`, `nav` — pick
whatever scope names the area actually touched; these are examples, not a
closed list. Body paragraphs (when needed) are also in Spanish.
