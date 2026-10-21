# Trayectoria Estudiantil UI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert the Pencil mockup (`mokup/trayectoria-estudiantil.pen`) into 6 functional, responsive Blade views (Login, Panel de Coordinación, Listado de estudiantes, Ficha del estudiante, Formulario de solicitud, Indicadores) wired to real routes, styled with Tailwind CSS v4 and the design's token system — visual/presentational only, no real auth or database.

**Architecture:** Tailwind v4 via Vite reads design tokens copied into `resources/css/app.css` as CSS custom properties. Shared Blade components (button, nav-item, indicator-card, status-badge, form-field) mirror the Pencil design system's reusable components. Two layout components (`x-layouts.guest`, `x-layouts.app`) wrap screens; `x-layouts.app` composes a topbar and a role-aware sidebar built from a small `App\Support\Navigation` helper. Each screen has one route, one single-action-per-method controller returning mock PHP array data, and one Blade view.

**Tech Stack:** Laravel 10 (PHP 8.1), Blade components, Tailwind CSS v4 (`@tailwindcss/vite`), `mallardduck/blade-lucide-icons` for the 25 Lucide icons used in the design, PHPUnit 10 Feature tests.

**Spec:** `docs/superpowers/specs/2026-09-23-trayectoria-estudiantil-ui-design.md`

## Global Constraints

- Tailwind CSS v4 via `@tailwindcss/vite`; `resources/css/app.css` starts with `@import "tailwindcss";`.
- All design tokens live as CSS custom properties in a `:root` block in `resources/css/app.css`, copied verbatim from `mokup/design-tokens.css`. Reference them via arbitrary values: `bg-[var(--color-primary-600)]`, never hardcoded hex.
- No inline `style=` attributes, **except** for the one narrow case Tailwind's static class scanner cannot support: per-row computed chart bar heights, progress-bar widths, and the donut-ring `stroke-dasharray`/`stroke-dashoffset` — these are data-driven pixel/percentage values with no finite set of literal Tailwind classes to scan. Every other property (color, spacing, radius, typography) must be a literal Tailwind class string, never built by string-concatenating a variable into the middle of a class name (Tailwind's scanner only finds complete literal class strings in source files).
- Icons: use `mallardduck/blade-lucide-icons` components (`<x-lucide-{name} />` or `<x-dynamic-component :component="'lucide-'.$icon" />`), matching the 25 icon names used in the design. Colors applied via `text-[var(--...)]` (icons use `currentColor`).
- No authentication, sessions, database, or server-side validation. `POST /login` always redirects to `/panel`.
- Mock data lives as PHP arrays inside controllers.
- Follow existing project conventions: PHPUnit tests under `tests/Feature`, `Tests\TestCase` base class, no `RefreshDatabase` (no DB in scope).

## Review Focus

- `GET /estudiantes/{id}` with an arbitrary/unknown id (e.g. `999999`) must still return 200 with the mock profile, not 404 or 500 — the spec explicitly scopes student detail to a single illustrative profile regardless of id.
- `POST /login` with an empty request body must still redirect to `/panel` (no validation means no rejection path).
- Every component that maps a variant/tone/status to Tailwind classes (`x-button`, `x-status-badge`, `x-indicator-card`) must return one complete literal class string per branch (array/match lookup), never a concatenated partial class name — otherwise Tailwind's build silently drops the style with no error.
- The active sidebar item must match the current screen on every internal route, and only one item should carry the active classes at a time.
- Content at 360px viewport width (Listado, Ficha, Formulario) must not overflow horizontally or clip text, per the mobile mockups in the design.

---

## File Structure

```
app/Http/Controllers/LoginController.php
app/Http/Controllers/PanelController.php
app/Http/Controllers/StudentController.php
app/Http/Controllers/RequestController.php
app/Http/Controllers/IndicatorController.php
app/Support/Navigation.php

resources/css/app.css                          (modified)
vite.config.js                                  (modified)

resources/views/components/button.blade.php
resources/views/components/nav-item.blade.php
resources/views/components/indicator-card.blade.php
resources/views/components/status-badge.blade.php
resources/views/components/form-field.blade.php
resources/views/components/topbar.blade.php
resources/views/components/sidebar.blade.php
resources/views/components/layouts/app.blade.php
resources/views/components/layouts/guest.blade.php

resources/views/auth/login.blade.php
resources/views/panel/index.blade.php
resources/views/students/index.blade.php
resources/views/students/show.blade.php
resources/views/requests/create.blade.php
resources/views/indicators/index.blade.php

routes/web.php                                   (modified)

tests/Unit/DesignTokensTest.php
tests/Feature/SharedComponentsTest.php
tests/Feature/LayoutTest.php
tests/Feature/LoginTest.php
tests/Feature/PanelTest.php
tests/Feature/StudentTest.php
tests/Feature/RequestTest.php
tests/Feature/IndicatorTest.php
tests/Feature/NavigationTest.php
```

---

### Task 1: Tailwind v4, design tokens, and icon package setup

**Files:**
- Modify: `package.json`, `vite.config.js`
- Modify: `resources/css/app.css`
- Modify: `composer.json` (via `composer require`)
- Test: `tests/Unit/DesignTokensTest.php`

**Interfaces:**
- Produces: `resources/css/app.css` importable via `@vite(['resources/css/app.css'])`; every later task's Blade files rely on the CSS variables it defines (`--color-*`, `--text-*`, `--surface-*`, `--border-*`, `--radius-*`, `--space-*`, `--control-height`, `--sidebar-width`, `--topbar-height`) and on the `.font-sans` utility class.
- Produces: `<x-lucide-{name} />` and `<x-dynamic-component :component="'lucide-'.$icon" />` available in all views after `composer require mallardduck/blade-lucide-icons`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Unit/DesignTokensTest.php

namespace Tests\Unit;

use Tests\TestCase;

class DesignTokensTest extends TestCase
{
    public function test_app_css_imports_tailwind_and_defines_core_tokens(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('@import "tailwindcss";', $css);
        $this->assertStringContainsString('--color-primary-600: #1E5290;', $css);
        $this->assertStringContainsString('--surface-page:', $css);
        $this->assertStringContainsString('--control-height:    40px;', $css);
        $this->assertStringContainsString('.font-sans', $css);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/DesignTokensTest.php`
Expected: FAIL — `resources/css/app.css` is currently empty.

- [ ] **Step 3: Install Tailwind v4 and the icon package**

Run:
```bash
npm install -D tailwindcss @tailwindcss/vite
composer require mallardduck/blade-lucide-icons
```

- [ ] **Step 4: Wire the Vite plugin**

```js
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

- [ ] **Step 5: Write `resources/css/app.css`**

```css
@import "tailwindcss";

:root {
  /* ---------- Marca · Azul institucional ---------- */
  --color-primary-50:  #EEF4FB;
  --color-primary-100: #D6E4F5;
  --color-primary-200: #ADC9EB;
  --color-primary-300: #7FA9DD;
  --color-primary-400: #4E85C9;
  --color-primary-500: #2B66AE;
  --color-primary-600: #1E5290;
  --color-primary-700: #17406F;
  --color-primary-800: #112F52;
  --color-primary-900: #0B1F36;

  /* ---------- Acento · Verde académico ---------- */
  --color-accent-50:  #ECF7F1;
  --color-accent-100: #D2EDE0;
  --color-accent-300: #7FCFA9;
  --color-accent-500: #2E9E6B;
  --color-accent-600: #16704A;
  --color-accent-800: #0D4A31;

  /* ---------- Neutrales ---------- */
  --color-white: #FFFFFF;
  --color-neutral-50:  #F7F8FA;
  --color-neutral-100: #EEF0F4;
  --color-neutral-200: #DFE3E9;
  --color-neutral-300: #C6CCD6;
  --color-neutral-400: #99A1AE;
  --color-neutral-500: #6B7280;
  --color-neutral-600: #4B5563;
  --color-neutral-700: #374151;
  --color-neutral-800: #1F2937;
  --color-neutral-900: #111827;

  /* ---------- Semánticos ---------- */
  --color-success:    #16704A;
  --color-success-bg: #ECF7F1;
  --color-warning:    #92610C;
  --color-warning-bg: #FDF6E7;
  --color-danger:     #B42318;
  --color-danger-bg:  #FDF0EF;
  --color-info:       #1E5290;
  --color-info-bg:    #EEF4FB;

  --color-restricted:    #6D28D9;
  --color-restricted-bg: #F4F0FE;

  /* ---------- Roles de superficie ---------- */
  --surface-page:    var(--color-neutral-50);
  --surface-card:    var(--color-white);
  --surface-sunken:  var(--color-neutral-100);
  --surface-inverse: var(--color-primary-800);
  --border-subtle:   var(--color-neutral-200);
  --border-default:  var(--color-neutral-300);
  --border-strong:   var(--color-neutral-400);
  --text-primary:    var(--color-neutral-900);
  --text-secondary:  var(--color-neutral-600);
  --text-muted:      var(--color-neutral-500);
  --text-on-brand:   var(--color-white);
  --focus-ring:      var(--color-primary-500);

  /* ---------- Tipografía ---------- */
  --text-xs:   0.75rem;
  --text-sm:   0.875rem;
  --text-base: 1rem;
  --text-lg:   1.125rem;
  --text-xl:   1.25rem;
  --text-2xl:  1.5rem;
  --text-3xl:  1.875rem;

  --weight-regular:  400;
  --weight-medium:   500;
  --weight-semibold: 600;
  --weight-bold:     700;

  --leading-tight:  1.25;
  --leading-normal: 1.5;
  --leading-loose:  1.7;

  /* ---------- Espaciado · base 4px ---------- */
  --space-1:  0.25rem;
  --space-2:  0.5rem;
  --space-3:  0.75rem;
  --space-4:  1rem;
  --space-5:  1.25rem;
  --space-6:  1.5rem;
  --space-8:  2rem;
  --space-10: 2.5rem;
  --space-12: 3rem;
  --space-16: 4rem;

  /* ---------- Radios ---------- */
  --radius-sm:   4px;
  --radius-md:   6px;
  --radius-lg:   10px;
  --radius-xl:   14px;
  --radius-full: 9999px;

  /* ---------- Sombras ---------- */
  --shadow-sm: 0 1px 2px rgba(17, 24, 39, 0.06);
  --shadow-md: 0 2px 6px rgba(17, 24, 39, 0.08);
  --shadow-lg: 0 8px 24px rgba(17, 24, 39, 0.12);

  /* ---------- Layout ---------- */
  --sidebar-width:           260px;
  --sidebar-width-collapsed: 72px;
  --topbar-height:           60px;
  --content-max-width:       1280px;

  /* ---------- Puntos de quiebre ---------- */
  --bp-mobile:  360px;
  --bp-tablet:  768px;
  --bp-desktop: 1280px;

  /* ---------- Componentes ---------- */
  --control-height:    40px;
  --control-height-sm: 32px;
  --touch-target-min:  44px;
}

@layer base {
  html, body {
    height: 100%;
  }

  .font-sans {
    font-family: "Inter", -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  }
}

:where(a, button, input, select, textarea, [tabindex]):focus-visible {
  outline: 2px solid var(--focus-ring);
  outline-offset: 2px;
  border-radius: var(--radius-sm);
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Unit/DesignTokensTest.php`
Expected: PASS

- [ ] **Step 7: Verify the Vite build compiles and the icon package resolves**

Run:
```bash
npm run build
php artisan tinker --execute="echo view()->exists('components.layouts.app') ? 'n/a' : 'ok';"
```
Expected: `npm run build` exits 0 and prints a `public/build/assets/*.css` file. The tinker line is just a smoke check that Artisan boots; it will print `ok` since that view doesn't exist yet — that's fine at this stage.

- [ ] **Step 8: Commit**

```bash
git add package.json package-lock.json composer.json composer.lock vite.config.js resources/css/app.css tests/Unit/DesignTokensTest.php
git commit -m "feat: install Tailwind v4, design tokens, and Lucide icons"
```

---

### Task 2: Navigation helper and shared UI components

**Files:**
- Create: `app/Support/Navigation.php`
- Create: `resources/views/components/button.blade.php`
- Create: `resources/views/components/nav-item.blade.php`
- Create: `resources/views/components/indicator-card.blade.php`
- Create: `resources/views/components/status-badge.blade.php`
- Create: `resources/views/components/form-field.blade.php`
- Test: `tests/Feature/SharedComponentsTest.php`

**Interfaces:**
- Consumes: CSS tokens and `.font-sans` from Task 1.
- Produces: `App\Support\Navigation::coordinator(string $active): array`, `::coordinatorWithTutorExtras(string $active): array`, `::studentPortal(string $active): array` — each returns `[['label' => ?string, 'items' => [['key','icon','label','href','active'], ...]], ...]`. Used by Task 3's sidebar and every controller from Task 4 onward.
- Produces: `<x-button variant="primary|secondary|subtle" :icon="?string" :href="?string" type="button|submit">Label</x-button>`.
- Produces: `<x-nav-item :icon="string" :label="string" :href="string" :active="bool" />`.
- Produces: `<x-indicator-card :label="string" :value="string" :detail="string" :value-class="?string" />` (default `value-class` is `text-[var(--text-primary)]`).
- Produces: `<x-status-badge :text="string" :tone="success|warning|danger|info|primary|accent|restricted" />`.
- Produces: `<x-form-field :label :name :type="text|email|password|select|textarea" :placeholder :icon :help :value :rows>` (slot holds `<option>`s when `type="select"`).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/SharedComponentsTest.php

namespace Tests\Feature;

use App\Support\Navigation;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SharedComponentsTest extends TestCase
{
    public function test_button_renders_variant_classes_and_label(): void
    {
        $html = Blade::render('<x-button variant="primary">Ingresar</x-button>');

        $this->assertStringContainsString('bg-[var(--color-primary-600)]', $html);
        $this->assertStringContainsString('Ingresar', $html);
    }

    public function test_button_with_icon_and_href_renders_anchor(): void
    {
        $html = Blade::render('<x-button variant="subtle" icon="download" href="/x">Exportar</x-button>');

        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('href="/x"', $html);
        $this->assertStringContainsString('Exportar', $html);
    }

    public function test_status_badge_maps_tone_to_literal_classes(): void
    {
        $html = Blade::render('<x-status-badge text="En riesgo" tone="danger" />');

        $this->assertStringContainsString('bg-[var(--color-danger-bg)]', $html);
        $this->assertStringContainsString('text-[var(--color-danger)]', $html);
        $this->assertStringContainsString('En riesgo', $html);
    }

    public function test_indicator_card_renders_label_value_detail(): void
    {
        $html = Blade::render(
            '<x-indicator-card label="ALERTAS ABIERTAS" value="7" detail="Requieren atención temprana" value-class="text-[var(--color-danger)]" />'
        );

        $this->assertStringContainsString('ALERTAS ABIERTAS', $html);
        $this->assertStringContainsString('>7<', $html);
        $this->assertStringContainsString('text-[var(--color-danger)]', $html);
    }

    public function test_form_field_select_renders_slot_options(): void
    {
        $html = Blade::render(
            '<x-form-field label="Tipo" name="tipo" type="select"><option>Cancelación</option></x-form-field>'
        );

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('Cancelación', $html);
    }

    public function test_nav_item_marks_active_state(): void
    {
        $html = Blade::render('<x-nav-item icon="users" label="Estudiantes" href="/estudiantes" :active="true" />');

        $this->assertStringContainsString('bg-[var(--color-primary-50)]', $html);
    }

    public function test_navigation_coordinator_marks_current_item_active(): void
    {
        $groups = Navigation::coordinator('estudiantes');

        $general = collect($groups[0]['items']);
        $this->assertTrue($general->firstWhere('key', 'estudiantes')['active']);
        $this->assertFalse($general->firstWhere('key', 'panel')['active']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/SharedComponentsTest.php`
Expected: FAIL — none of the components or the `Navigation` class exist yet.

- [ ] **Step 3: Write `app/Support/Navigation.php`**

```php
<?php

namespace App\Support;

class Navigation
{
    public static function coordinator(string $active): array
    {
        return [
            ['label' => 'GENERAL', 'items' => self::mark(self::coordinatorGeneralItems(), $active)],
            ['label' => 'ADMINISTRACIÓN', 'items' => self::mark(self::adminItems(), $active)],
        ];
    }

    public static function coordinatorWithTutorExtras(string $active): array
    {
        $groups = self::coordinator($active);
        $groups[] = [
            'label' => null,
            'items' => self::mark([
                ['key' => 'registrar-tutoria', 'icon' => 'message-square', 'label' => 'Registrar tutoría', 'href' => '#'],
                ['key' => 'mis-asesorias', 'icon' => 'clipboard-list', 'label' => 'Mis asesorías', 'href' => '#'],
            ], $active),
        ];

        return $groups;
    }

    public static function studentPortal(string $active): array
    {
        $items = [
            ['key' => 'panel', 'icon' => 'layout-dashboard', 'label' => 'Panel', 'href' => '/panel'],
            ['key' => 'perfil', 'icon' => 'user', 'label' => 'Mi perfil', 'href' => '#'],
            ['key' => 'solicitudes', 'icon' => 'file-text', 'label' => 'Mis solicitudes', 'href' => '/solicitudes/nueva'],
            ['key' => 'experiencias', 'icon' => 'sparkles', 'label' => 'Mis experiencias', 'href' => '#'],
            ['key' => 'trabajo-grado', 'icon' => 'book-open', 'label' => 'Mi trabajo de grado', 'href' => '#'],
            ['key' => 'historia', 'icon' => 'trending-up', 'label' => 'Mi historia académica', 'href' => '#'],
        ];

        return [
            ['label' => 'GENERAL', 'items' => self::mark($items, $active)],
            ['label' => 'ADMINISTRACIÓN', 'items' => self::mark(self::adminItems(), $active)],
            ['label' => null, 'items' => self::mark([
                ['key' => 'practica', 'icon' => 'briefcase', 'label' => 'Mi práctica', 'href' => '#'],
            ], $active)],
        ];
    }

    private static function coordinatorGeneralItems(): array
    {
        return [
            ['key' => 'panel', 'icon' => 'layout-dashboard', 'label' => 'Panel', 'href' => '/panel'],
            ['key' => 'estudiantes', 'icon' => 'users', 'label' => 'Estudiantes', 'href' => '/estudiantes'],
            ['key' => 'solicitudes', 'icon' => 'file-text', 'label' => 'Solicitudes', 'href' => '/solicitudes/nueva'],
            ['key' => 'experiencias', 'icon' => 'sparkles', 'label' => 'Experiencias', 'href' => '#'],
            ['key' => 'tutorias', 'icon' => 'message-square', 'label' => 'Tutorías', 'href' => '#'],
            ['key' => 'indicadores', 'icon' => 'bar-chart-3', 'label' => 'Indicadores', 'href' => '/indicadores'],
        ];
    }

    private static function adminItems(): array
    {
        return [
            ['key' => 'usuarios', 'icon' => 'shield', 'label' => 'Usuarios y roles', 'href' => '#'],
            ['key' => 'cargas', 'icon' => 'upload', 'label' => 'Cargas desde Excel', 'href' => '#'],
            ['key' => 'catalogos', 'icon' => 'list', 'label' => 'Catálogos', 'href' => '#'],
            ['key' => 'bitacora', 'icon' => 'history', 'label' => 'Bitácora', 'href' => '#'],
        ];
    }

    private static function mark(array $items, string $active): array
    {
        return array_map(
            static fn (array $item) => $item + ['active' => $item['key'] === $active],
            $items
        );
    }
}
```

- [ ] **Step 4: Write `resources/views/components/button.blade.php`**

```blade
@props(['variant' => 'primary', 'icon' => null, 'href' => null, 'type' => 'button'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-[var(--radius-md)] text-sm font-semibold transition-colors';
    $variants = [
        'primary' => 'bg-[var(--color-primary-600)] text-[var(--text-on-brand)] px-[18px] py-[10px] hover:bg-[var(--color-primary-700)]',
        'secondary' => 'bg-[var(--surface-card)] text-[var(--text-secondary)] border border-[var(--border-default)] px-[18px] py-[10px] hover:bg-[var(--surface-sunken)]',
        'subtle' => 'bg-transparent text-[var(--color-primary-600)] px-3 py-2 gap-[6px] hover:bg-[var(--color-primary-50)]',
    ];
    $classes = $base . ' ' . $variants[$variant];
    $tag = $href ? 'a' : 'button';
@endphp
<{{ $tag }}
    @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="h-4 w-4" />
    @endif
    {{ $slot }}
</{{ $tag }}>
```

- [ ] **Step 5: Write `resources/views/components/nav-item.blade.php`**

```blade
@props(['icon', 'label', 'href' => '#', 'active' => false])

<a
    href="{{ $href }}"
    class="flex w-full items-center gap-[10px] rounded-[var(--radius-md)] px-3 py-[10px] text-sm font-medium {{ $active ? 'bg-[var(--color-primary-50)] text-[var(--color-primary-700)]' : 'text-[var(--color-neutral-600)] hover:bg-[var(--surface-sunken)]' }}"
>
    <x-dynamic-component
        :component="'lucide-' . $icon"
        class="h-[18px] w-[18px] {{ $active ? 'text-[var(--color-primary-600)]' : 'text-[var(--color-neutral-500)]' }}"
    />
    <span>{{ $label }}</span>
</a>
```

- [ ] **Step 6: Write `resources/views/components/indicator-card.blade.php`**

```blade
@props(['label', 'value', 'detail', 'valueClass' => 'text-[var(--text-primary)]'])

<div class="flex w-full flex-col gap-[6px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[18px]">
    <p class="text-[11px] font-semibold tracking-[0.6px] text-[var(--text-muted)]">{{ $label }}</p>
    <p class="text-[30px] font-bold {{ $valueClass }}">{{ $value }}</p>
    <p class="text-xs text-[var(--text-secondary)]">{{ $detail }}</p>
</div>
```

- [ ] **Step 7: Write `resources/views/components/status-badge.blade.php`**

```blade
@props(['text', 'tone' => 'success'])

@php
    $tones = [
        'success' => 'bg-[var(--color-success-bg)] text-[var(--color-success)]',
        'warning' => 'bg-[var(--color-warning-bg)] text-[var(--color-warning)]',
        'danger' => 'bg-[var(--color-danger-bg)] text-[var(--color-danger)]',
        'info' => 'bg-[var(--color-info-bg)] text-[var(--color-info)]',
        'primary' => 'bg-[var(--color-primary-50)] text-[var(--color-primary-600)]',
        'accent' => 'bg-[var(--color-accent-100)] text-[var(--color-accent-600)]',
        'restricted' => 'bg-[var(--color-restricted-bg)] text-[var(--color-restricted)]',
    ];
@endphp
<span class="inline-flex items-center rounded-full px-[11px] py-[5px] text-xs font-semibold {{ $tones[$tone] }}">
    {{ $text }}
</span>
```

- [ ] **Step 8: Write `resources/views/components/form-field.blade.php`**

```blade
@props([
    'label',
    'name',
    'type' => 'text',
    'placeholder' => '',
    'icon' => null,
    'help' => null,
    'value' => null,
    'rows' => 4,
])

@php
    $isTextarea = $type === 'textarea';
    $wrapperClasses = $isTextarea
        ? 'flex w-full items-start gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] bg-[var(--surface-card)] p-3 focus-within:border-[var(--focus-ring)]'
        : 'flex h-[var(--control-height)] w-full items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] bg-[var(--surface-card)] px-3 focus-within:border-[var(--focus-ring)]';
@endphp

<div class="flex w-full flex-col gap-[6px]">
    <label for="{{ $name }}" class="text-xs font-semibold text-[var(--text-secondary)]">{{ $label }}</label>
    <div class="{{ $wrapperClasses }}">
        @if($type === 'select')
            <select id="{{ $name }}" name="{{ $name }}" class="w-full flex-1 appearance-none bg-transparent text-sm text-[var(--text-primary)] outline-none">
                {{ $slot }}
            </select>
        @elseif($isTextarea)
            <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" class="min-h-[80px] w-full flex-1 resize-none bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]">{{ $value }}</textarea>
        @else
            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" placeholder="{{ $placeholder }}" value="{{ $value }}" class="w-full flex-1 bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
        @endif
        @if($icon)
            <x-dynamic-component :component="'lucide-' . $icon" class="h-4 w-4 shrink-0 text-[var(--color-neutral-400)]" />
        @endif
    </div>
    @if($help)
        <p class="text-[11px] text-[var(--text-muted)]">{{ $help }}</p>
    @endif
</div>
```

- [ ] **Step 9: Run test to verify it passes**

Run: `php artisan test tests/Feature/SharedComponentsTest.php`
Expected: PASS

- [ ] **Step 10: Commit**

```bash
git add app/Support/Navigation.php resources/views/components tests/Feature/SharedComponentsTest.php
git commit -m "feat: add navigation helper and shared UI components"
```

---

### Task 3: Layouts — topbar, sidebar, guest layout, app layout

**Files:**
- Create: `resources/views/components/topbar.blade.php`
- Create: `resources/views/components/sidebar.blade.php`
- Create: `resources/views/components/layouts/app.blade.php`
- Create: `resources/views/components/layouts/guest.blade.php`
- Test: `tests/Feature/LayoutTest.php`

**Interfaces:**
- Consumes: `x-nav-item` from Task 2; `Navigation::coordinator()` shape (`groups` array) from Task 2.
- Produces: `<x-layouts.app :user="['initials'=>string,'name'=>string,'role'=>string]" :nav-groups="array" title="?string">...content...</x-layouts.app>` — used by every internal screen from Task 5 onward.
- Produces: `<x-layouts.guest title="?string">...content...</x-layouts.guest>` — used by Task 4 (Login).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/LayoutTest.php

namespace Tests\Feature;

use App\Support\Navigation;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    public function test_app_layout_renders_topbar_sidebar_and_slot_content(): void
    {
        $html = Blade::render(
            '<x-layouts.app :user="$user" :nav-groups="$navGroups">CONTENIDO_DE_PRUEBA</x-layouts.app>',
            [
                'user' => ['initials' => 'AR', 'name' => 'Ana Restrepo', 'role' => 'Coordinación'],
                'navGroups' => Navigation::coordinator('panel'),
            ]
        );

        $this->assertStringContainsString('Trayectoria Estudiantil', $html);
        $this->assertStringContainsString('Ana Restrepo', $html);
        $this->assertStringContainsString('Estudiantes', $html);
        $this->assertStringContainsString('CONTENIDO_DE_PRUEBA', $html);
    }

    public function test_guest_layout_renders_slot_content(): void
    {
        $html = Blade::render('<x-layouts.guest>CONTENIDO_LOGIN</x-layouts.guest>');

        $this->assertStringContainsString('CONTENIDO_LOGIN', $html);
        $this->assertStringContainsString('<html', $html);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/LayoutTest.php`
Expected: FAIL — layout components don't exist yet.

- [ ] **Step 3: Write `resources/views/components/topbar.blade.php`**

```blade
@props(['user', 'period' => '2026-2S'])

<header class="flex h-[var(--topbar-height)] w-full shrink-0 items-center gap-3 border-b border-[var(--border-subtle)] bg-[var(--surface-card)] px-5">
    <x-lucide-menu class="h-5 w-5 text-[var(--color-neutral-600)] sm:hidden" />

    <div class="flex items-center gap-[10px]">
        <div class="flex h-[30px] w-[30px] items-center justify-center rounded-[var(--radius-md)] bg-[var(--color-primary-600)]">
            <x-lucide-graduation-cap class="h-[18px] w-[18px] text-[var(--color-white)]" />
        </div>
        <span class="hidden text-sm font-semibold text-[var(--text-primary)] sm:inline">Trayectoria Estudiantil</span>
    </div>

    <div class="flex-1"></div>

    <div class="hidden items-center gap-[6px] rounded-full bg-[var(--surface-sunken)] px-[11px] py-[5px] sm:flex">
        <x-lucide-calendar class="h-[14px] w-[14px] text-[var(--text-secondary)]" />
        <span class="text-xs font-medium text-[var(--text-secondary)]">{{ $period }}</span>
    </div>

    <x-lucide-bell class="h-[18px] w-[18px] text-[var(--color-neutral-500)]" />

    <div class="flex items-center gap-2 rounded-[var(--radius-md)] py-1 pl-1 pr-2">
        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-xs font-semibold text-[var(--color-primary-700)]">
            {{ $user['initials'] }}
        </div>
        <div class="flex flex-col leading-tight">
            <span class="text-sm font-medium text-[var(--text-primary)]">{{ $user['name'] }}</span>
            <span class="text-xs text-[var(--text-muted)]">{{ $user['role'] }}</span>
        </div>
        <x-lucide-chevron-down class="h-[14px] w-[14px] text-[var(--color-neutral-400)]" />
    </div>
</header>
```

- [ ] **Step 4: Write `resources/views/components/sidebar.blade.php`**

```blade
@props(['groups'])

<nav class="hidden h-full w-[var(--sidebar-width)] shrink-0 flex-col gap-[3px] overflow-y-auto border-r border-[var(--border-subtle)] bg-[var(--surface-card)] p-3 sm:flex">
    @foreach($groups as $group)
        @if($group['label'] ?? null)
            <p class="px-[10px] pb-[6px] pt-3 text-[11px] font-semibold tracking-[0.6px] text-[var(--text-muted)]">
                {{ $group['label'] }}
            </p>
        @endif
        @foreach($group['items'] as $item)
            <x-nav-item :icon="$item['icon']" :label="$item['label']" :href="$item['href']" :active="$item['active']" />
        @endforeach
    @endforeach
</nav>
```

- [ ] **Step 5: Write `resources/views/components/layouts/app.blade.php`**

```blade
@props(['user', 'navGroups', 'title' => null])

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'Trayectoria Estudiantil' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-[var(--surface-page)] font-sans text-[var(--text-primary)]">
    <div class="flex h-full flex-col">
        <x-topbar :user="$user" />
        <div class="flex flex-1 overflow-hidden">
            <x-sidebar :groups="$navGroups" />
            <main class="flex-1 overflow-y-auto">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
```

- [ ] **Step 6: Write `resources/views/components/layouts/guest.blade.php`**

```blade
@props(['title' => null])

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'Trayectoria Estudiantil' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="h-full font-sans">
    {{ $slot }}
</body>
</html>
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test tests/Feature/LayoutTest.php`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add resources/views/components/topbar.blade.php resources/views/components/sidebar.blade.php resources/views/components/layouts tests/Feature/LayoutTest.php
git commit -m "feat: add app and guest layouts with topbar and sidebar"
```

---

### Task 4: Login screen

**Files:**
- Create: `app/Http/Controllers/LoginController.php`
- Create: `resources/views/auth/login.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/LoginTest.php`

**Interfaces:**
- Consumes: `x-layouts.guest`, `x-form-field`, `x-button` from Tasks 2–3.
- Produces: named routes `login.show` (`GET /login`) and `login.store` (`POST /login`).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/LoginTest.php

namespace Tests\Feature;

use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Iniciar sesión');
        $response->assertSee('Una visión integral de la trayectoria de cada estudiante.');
    }

    public function test_submitting_login_redirects_to_panel_without_validation(): void
    {
        $response = $this->post('/login', []);

        $response->assertRedirect('/panel');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/LoginTest.php`
Expected: FAIL — `/login` route does not exist (404).

- [ ] **Step 3: Write `app/Http/Controllers/LoginController.php`**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect('/panel');
    }
}
```

- [ ] **Step 4: Write `resources/views/auth/login.blade.php`**

```blade
<x-layouts.guest title="Iniciar sesión · Trayectoria Estudiantil">
    <div class="flex h-full w-full flex-col overflow-y-auto sm:flex-row sm:overflow-visible">
        <div
            class="flex shrink-0 flex-col justify-between gap-8 p-8 text-[var(--color-white)] sm:h-full sm:w-[560px] sm:justify-between sm:gap-0 sm:p-14"
            style="background-image: linear-gradient(168.743deg, #112F52 5.949%, #1E5290 94.051%)"
        >
            <div class="flex items-center gap-3">
                <div class="flex h-[38px] w-[38px] items-center justify-center rounded-[var(--radius-md)] bg-white/10">
                    <x-lucide-graduation-cap class="h-[22px] w-[22px] text-white" />
                </div>
                <span class="text-base font-semibold">Trayectoria Estudiantil</span>
            </div>

            <div class="flex flex-col gap-4">
                <h1 class="text-2xl font-semibold text-white">Una visión integral de la trayectoria de cada estudiante.</h1>
                <p class="text-sm text-[var(--color-primary-200)]">
                    Historia académica, experiencias formativas y acompañamiento, reunidos en un solo lugar y conservados en el tiempo.
                </p>
            </div>

            <p class="hidden text-xs text-[var(--color-primary-300)] sm:block">
                Programa de Administración de Sistemas Informáticos<br />
                Universidad Nacional de Colombia · Sede Manizales
            </p>
        </div>

        <div class="flex flex-1 flex-col items-center justify-center gap-5 bg-[var(--surface-page)] p-6 sm:h-full sm:p-14">
            <form method="POST" action="{{ url('/login') }}" class="flex w-full max-w-[400px] flex-col gap-5 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6 sm:p-9">
                <div class="flex flex-col gap-[5px]">
                    <h2 class="text-xl font-semibold text-[var(--text-primary)]">Iniciar sesión</h2>
                    <p class="text-sm text-[var(--text-secondary)]">Use sus credenciales institucionales.</p>
                </div>

                <x-form-field label="Correo institucional" name="email" type="email" placeholder="usuario@unal.edu.co" icon="mail" />
                <x-form-field label="Contraseña" name="password" type="password" placeholder="••••••••••" icon="eye-off" />

                <x-button variant="primary" type="submit" class="w-full justify-center">Ingresar</x-button>

                <a href="#" class="text-sm font-medium text-[var(--color-primary-600)]">¿Olvidó su contraseña?</a>
            </form>

            <p class="w-full max-w-[400px] text-xs text-[var(--text-muted)]">
                Acceso por HTTPS · las contraseñas se almacenan con hash bcrypt (RNF-01, RNF-02)
            </p>
        </div>
    </div>
</x-layouts.guest>
```

- [ ] **Step 5: Add routes**

```php
// routes/web.php — append after the existing "/" route

use App\Http\Controllers\LoginController;

Route::get('/login', [LoginController::class, 'show'])->name('login.show');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Feature/LoginTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/LoginController.php resources/views/auth routes/web.php tests/Feature/LoginTest.php
git commit -m "feat: add login screen"
```

---

### Task 5: Panel de Coordinación screen

**Files:**
- Create: `app/Http/Controllers/PanelController.php`
- Create: `resources/views/panel/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PanelTest.php`

**Interfaces:**
- Consumes: `x-layouts.app`, `x-button`, `x-indicator-card`, `x-status-badge`, `Navigation::coordinator()` from Tasks 2–3.
- Produces: named route `panel.index` (`GET /panel`).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/PanelTest.php

namespace Tests\Feature;

use Tests\TestCase;

class PanelTest extends TestCase
{
    public function test_panel_renders_indicators_and_attention_table(): void
    {
        $response = $this->get('/panel');

        $response->assertOk();
        $response->assertSee('Panel del programa');
        $response->assertSee('ESTUDIANTES ACTIVOS');
        $response->assertSee('312');
        $response->assertSee('Pérez Rojas, Juan');
        $response->assertSee('Sin atender');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PanelTest.php`
Expected: FAIL — `/panel` route does not exist.

- [ ] **Step 3: Write `app/Http/Controllers/PanelController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Support\Navigation;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(): View
    {
        return view('panel.index', [
            'user' => ['initials' => 'AR', 'name' => 'Ana Restrepo', 'role' => 'Coordinación'],
            'navGroups' => Navigation::coordinator('panel'),
            'indicators' => [
                ['label' => 'ESTUDIANTES ACTIVOS', 'value' => '312', 'detail' => 'Matriculados en 2026-2S'],
                ['label' => 'PAPA PROMEDIO', 'value' => '3.9', 'detail' => '+0.1 frente al periodo anterior'],
                ['label' => 'AVANCE MEDIO', 'value' => '58%', 'detail' => 'Créditos cursados del plan'],
                ['label' => 'ALERTAS ABIERTAS', 'value' => '7', 'detail' => 'Requieren atención temprana', 'valueClass' => 'text-[var(--color-danger)]'],
            ],
            'attentionRows' => [
                ['name' => 'Pérez Rojas, Juan', 'issue' => 'Caída del PAPA frente al periodo anterior', 'date' => '12/09/2026', 'status' => 'Sin atender', 'tone' => 'danger'],
                ['name' => 'Gómez Ruiz, Laura', 'issue' => 'Tercera solicitud del mismo tipo en el periodo', 'date' => '10/09/2026', 'status' => 'En revisión', 'tone' => 'warning'],
                ['name' => 'Ruiz Salazar, Andrés', 'issue' => 'Matrícula sin avance en créditos', 'date' => '05/09/2026', 'status' => 'En revisión', 'tone' => 'warning'],
                ['name' => 'Martínez Cruz, Diana', 'issue' => 'Sin registro de tutoría en dos periodos', 'date' => '01/09/2026', 'status' => 'Atendida', 'tone' => 'success'],
            ],
            'cohortChart' => [
                ['label' => '2019', 'value' => '92%', 'height' => 152],
                ['label' => '2020', 'value' => '78%', 'height' => 129],
                ['label' => '2021', 'value' => '61%', 'height' => 101],
                ['label' => '2022', 'value' => '44%', 'height' => 73],
                ['label' => '2023', 'value' => '29%', 'height' => 48],
                ['label' => '2024', 'value' => '16%', 'height' => 26],
                ['label' => '2025', 'value' => '8%', 'height' => 13],
                ['label' => '2026', 'value' => '3%', 'height' => 5],
            ],
            'recentActivity' => [
                ['icon' => 'upload', 'description' => 'Carga de 42 registros Saber Pro', 'meta' => 'Hace 2 h · Ana Restrepo'],
                ['icon' => 'message-square', 'description' => 'Tutoría registrada a Gómez Ruiz, L.', 'meta' => 'Hace 5 h · Prof. Carlos P.'],
                ['icon' => 'file-text', 'description' => 'Solicitud de reingreso radicada', 'meta' => 'Ayer · Ruiz Salazar, A.'],
                ['icon' => 'circle-check', 'description' => 'Práctica aprobada en Softlogic S.A.S.', 'meta' => 'Ayer · Coordinación'],
                ['icon' => 'sparkles', 'description' => 'Experiencia validada: Semillero SIGMA', 'meta' => '18/09 · Coordinación'],
            ],
            'quickLinks' => [
                ['icon' => 'upload', 'label' => 'Cargar archivo de Excel'],
                ['icon' => 'file-plus', 'label' => 'Registrar decisión del comité'],
                ['icon' => 'user-plus', 'label' => 'Crear usuario y asignar rol'],
            ],
        ]);
    }
}
```

- [ ] **Step 4: Write `resources/views/panel/index.blade.php`**

```blade
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Panel del programa · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Panel del programa</h1>
                <p class="text-sm text-[var(--text-secondary)]">Datos al 20/09/2026 · última sincronización con la API hace 2 horas</p>
            </div>
            <div class="flex gap-3">
                <x-button variant="subtle" icon="download">Exportar</x-button>
                <x-button variant="primary" icon="upload">Cargar Excel</x-button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach($indicators as $indicator)
                <x-indicator-card :label="$indicator['label']" :value="$indicator['value']" :detail="$indicator['detail']" :value-class="$indicator['valueClass'] ?? null" />
            @endforeach
        </div>

        <div class="flex flex-col gap-4 lg:flex-row">
            <div class="flex flex-1 flex-col gap-4">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex items-center gap-3 px-4 py-[14px]">
                        <div class="flex flex-1 flex-col gap-[2px]">
                            <h2 class="text-sm font-semibold text-[var(--text-primary)]">Situaciones que requieren atención</h2>
                            <p class="text-xs text-[var(--text-muted)]">Detectadas por reglas del programa · RF-37</p>
                        </div>
                        <a href="/estudiantes" class="text-sm font-medium text-[var(--color-primary-600)]">Ver todas</a>
                    </div>
                    <div class="overflow-x-auto">
                        <div class="flex min-w-[620px] gap-3 border-t border-[var(--border-subtle)] px-4 py-[9px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                            <span class="w-[210px]">ESTUDIANTE</span>
                            <span class="flex-1">SITUACIÓN</span>
                            <span class="w-24">DETECTADA</span>
                            <span class="w-[118px]">ESTADO</span>
                            <span class="w-[70px]"></span>
                        </div>
                        @foreach($attentionRows as $row)
                            <div class="flex min-w-[620px] items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-3 text-sm">
                                <span class="w-[210px] text-[var(--text-primary)]">{{ $row['name'] }}</span>
                                <span class="flex-1 text-[var(--text-secondary)]">{{ $row['issue'] }}</span>
                                <span class="w-24 text-[var(--text-secondary)]">{{ $row['date'] }}</span>
                                <span class="w-[118px]"><x-status-badge :text="$row['status']" :tone="$row['tone']" /></span>
                                <a href="/estudiantes" class="w-[70px] text-sm font-medium text-[var(--color-primary-600)]">Ver ficha</a>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex flex-col gap-[2px] px-4 py-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Avance en créditos por cohorte</h2>
                        <p class="text-xs text-[var(--text-muted)]">Porcentaje medio del plan de estudios cursado</p>
                    </div>
                    <div class="flex items-end gap-[18px] overflow-x-auto border-t border-[var(--border-subtle)] px-4 py-5">
                        @foreach($cohortChart as $bar)
                            <div class="flex min-w-[28px] flex-1 flex-col items-center justify-end gap-2">
                                <span class="text-xs font-medium text-[var(--text-secondary)]">{{ $bar['value'] }}</span>
                                <div class="w-full rounded-t-[var(--radius-sm)] bg-[var(--color-primary-500)]" style="height: {{ $bar['height'] }}px"></div>
                                <span class="text-xs text-[var(--text-muted)]">{{ $bar['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4 lg:w-[360px]">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex flex-col gap-[2px] px-4 py-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Actividad reciente</h2>
                        <p class="text-xs text-[var(--text-muted)]">Últimos registros del programa</p>
                    </div>
                    <div class="flex flex-col gap-1 border-t border-[var(--border-subtle)] py-1">
                        @foreach($recentActivity as $activity)
                            <div class="flex items-center gap-[11px] px-4 py-[10px]">
                                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[var(--surface-sunken)]">
                                    <x-dynamic-component :component="'lucide-' . $activity['icon']" class="h-[15px] w-[15px] text-[var(--color-primary-600)]" />
                                </div>
                                <div class="flex flex-1 flex-col gap-[2px]">
                                    <p class="text-sm text-[var(--text-primary)]">{{ $activity['description'] }}</p>
                                    <p class="text-xs text-[var(--text-muted)]">{{ $activity['meta'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="px-4 py-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Accesos rápidos</h2>
                    </div>
                    <div class="flex flex-col gap-2 border-t border-[var(--border-subtle)] p-4">
                        @foreach($quickLinks as $link)
                            <button type="button" class="flex w-full items-center gap-[9px] rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 py-[10px] text-sm font-medium text-[var(--text-secondary)] hover:bg-[var(--surface-sunken)]">
                                <x-dynamic-component :component="'lucide-' . $link['icon']" class="h-4 w-4 text-[var(--color-primary-600)]" />
                                {{ $link['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
```

- [ ] **Step 5: Add route**

```php
// routes/web.php

use App\Http\Controllers\PanelController;

Route::get('/panel', [PanelController::class, 'index'])->name('panel.index');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Feature/PanelTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PanelController.php resources/views/panel routes/web.php tests/Feature/PanelTest.php
git commit -m "feat: add panel de coordinación screen"
```

---

### Task 6: Estudiantes · Listado screen (responsive)

**Files:**
- Modify: `app/Http/Controllers/StudentController.php` (create in this task)
- Create: `resources/views/students/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/StudentTest.php` (create in this task)

**Interfaces:**
- Consumes: `x-layouts.app`, `x-button`, `x-status-badge`, `Navigation::coordinator()`.
- Produces: named route `students.index` (`GET /estudiantes`). `StudentController` will also gain a `show` method in Task 7 — create the class now with just `index`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/StudentTest.php

namespace Tests\Feature;

use Tests\TestCase;

class StudentTest extends TestCase
{
    public function test_students_index_renders_table_and_filters(): void
    {
        $response = $this->get('/estudiantes');

        $response->assertOk();
        $response->assertSee('Estudiantes');
        $response->assertSee('312 estudiantes activos en el programa · RF-07');
        $response->assertSee('Ruiz Salazar, Andrés');
        $response->assertSee('Mostrando 1–8 de 312 estudiantes');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/StudentTest.php`
Expected: FAIL — `/estudiantes` route does not exist.

- [ ] **Step 3: Write `app/Http/Controllers/StudentController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Support\Navigation;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Mock roster shown in the listing. Row order matches the design's table exactly.
     */
    private const STUDENTS = [
        ['code' => '2019087', 'name' => 'Ruiz Salazar, Andrés', 'cohort' => '2019-2', 'papa' => '3.2', 'advance' => 88, 'status' => 'Activo', 'tone' => 'success'],
        ['code' => '2020123', 'name' => 'Pérez Rojas, Juan', 'cohort' => '2020-1', 'papa' => '4.1', 'advance' => 72, 'status' => 'Activo', 'tone' => 'success'],
        ['code' => '2021045', 'name' => 'Gómez Ruiz, Laura', 'cohort' => '2021-1', 'papa' => '3.7', 'advance' => 55, 'status' => 'Activo', 'tone' => 'success'],
        ['code' => '2021098', 'name' => 'Cardona Vélez, Mateo', 'cohort' => '2021-2', 'papa' => '2.9', 'advance' => 41, 'status' => 'En riesgo', 'tone' => 'danger'],
        ['code' => '2022011', 'name' => 'Martínez Cruz, Diana', 'cohort' => '2022-1', 'papa' => '4.4', 'advance' => 31, 'status' => 'Activo', 'tone' => 'success'],
        ['code' => '2022076', 'name' => 'Ospina Lara, Sofía', 'cohort' => '2022-2', 'papa' => '3.5', 'advance' => 27, 'status' => 'Activo', 'tone' => 'success'],
        ['code' => '2023019', 'name' => 'Herrera Niño, Camilo', 'cohort' => '2023-1', 'papa' => '3.8', 'advance' => 19, 'status' => 'Activo', 'tone' => 'success'],
        ['code' => '2023044', 'name' => 'Valencia Toro, Ana', 'cohort' => '2023-2', 'papa' => '4.0', 'advance' => 15, 'status' => 'Intercambio', 'tone' => 'info'],
    ];

    public function index(): View
    {
        return view('students.index', [
            'user' => ['initials' => 'AR', 'name' => 'Ana Restrepo', 'role' => 'Coordinación'],
            'navGroups' => Navigation::coordinator('estudiantes'),
            'students' => self::STUDENTS,
        ]);
    }
}
```

- [ ] **Step 4: Write `resources/views/students/index.blade.php`**

```blade
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Estudiantes · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Estudiantes</h1>
                <p class="text-sm text-[var(--text-secondary)]">312 estudiantes activos en el programa · RF-07</p>
            </div>
            <div class="flex gap-3">
                <x-button variant="subtle" icon="download">Exportar</x-button>
                <x-button variant="primary" icon="upload">Cargar Excel</x-button>
            </div>
        </div>

        <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[14px] sm:flex-row sm:items-center">
            <div class="flex h-[var(--control-height)] flex-1 items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3">
                <x-lucide-search class="h-4 w-4 text-[var(--color-neutral-400)]" />
                <input type="text" placeholder="Buscar por nombre, código o documento" class="w-full bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
            </div>
            <div class="flex h-[var(--control-height)] items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 text-sm text-[var(--text-secondary)]">
                Cohorte: todas <x-lucide-chevron-down class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
            </div>
            <div class="flex h-[var(--control-height)] items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 text-sm text-[var(--text-secondary)]">
                Estado: activo <x-lucide-chevron-down class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
            </div>
            <div class="flex h-[var(--control-height)] items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 text-sm text-[var(--text-secondary)]">
                Tutor: todos <x-lucide-chevron-down class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
            </div>
            <x-button variant="secondary">Filtrar</x-button>
        </div>

        <!-- Desktop table (hidden on mobile) -->
        <div class="hidden flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] sm:flex">
            <div class="flex gap-3 px-4 py-[11px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                <span class="w-24">CÓDIGO</span>
                <span class="flex-1">ESTUDIANTE</span>
                <span class="w-[92px]">COHORTE</span>
                <span class="w-16">PAPA</span>
                <span class="w-[150px]">AVANCE</span>
                <span class="w-[110px]">ESTADO</span>
                <span class="w-[76px]"></span>
            </div>
            @foreach($students as $student)
                <div class="flex items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm">
                    <span class="w-24 text-[var(--text-secondary)]">{{ $student['code'] }}</span>
                    <span class="flex-1 text-[var(--text-primary)]">{{ $student['name'] }}</span>
                    <span class="w-[92px] text-[var(--text-secondary)]">{{ $student['cohort'] }}</span>
                    <span class="w-16 text-[var(--text-secondary)]">{{ $student['papa'] }}</span>
                    <span class="flex w-[150px] items-center gap-2">
                        <span class="h-[7px] flex-1 rounded-full bg-[var(--surface-sunken)]">
                            <span class="block h-[7px] rounded-full bg-[var(--color-primary-500)]" style="width: {{ $student['advance'] }}%"></span>
                        </span>
                        <span class="text-xs text-[var(--text-secondary)]">{{ $student['advance'] }}%</span>
                    </span>
                    <span class="w-[110px]"><x-status-badge :text="$student['status']" :tone="$student['tone']" /></span>
                    <a href="/estudiantes/{{ $student['code'] }}" class="w-[76px] text-sm font-medium text-[var(--color-primary-600)]">Ver ficha</a>
                </div>
            @endforeach
        </div>

        <!-- Mobile cards (hidden on desktop), per the 360px mockup -->
        <div class="flex flex-col gap-3 sm:hidden">
            @foreach($students as $student)
                <a href="/estudiantes/{{ $student['code'] }}" class="flex flex-col gap-2 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-3">
                    <div class="flex items-center gap-2">
                        <span class="flex-1 text-sm font-medium text-[var(--text-primary)]">{{ $student['name'] }}</span>
                        <x-status-badge :text="$student['status']" :tone="$student['tone']" />
                    </div>
                    <p class="text-xs text-[var(--text-secondary)]">{{ $student['code'] }} · PAPA {{ $student['papa'] }} · avance {{ $student['advance'] }}%</p>
                    <span class="block h-[6px] rounded-full bg-[var(--surface-sunken)]">
                        <span class="block h-[6px] rounded-full bg-[var(--color-primary-500)]" style="width: {{ $student['advance'] }}%"></span>
                    </span>
                </a>
            @endforeach
        </div>

        <div class="hidden items-center gap-2 sm:flex">
            <span class="flex-1 text-sm text-[var(--text-secondary)]">Mostrando 1–8 de 312 estudiantes</span>
            <span class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)]">Anterior</span>
            <span class="rounded-[var(--radius-md)] bg-[var(--color-primary-600)] px-3 py-[7px] text-sm text-white">1</span>
            <span class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)]">2</span>
            <span class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)]">3</span>
            <span class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)]">Siguiente</span>
        </div>
    </div>
</x-layouts.app>
```

- [ ] **Step 5: Add route**

```php
// routes/web.php

use App\Http\Controllers\StudentController;

Route::get('/estudiantes', [StudentController::class, 'index'])->name('students.index');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Feature/StudentTest.php`
Expected: PASS

- [ ] **Step 7: Manual responsive check**

Run: `npm run dev` and `php artisan serve`, then open `http://127.0.0.1:8000/estudiantes` and resize to 360px width. Confirm the card layout (not the table) shows, matches `mokup/exports/20-vistas-moviles.png`, and has no horizontal overflow.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/StudentController.php resources/views/students/index.blade.php routes/web.php tests/Feature/StudentTest.php
git commit -m "feat: add estudiantes listado screen"
```

---

### Task 7: Ficha del estudiante screen (responsive)

**Files:**
- Modify: `app/Http/Controllers/StudentController.php` (add `show`)
- Create: `resources/views/students/show.blade.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/StudentTest.php`

**Interfaces:**
- Consumes: `x-layouts.app`, `x-button`, `x-status-badge`, `Navigation::coordinatorWithTutorExtras()`.
- Produces: named route `students.show` (`GET /estudiantes/{id}`). Per spec scope, `$id` is accepted but not used to look up different data — the mock profile is the same regardless of id.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/StudentTest.php — add this method to the existing class

    public function test_student_show_renders_profile_regardless_of_id(): void
    {
        $response = $this->get('/estudiantes/2020123');

        $response->assertOk();
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Evolución del PAPA por periodo');
        $response->assertSee('Información restringida');
    }

    public function test_student_show_does_not_fail_on_unknown_id(): void
    {
        $response = $this->get('/estudiantes/999999');

        $response->assertOk();
        $response->assertSee('Pérez Rojas, Juan Sebastián');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/StudentTest.php`
Expected: FAIL — `/estudiantes/{id}` route does not exist.

- [ ] **Step 3: Add `show` to `app/Http/Controllers/StudentController.php`**

```php
    public function show(string $id): View
    {
        return view('students.show', [
            'user' => ['initials' => 'CP', 'name' => 'Carlos Pineda', 'role' => 'Tutor'],
            'navGroups' => Navigation::coordinatorWithTutorExtras('estudiantes'),
            'student' => [
                'initials' => 'JP',
                'name' => 'Pérez Rojas, Juan Sebastián',
                'code' => 'Código 2020123',
                'cohort' => 'Cohorte 2020-1',
                'enrollment' => 'Séptima matrícula',
                'tutor' => 'Tutor asignado: Carlos Pineda',
            ],
            'tabs' => ['General', 'Académica', 'Experiencias', 'Trabajo de grado', 'Práctica', 'Solicitudes', 'Tutorías'],
            'papaChart' => [
                ['period' => '2020-1', 'value' => '3.4', 'height' => 76],
                ['period' => '2021-1', 'value' => '3.6', 'height' => 94],
                ['period' => '2021-2', 'value' => '3.5', 'height' => 85],
                ['period' => '2022-1', 'value' => '3.9', 'height' => 119],
                ['period' => '2022-2', 'value' => '4.2', 'height' => 145],
                ['period' => '2023-1', 'value' => '4.0', 'height' => 128],
                ['period' => '2026-2', 'value' => '4.1', 'height' => 136],
            ],
            'timeline' => [
                ['icon' => 'sparkles', 'description' => 'Semillero de investigación SIGMA', 'period' => '2022-1', 'tag' => 'Investigación', 'tone' => 'primary'],
                ['icon' => 'book-open', 'description' => 'Monitoría de Bases de Datos', 'period' => '2023-2', 'tag' => 'Vinculación', 'tone' => 'accent'],
                ['icon' => 'plane', 'description' => 'Pasantía Sígueme · Univ. de Antioquia', 'period' => '2024-1', 'tag' => 'Pasantía', 'tone' => 'restricted'],
                ['icon' => 'mic', 'description' => 'Ponencia en Congreso Colombiano de Computación', 'period' => '2025-2', 'tag' => 'Evento', 'tone' => 'warning'],
                ['icon' => 'briefcase', 'description' => 'Práctica empresarial · Softlogic S.A.S.', 'period' => '2026-2', 'tag' => 'Práctica', 'tone' => 'success'],
            ],
            'processes' => [
                ['title' => 'Trabajo de grado · modalidad pasantía', 'meta' => 'Director: Prof. María Londoño · 2026-2S', 'status' => 'En desarrollo', 'tone' => 'warning'],
                ['title' => 'Práctica empresarial · Softlogic S.A.S.', 'meta' => 'Asesor: Prof. Carlos Pineda · jul–dic 2026', 'status' => 'En curso', 'tone' => 'info'],
            ],
            'progress' => [
                'percent' => 72,
                'details' => [
                    ['label' => 'Créditos cursados', 'value' => '116 de 160'],
                    ['label' => 'Cupo del periodo', 'value' => '21 créditos'],
                    ['label' => 'PAPA actual', 'value' => '4.1'],
                    ['label' => 'Promedio anterior', 'value' => '4.0'],
                ],
            ],
            'tutorials' => [
                ['date' => '12/09/2026', 'reason' => 'Seguimiento a carga académica', 'note' => 'Acordó reducir a 15 créditos el próximo periodo.'],
                ['date' => '03/05/2026', 'reason' => 'Orientación sobre modalidad de grado', 'note' => 'Interesado en pasantía; se remitió a Coordinación.'],
                ['date' => '18/11/2025', 'reason' => 'Revisión de avance', 'note' => 'Sin novedades; continúa según plan.'],
            ],
        ]);
    }
```

- [ ] **Step 4: Write `resources/views/students/show.blade.php`**

```blade
@php($ringCircumference = 2 * pi() * 58)
@php($ringOffset = $ringCircumference * (1 - $progress['percent'] / 100))

<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $student['name'] }} · Trayectoria Estudiantil">
    <div class="flex flex-col gap-4 p-4 sm:p-7">
        <div class="flex items-center gap-[6px] text-sm text-[var(--text-secondary)]">
            <a href="/panel">Inicio</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <a href="/estudiantes">Estudiantes</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <span class="text-[var(--text-primary)]">{{ $student['name'] }}</span>
        </div>

        <div class="flex flex-col gap-4 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[18px] sm:flex-row sm:items-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-lg font-semibold text-[var(--color-primary-700)]">
                {{ $student['initials'] }}
            </div>
            <div class="flex flex-1 flex-col gap-[6px]">
                <h1 class="text-xl font-semibold text-[var(--text-primary)]">{{ $student['name'] }}</h1>
                <div class="flex flex-wrap gap-2">
                    @foreach([$student['code'], $student['cohort'], $student['enrollment'], $student['tutor']] as $chip)
                        <span class="rounded-full bg-[var(--surface-sunken)] px-[9px] py-1 text-xs text-[var(--text-secondary)]">{{ $chip }}</span>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-3">
                <x-button variant="secondary">Exportar ficha</x-button>
                <x-button variant="primary">Registrar tutoría</x-button>
            </div>
        </div>

        <div class="flex gap-1 overflow-x-auto border-b border-[var(--border-subtle)]">
            @foreach($tabs as $index => $tab)
                <span class="shrink-0 border-b-2 px-[14px] py-[10px] text-sm font-medium {{ $index === 0 ? 'border-[var(--color-primary-600)] text-[var(--color-primary-700)]' : 'border-transparent text-[var(--text-secondary)]' }}">
                    {{ $tab }}
                </span>
            @endforeach
        </div>

        <div class="flex flex-col gap-4 lg:flex-row">
            <div class="flex flex-1 flex-col gap-4">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex flex-col gap-[2px] px-4 pb-[10px] pt-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Evolución del PAPA por periodo</h2>
                        <p class="text-xs text-[var(--text-muted)]">Historia conservada por periodo, no sobrescrita · RNF-08</p>
                    </div>
                    <div class="flex items-end gap-[14px] overflow-x-auto border-t border-[var(--border-subtle)] px-4 py-4">
                        @foreach($papaChart as $bar)
                            <div class="flex min-w-[36px] flex-1 flex-col items-center justify-end gap-[7px]">
                                <span class="text-xs font-medium text-[var(--text-secondary)]">{{ $bar['value'] }}</span>
                                <div class="w-full rounded-t-[var(--radius-sm)] bg-[var(--color-primary-500)]" style="height: {{ $bar['height'] }}px"></div>
                                <span class="text-xs text-[var(--text-muted)]">{{ $bar['period'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex flex-col gap-[2px] px-4 pb-[10px] pt-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Línea de tiempo de experiencias formativas</h2>
                        <p class="text-xs text-[var(--text-muted)]">Registradas y validadas por Coordinación · RF-30, RF-33</p>
                    </div>
                    <div class="flex flex-col border-t border-[var(--border-subtle)] px-4 pb-4">
                        @foreach($timeline as $item)
                            <div class="flex gap-3 py-2">
                                <div class="flex w-7 flex-col items-center">
                                    <div class="flex h-[26px] w-[26px] items-center justify-center rounded-full bg-[var(--surface-sunken)]">
                                        <x-dynamic-component :component="'lucide-' . $item['icon']" class="h-[14px] w-[14px] text-[var(--color-primary-600)]" />
                                    </div>
                                    @if(!$loop->last)
                                        <span class="mt-1 w-[2px] flex-1 bg-[var(--border-subtle)]"></span>
                                    @endif
                                </div>
                                <div class="flex flex-1 flex-col gap-[3px] pb-2 pt-[3px]">
                                    <p class="text-sm text-[var(--text-primary)]">{{ $item['description'] }}</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-[var(--text-muted)]">{{ $item['period'] }}</span>
                                        <x-status-badge :text="$item['tag']" :tone="$item['tone']" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="px-4 pb-[10px] pt-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Procesos en curso</h2>
                    </div>
                    <div class="flex flex-col gap-[10px] border-t border-[var(--border-subtle)] p-4">
                        @foreach($processes as $process)
                            <div class="flex items-center gap-3 rounded-[var(--radius-md)] border border-[var(--border-subtle)] p-3">
                                <div class="flex flex-1 flex-col gap-[3px]">
                                    <p class="text-sm font-medium text-[var(--text-primary)]">{{ $process['title'] }}</p>
                                    <p class="text-xs text-[var(--text-muted)]">{{ $process['meta'] }}</p>
                                </div>
                                <x-status-badge :text="$process['status']" :tone="$process['tone']" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4 lg:w-[340px]">
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="px-4 pb-[10px] pt-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Avance en el plan de estudios</h2>
                    </div>
                    <div class="flex flex-col items-center gap-[18px] border-t border-[var(--border-subtle)] px-4 pb-[18px] pt-1">
                        <svg width="132" height="132" viewBox="0 0 132 132" class="shrink-0">
                            <circle cx="66" cy="66" r="58" fill="none" stroke="var(--color-neutral-200)" stroke-width="12" />
                            <circle
                                cx="66" cy="66" r="58" fill="none" stroke="var(--color-primary-600)" stroke-width="12"
                                stroke-linecap="round" transform="rotate(-90 66 66)"
                                stroke-dasharray="{{ $ringCircumference }}" stroke-dashoffset="{{ $ringOffset }}"
                            />
                            <text x="66" y="62" text-anchor="middle" class="fill-[var(--text-primary)] text-[22px] font-bold">{{ $progress['percent'] }}%</text>
                            <text x="66" y="80" text-anchor="middle" class="fill-[var(--text-muted)] text-[11px]">cursado</text>
                        </svg>
                        <div class="flex w-full flex-col gap-[9px]">
                            @foreach($progress['details'] as $detail)
                                <div class="flex items-center gap-2">
                                    <span class="flex-1 text-sm text-[var(--text-secondary)]">{{ $detail['label'] }}</span>
                                    <span class="text-sm font-medium text-[var(--text-primary)]">{{ $detail['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex flex-col gap-[2px] px-4 pb-[10px] pt-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Últimas tutorías</h2>
                        <p class="text-xs text-[var(--text-muted)]">Visibles para tutores y Coordinación</p>
                    </div>
                    <div class="flex flex-col gap-[10px] border-t border-[var(--border-subtle)] p-4">
                        @foreach($tutorials as $tutorial)
                            <div class="flex flex-col gap-1 rounded-[var(--radius-md)] border border-[var(--border-subtle)] p-[11px]">
                                <span class="text-xs text-[var(--text-muted)]">{{ $tutorial['date'] }}</span>
                                <span class="text-sm font-medium text-[var(--text-primary)]">{{ $tutorial['reason'] }}</span>
                                <span class="text-xs text-[var(--text-secondary)]">{{ $tutorial['note'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-[11px] rounded-[var(--radius-lg)] border border-[var(--color-restricted)] bg-[var(--color-restricted-bg)] p-[14px]">
                    <x-lucide-lock class="h-[17px] w-[17px] shrink-0 text-[var(--color-restricted)]" />
                    <div class="flex flex-col gap-[3px]">
                        <p class="text-sm font-semibold text-[var(--color-restricted)]">Información restringida</p>
                        <p class="text-xs text-[var(--text-secondary)]">El perfil psicosocial y de salud solo es visible para Coordinación. Cada consulta queda auditada (RNF-05, RNF-07).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
```

- [ ] **Step 5: Add route**

```php
// routes/web.php

Route::get('/estudiantes/{id}', [StudentController::class, 'show'])->name('students.show');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Feature/StudentTest.php`
Expected: PASS (all 4 methods)

- [ ] **Step 7: Manual responsive check**

Resize the browser to 360px on `/estudiantes/2020123` and confirm no clipped text or overflow, comparing against `mokup/exports/20-vistas-moviles.png` and `mokup/exports/13-ficha-estudiante.png`.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/StudentController.php resources/views/students/show.blade.php routes/web.php tests/Feature/StudentTest.php
git commit -m "feat: add ficha del estudiante screen"
```

---

### Task 8: Solicitud · Formulario screen (responsive)

**Files:**
- Create: `app/Http/Controllers/RequestController.php`
- Create: `resources/views/requests/create.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/RequestTest.php`

**Interfaces:**
- Consumes: `x-layouts.app`, `x-form-field`, `x-button`, `Navigation::studentPortal()`.
- Produces: named routes `requests.create` (`GET /solicitudes/nueva`) and `requests.store` (`POST /solicitudes/nueva`, no validation, redirects back to `requests.create` — same no-backend pattern as `login.store`).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/RequestTest.php

namespace Tests\Feature;

use Tests\TestCase;

class RequestTest extends TestCase
{
    public function test_request_form_renders_fields_and_options(): void
    {
        $response = $this->get('/solicitudes/nueva');

        $response->assertOk();
        $response->assertSee('Nueva solicitud al comité asesor');
        $response->assertSee('Cancelación de asignatura');
        $response->assertSee('Radicar solicitud');
        $response->assertSee('Falta un campo obligatorio');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/RequestTest.php`
Expected: FAIL — `/solicitudes/nueva` route does not exist.

- [ ] **Step 3: Write `app/Http/Controllers/RequestController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Support\Navigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('requests.create');
    }

    public function create(): View
    {
        return view('requests.create', [
            'user' => ['initials' => 'JP', 'name' => 'Juan Pérez', 'role' => 'Estudiante'],
            'navGroups' => Navigation::studentPortal('solicitudes'),
            'requestTypes' => [
                'Cancelación de asignatura',
                'Reingreso al programa',
                'Homologación de créditos',
                'Ampliación de cupo de créditos',
                'Otras',
            ],
            'steps' => [
                ['number' => 1, 'title' => 'Radicada', 'detail' => 'Queda registrada con fecha y ya no puede editarla.'],
                ['number' => 2, 'title' => 'En revisión', 'detail' => 'El comité asesor estudia el caso.'],
                ['number' => 3, 'title' => 'Con decisión', 'detail' => 'Coordinación carga la decisión y el número de acta.'],
            ],
        ]);
    }
}
```

- [ ] **Step 4: Write `resources/views/requests/create.blade.php`**

```blade
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Nueva solicitud · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex items-center gap-[6px] text-sm text-[var(--text-secondary)]">
            <a href="/panel">Inicio</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <a href="/solicitudes/nueva">Mis solicitudes</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <span class="text-[var(--text-primary)]">Nueva solicitud</span>
        </div>

        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Nueva solicitud al comité asesor</h1>
            <p class="text-sm text-[var(--text-secondary)]">Los campos marcados con * son obligatorios · RF-25</p>
        </div>

        <div class="flex flex-col gap-5 lg:flex-row">
            <form method="POST" action="{{ route('requests.store') }}" class="flex flex-1 flex-col gap-[18px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6">
                @csrf
                <div class="flex flex-col gap-4 sm:flex-row">
                    <div class="flex-1">
                        <x-form-field label="Tipo de solicitud *" name="tipo" type="select" help="Cancelación de asignatura, reingreso, homologación, cupo de créditos…">
                            <option value="">Seleccione…</option>
                            @foreach($requestTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </x-form-field>
                    </div>
                    <div class="sm:w-[300px]">
                        <x-form-field label="Periodo académico *" name="periodo" value="2026-2S" help="Se toma del periodo activo." />
                    </div>
                </div>

                <x-form-field
                    label="Descripción y justificación *"
                    name="descripcion"
                    type="textarea"
                    placeholder="Describa el motivo de la solicitud y los hechos que la sustentan…"
                    help="Mínimo 50 caracteres."
                />

                <div class="flex flex-col gap-[6px]">
                    <label class="text-xs font-semibold text-[var(--text-secondary)]">Soportes</label>
                    <div class="flex h-24 flex-col items-center justify-center gap-[5px] rounded-[var(--radius-md)] border border-dashed border-[var(--border-default)]">
                        <x-lucide-cloud-upload class="h-[22px] w-[22px] text-[var(--color-neutral-400)]" />
                        <p class="text-sm text-[var(--text-secondary)]">Arrastre los archivos aquí o haga clic para seleccionar</p>
                        <p class="text-xs text-[var(--text-muted)]">PDF, JPG o PNG · máximo 5 MB por archivo</p>
                    </div>
                    <div class="flex items-center gap-[10px] rounded-[var(--radius-md)] border border-[var(--border-subtle)] p-[10px]">
                        <x-lucide-file-text class="h-4 w-4 text-[var(--color-neutral-500)]" />
                        <span class="flex-1 text-sm text-[var(--text-primary)]">soporte-medico.pdf</span>
                        <span class="text-xs text-[var(--text-muted)]">1,2 MB</span>
                        <x-lucide-x class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
                    </div>
                </div>

                <div class="flex gap-[10px] rounded-[var(--radius-md)] bg-[var(--color-danger-bg)] p-3">
                    <x-lucide-circle-alert class="h-4 w-4 shrink-0 text-[var(--color-danger)]" />
                    <div class="flex flex-col gap-[2px]">
                        <p class="text-sm font-semibold text-[var(--color-danger)]">Falta un campo obligatorio</p>
                        <p class="text-xs text-[var(--text-secondary)]">Seleccione el tipo de solicitud antes de continuar. Los datos escritos se conservan (RNF-10).</p>
                    </div>
                </div>

                <div class="flex flex-col gap-[10px] sm:flex-row sm:items-center">
                    <div class="flex-1"></div>
                    <x-button variant="secondary">Cancelar</x-button>
                    <x-button variant="secondary">Guardar borrador</x-button>
                    <x-button variant="primary" type="submit">Radicar solicitud</x-button>
                </div>
            </form>

            <div class="flex flex-col gap-4 lg:w-[320px]">
                <div class="flex flex-col gap-[14px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[18px]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">¿Qué pasa después?</h2>
                    @foreach($steps as $step)
                        <div class="flex gap-[11px]">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--color-primary-50)] text-xs font-semibold text-[var(--color-primary-700)]">
                                {{ $step['number'] }}
                            </div>
                            <div class="flex flex-col gap-[2px]">
                                <p class="text-sm font-medium text-[var(--text-primary)]">{{ $step['title'] }}</p>
                                <p class="text-xs text-[var(--text-secondary)]">{{ $step['detail'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex gap-[10px] rounded-[var(--radius-lg)] bg-[var(--color-info-bg)] p-[14px]">
                    <x-lucide-info class="h-4 w-4 shrink-0 text-[var(--color-info)]" />
                    <p class="text-sm text-[var(--text-secondary)]">Cada cambio de estado queda registrado con autor y fecha, de modo que la solicitud conserva su traza completa (RNF-07).</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
```

- [ ] **Step 5: Add route**

```php
// routes/web.php

use App\Http\Controllers\RequestController;

Route::get('/solicitudes/nueva', [RequestController::class, 'create'])->name('requests.create');
Route::post('/solicitudes/nueva', [RequestController::class, 'store'])->name('requests.store');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Feature/RequestTest.php`
Expected: PASS

- [ ] **Step 7: Manual responsive check**

Resize to 360px on `/solicitudes/nueva` and compare against `mokup/exports/20-vistas-moviles.png` (Nueva solicitud variant) — fields stack vertically, action buttons stack full-width.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/RequestController.php resources/views/requests routes/web.php tests/Feature/RequestTest.php
git commit -m "feat: add solicitud formulario screen"
```

---

### Task 9: Indicadores del programa screen

**Files:**
- Create: `app/Http/Controllers/IndicatorController.php`
- Create: `resources/views/indicators/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/IndicatorTest.php`

**Interfaces:**
- Consumes: `x-layouts.app`, `x-button`, `x-indicator-card`, `Navigation::coordinator()`.
- Produces: named route `indicators.index` (`GET /indicadores`).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/IndicatorTest.php

namespace Tests\Feature;

use Tests\TestCase;

class IndicatorTest extends TestCase
{
    public function test_indicators_page_renders_cards_and_tables(): void
    {
        $response = $this->get('/indicadores');

        $response->assertOk();
        $response->assertSee('Indicadores del programa');
        $response->assertSee('PRÁCTICAS EN CURSO');
        $response->assertSee('Distribución del PAPA');
        $response->assertSee('Cancelación de asignatura');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/IndicatorTest.php`
Expected: FAIL — `/indicadores` route does not exist.

- [ ] **Step 3: Write `app/Http/Controllers/IndicatorController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Support\Navigation;
use Illuminate\View\View;

class IndicatorController extends Controller
{
    public function index(): View
    {
        return view('indicators.index', [
            'user' => ['initials' => 'AR', 'name' => 'Ana Restrepo', 'role' => 'Coordinación'],
            'navGroups' => Navigation::coordinator('indicadores'),
            'keyIndicators' => [
                ['label' => 'ESTUDIANTES ACTIVOS', 'value' => '312', 'detail' => 'Matriculados en 2026-2S'],
                ['label' => 'PRÁCTICAS EN CURSO', 'value' => '28', 'detail' => '9% de los activos'],
                ['label' => 'TRABAJOS DE GRADO', 'value' => '41', 'detail' => '13% de los activos'],
                ['label' => 'SOLICITUDES DEL PERIODO', 'value' => '96', 'detail' => '31 aún sin decisión', 'valueClass' => 'text-[var(--color-warning)]'],
            ],
            'papaHistogram' => [
                ['range' => '< 3.0', 'value' => '22', 'height' => 33],
                ['range' => '3.0–3.3', 'value' => '41', 'height' => 62],
                ['range' => '3.3–3.6', 'value' => '68', 'height' => 102],
                ['range' => '3.6–4.0', 'value' => '94', 'height' => 141],
                ['range' => '4.0–4.3', 'value' => '59', 'height' => 89],
                ['range' => '> 4.3', 'value' => '28', 'height' => 42],
            ],
            'participation' => [
                ['label' => 'Semilleros y grupos de investigación', 'value' => '54 · 17%', 'percent' => 17],
                ['label' => 'Monitorías y vinculaciones', 'value' => '38 · 12%', 'percent' => 12],
                ['label' => 'Deporte y cultura', 'value' => '47 · 15%', 'percent' => 15],
                ['label' => 'Pasantías e intercambios', 'value' => '11 · 4%', 'percent' => 4],
                ['label' => 'Eventos y publicaciones', 'value' => '23 · 7%', 'percent' => 7],
            ],
            'requestsByType' => [
                ['type' => 'Cancelación de asignatura', 'filed' => '38', 'review' => '9', 'decided' => '29', 'avgTime' => '6 días'],
                ['type' => 'Reingreso al programa', 'filed' => '21', 'review' => '7', 'decided' => '14', 'avgTime' => '11 días'],
                ['type' => 'Homologación de créditos', 'filed' => '17', 'review' => '8', 'decided' => '9', 'avgTime' => '14 días'],
                ['type' => 'Ampliación de cupo de créditos', 'filed' => '12', 'review' => '4', 'decided' => '8', 'avgTime' => '5 días'],
                ['type' => 'Otras', 'filed' => '8', 'review' => '3', 'decided' => '5', 'avgTime' => '9 días'],
            ],
        ]);
    }
}
```

- [ ] **Step 4: Write `resources/views/indicators/index.blade.php`**

```blade
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Indicadores del programa · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Indicadores del programa</h1>
                <p class="text-sm text-[var(--text-secondary)]">Vista exclusiva de Coordinación · calculada sobre datos históricos por periodo · RF-38</p>
            </div>
            <x-button variant="subtle" icon="download">Exportar</x-button>
        </div>

        <div class="flex flex-wrap items-center gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-3">
            <div class="flex h-[38px] items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 text-sm text-[var(--text-secondary)]">
                Cohorte: todas <x-lucide-chevron-down class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
            </div>
            <div class="flex h-[38px] items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 text-sm text-[var(--text-secondary)]">
                Periodo: 2026-2S <x-lucide-chevron-down class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
            </div>
            <div class="flex h-[38px] items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3 text-sm text-[var(--text-secondary)]">
                Estado: activos <x-lucide-chevron-down class="h-[15px] w-[15px] text-[var(--color-neutral-400)]" />
            </div>
            <x-button variant="secondary">Aplicar</x-button>
            <div class="flex-1"></div>
            <span class="text-sm text-[var(--text-muted)]">Datos al 20/09/2026</span>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach($keyIndicators as $indicator)
                <x-indicator-card :label="$indicator['label']" :value="$indicator['value']" :detail="$indicator['detail']" :value-class="$indicator['valueClass'] ?? null" />
            @endforeach
        </div>

        <div class="flex flex-col gap-4 lg:flex-row">
            <div class="flex flex-1 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                <div class="flex flex-col gap-[2px] px-4 pb-2 pt-[14px]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Distribución del PAPA</h2>
                    <p class="text-xs text-[var(--text-muted)]">Número de estudiantes por rango</p>
                </div>
                <div class="flex items-end gap-3 border-t border-[var(--border-subtle)] px-4 py-4">
                    @foreach($papaHistogram as $bar)
                        <div class="flex flex-1 flex-col items-center justify-end gap-[7px]">
                            <span class="text-xs font-medium text-[var(--text-secondary)]">{{ $bar['value'] }}</span>
                            <div class="w-full rounded-t-[var(--radius-sm)] bg-[var(--color-primary-500)]" style="height: {{ $bar['height'] }}px"></div>
                            <span class="text-xs text-[var(--text-muted)]">{{ $bar['range'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] lg:w-[470px]">
                <div class="flex flex-col gap-[2px] px-4 pb-2 pt-[14px]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Participación en experiencias formativas</h2>
                    <p class="text-xs text-[var(--text-muted)]">Estudiantes con al menos un registro</p>
                </div>
                <div class="flex flex-col gap-3 border-t border-[var(--border-subtle)] px-4 py-[10px]">
                    @foreach($participation as $row)
                        <div class="flex flex-col gap-[5px]">
                            <div class="flex items-center gap-2">
                                <span class="flex-1 text-sm text-[var(--text-primary)]">{{ $row['label'] }}</span>
                                <span class="text-sm text-[var(--text-secondary)]">{{ $row['value'] }}</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-[var(--surface-sunken)]">
                                <div class="h-2 rounded-full bg-[var(--color-primary-500)]" style="width: {{ $row['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
            <div class="flex flex-col gap-[2px] px-4 pb-2 pt-[14px]">
                <h2 class="text-sm font-semibold text-[var(--text-primary)]">Solicitudes por tipo y estado</h2>
                <p class="text-xs text-[var(--text-muted)]">Periodo 2026-2S · RF-28</p>
            </div>
            <div class="hidden gap-3 border-t border-[var(--border-subtle)] px-4 py-[10px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)] sm:flex">
                <span class="flex-1">TIPO DE SOLICITUD</span>
                <span class="w-[110px]">RADICADAS</span>
                <span class="w-[120px]">EN REVISIÓN</span>
                <span class="w-[125px]">CON DECISIÓN</span>
                <span class="w-[125px]">TIEMPO MEDIO</span>
            </div>
            @foreach($requestsByType as $row)
                <div class="flex flex-col gap-1 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm sm:flex-row sm:items-center sm:gap-3">
                    <span class="flex-1 font-medium text-[var(--text-primary)] sm:font-normal">{{ $row['type'] }}</span>
                    <span class="text-[var(--text-secondary)] sm:w-[110px]">Radicadas: {{ $row['filed'] }}</span>
                    <span class="text-[var(--text-secondary)] sm:w-[120px]">En revisión: {{ $row['review'] }}</span>
                    <span class="text-[var(--text-secondary)] sm:w-[125px]">Con decisión: {{ $row['decided'] }}</span>
                    <span class="text-[var(--text-secondary)] sm:w-[125px]">Tiempo medio: {{ $row['avgTime'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
```

- [ ] **Step 5: Add route**

```php
// routes/web.php

use App\Http\Controllers\IndicatorController;

Route::get('/indicadores', [IndicatorController::class, 'index'])->name('indicators.index');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Feature/IndicatorTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/IndicatorController.php resources/views/indicators routes/web.php tests/Feature/IndicatorTest.php
git commit -m "feat: add indicadores del programa screen"
```

---

### Task 10: Cross-screen navigation and full regression pass

**Files:**
- Test: `tests/Feature/NavigationTest.php`

**Interfaces:**
- Consumes: all routes from Tasks 4–9.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/NavigationTest.php

namespace Tests\Feature;

use Tests\TestCase;

class NavigationTest extends TestCase
{
    public function test_every_internal_screen_links_to_the_other_implemented_screens(): void
    {
        $panel = $this->get('/panel');
        $panel->assertSee('href="/estudiantes"', false);

        $students = $this->get('/estudiantes');
        $students->assertSee('href="/estudiantes/2019087"', false);
        $students->assertSee('href="/indicadores"', false);

        $requestForm = $this->get('/solicitudes/nueva');
        $requestForm->assertSee('href="/panel"', false);
    }

    public function test_all_six_screens_return_200(): void
    {
        foreach (['/login', '/panel', '/estudiantes', '/estudiantes/2019087', '/solicitudes/nueva', '/indicadores'] as $route) {
            $this->get($route)->assertOk();
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails or passes**

Run: `php artisan test tests/Feature/NavigationTest.php`
Expected: Likely PASS already, since every screen already links to `/panel`, `/estudiantes`, and `/indicadores` via the sidebar built in Task 3. If any `assertSee` fails, fix the specific `href` in the relevant view from Tasks 4–9 (e.g. an `x-nav-item` still pointing at `#` where a route now exists) rather than adding new markup.

- [ ] **Step 3: Run the full test suite**

Run: `php artisan test`
Expected: All tests across every task pass.

- [ ] **Step 4: Full manual visual pass**

Run: `npm run dev` and `php artisan serve`. Visit `/login`, `/panel`, `/estudiantes`, `/estudiantes/2020123`, `/solicitudes/nueva`, `/indicadores` at 1440px width and compare each against its matching file in `mokup/exports/` (`10-login.png`, `11-panel-coordinacion.png`, `12-listado-estudiantes.png`, `13-ficha-estudiante.png`, `14-formulario-solicitud.png`, `15-indicadores.png`). Confirm: no clipped content, sufficient text contrast, consistent spacing, sidebar active state matches the current screen. Then resize to 360px and revisit all six routes — `/estudiantes`, `/estudiantes/2020123`, and `/solicitudes/nueva` should match `20-vistas-moviles.png`; `/login`, `/panel`, and `/indicadores` (no explicit mobile mockup) should just show no horizontal overflow, no clipped text, and the sidebar replaced by the topbar's menu icon.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/NavigationTest.php
git commit -m "test: verify cross-screen navigation and full regression pass"
```
