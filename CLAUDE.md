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

**Current scope is visual/presentational only**: no real authentication,
database, or server-side validation. `POST /login` and `POST
/solicitudes/nueva` always redirect without validating anything. All data
shown on screen is mock data hardcoded as PHP arrays inside controllers.

Design spec and implementation plan (superpowers-style docs, useful for
understanding *why* the UI is structured this way):
`docs/superpowers/specs/2026-09-23-trayectoria-estudiantil-ui-design.md`
`docs/superpowers/plans/2026-09-23-trayectoria-estudiantil-ui.md`

## Commands

```bash
# PHP dependencies / tests
composer install
php artisan test                       # full suite
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
to build assets just to run the suite.

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

**Navigation** (`app/Support/Navigation.php`) is a small static-method
helper, not a database-backed menu: `Navigation::coordinator($active)`,
`::coordinatorWithTutorExtras($active)`, and `::studentPortal($active)`
each return the exact nav-groups array shape `x-sidebar` expects
(`[['label' => ?string, 'items' => [['key','icon','label','href','active'], ...]], ...]`),
with the item matching `$active` marked `active: true`. Different screens
use different Navigation methods because the mockup shows different
sidebar content depending on the logged-in user's role for that screen
(coordinator vs. tutor vs. student) — this is deliberate, not
inconsistency, and is documented per-screen in the implementation plan
linked above.

**Routing pattern**: one route → one single-purpose controller method →
one Blade view. Controllers hold mock data as `private const` arrays or
inline arrays passed to the view; there is no model/migration layer yet.
`GET /estudiantes/{id}` intentionally ignores `$id` and always renders the
same illustrative profile — a deliberate scope decision, not a bug.

## Commit convention

Commits use Conventional Commits with a required scope:
`type(scope): descripción breve en español, en minúsculas`.

Types in use so far: `feat` (new screens/components), `fix` (bug fixes),
`test` (test-only additions), `docs` (spec/plan documents, `CLAUDE.md`),
`chore` (tooling/config with no source-code behavior change, e.g.
`.gitignore`). Common scopes: `add` (new screen/feature),
`setup` (tooling/build config), `login`, `plan`, `responsive`, `nav` — pick
whatever scope names the area actually touched; these are examples, not a
closed list. Body paragraphs (when needed) are also in Spanish.
