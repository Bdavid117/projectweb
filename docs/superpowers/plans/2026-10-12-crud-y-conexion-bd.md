# CRUD de solicitudes y tutorías y conexión de las pantallas a la BD — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que las pantallas existentes lean de PostgreSQL y que solicitudes y tutorías tengan CRUD real con validación, permisos por rol/propiedad y trazabilidad.

**Architecture:** Los controladores siguen armando los mismos arrays que reciben las vistas Blade, pero desde Eloquent. La lógica de negocio vive en `App\Services\RequestWorkflow` (solicitudes) y `App\Support\{ProgramMetrics,StudentProfile,Labels}` (lectura). Autorización con Policies además del middleware `role:`. Vistas nuevas (Solicitudes, Detalle, Registrar tutoría, Mis asesorías) construidas desde los marcos 16–19 de Pencil.

**Tech Stack:** Laravel 10.50 (PHP 8.1), PostgreSQL, Blade + Tailwind v4, PHPUnit (`RefreshDatabase`, `Storage::fake`).

**Spec:** `docs/superpowers/specs/2026-10-12-crud-y-conexion-bd-design.md` (base: `docs/superpowers/specs/2026-10-12-base-de-datos-y-auth-design.md`).

## Global Constraints

- PostgreSQL; tests contra `trayectoria_estudiantil_test` con `RefreshDatabase` (la guarda de `TestCase::setUpTraits` impide correr contra otra base).
- Todo test que toque un controlador respaldado por BD usa `RefreshDatabase`; los tests de componentes con `Blade::render` no.
- Tamaño de página 8 (`config('program.page_size')`); ventana de edición de tutorías 24 h; soportes PDF/JPG/PNG, máx. 5 MB (5120 KB) por archivo, hasta 5 archivos, disco privado `local`, nunca URL pública.
- Mensajes de validación en español, errores junto al campo y datos del formulario conservados (RNF-10).
- Estados de solicitud: `radicada` → `en_revision` → `con_decision`; decisión: `aprobada`, `rechazada`, `aplazada`. Solo Coordinación cambia estados (RF-26).
- Entradas de usuario de tipo arreglo (`q[]=x`) nunca deben producir 500: leer con `$request->string()`/`integer()`.
- Búsquedas con `ilike` escapando `\`, `%` y `_` (`addcslashes($s, '\\%_')`).
- Tailwind: clases literales completas con variables CSS del proyecto; nunca concatenar variables dentro de un nombre de clase. `style=` inline solo para anchos/alturas dirigidos por datos (barras de gráficos y progreso).
- Commits: Conventional Commits con scope, descripción breve en español y en minúsculas.
- No tocar `mokup/` salvo la sincronización de Pencil de la Task 13; el `.pen` solo se edita con las herramientas MCP de Pencil.

## Review Focus

1. Un estudiante no puede abrir, descargar soportes ni listar solicitudes de otro; `/panel` y `/solicitudes` no le muestran datos ajenos (Tasks 6, 8).
2. Soportes inválidos (6 MB, extensión `.exe`, 6 archivos) no dejan nada guardado: ni fila en BD ni archivo en disco (Task 9).
3. Decidir dos veces, decidir sin pasar por revisión o decidir sin ser Coordinación no altera el estado ni el historial (Task 10).
4. Frontera de las 24 h: a 23 h 59 min el autor edita, a 24 h 01 min recibe 403; otro tutor nunca edita; una tutoría eliminada no aparece en la ficha (Task 11).
5. Base vacía (sin periodos, sin registros) y entradas hostiles (`%`, `_`, arreglos, ids inexistentes) no rompen Panel, Indicadores, listados ni fichas (Tasks 4, 5, 6).

---

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `config/program.php` | Umbrales y límites (PAPA de alerta, página, créditos, 24 h, soportes, motivos de tutoría) |
| `app/Support/Labels.php` | Etiquetas/tonos de estado, formato de fechas, periodo y tamaños |
| `app/Support/StudentProfile.php` | Arma el array de la ficha y la cabecera del estudiante |
| `app/Support/ProgramMetrics.php` | Cálculos de Panel e Indicadores |
| `app/Services/RequestWorkflow.php` | Radicar, pasar a revisión, decidir, historial de estados |
| `app/Policies/{CommitteeRequest,Tutorship}Policy.php` | Autorización por rol, propiedad y tiempo |
| `app/Http/Requests/{StoreSolicitud,DecideRequest,StoreTutorship}Request.php` | Validación en español |
| `app/Http/Controllers/{Student,Request,Tutorship,Advisory,Panel,Indicator}Controller.php` | Controladores delgados |
| `resources/views/components/{form-field,filter-select,pagination}.blade.php` | Componentes compartidos |
| `resources/views/{requests/{index,show,create},tutorships/form,advisories/index,students/*,panel,indicators}` | Vistas |
| `tests/Feature/*`, `tests/Unit/LabelsTest.php`, `tests/Concerns/CreatesStudents.php` | Pruebas |

---

### Task 1: Configuración, periodo vigente, etiquetas y menú por rol

**Files:**
- Create: `config/program.php`, `app/Support/Labels.php`, `tests/Unit/LabelsTest.php`, `tests/Feature/NavigationRolesTest.php`, `tests/Feature/Database/AcademicTermCurrentTest.php`
- Modify: `config/app.php:86`, `app/Models/AcademicTerm.php`, `app/Support/Navigation.php`

**Interfaces:**
- Produces:
  - `config('program.*')` con claves `papa_alert`, `page_size`, `total_credits`, `tutorship_edit_hours`, `attachment_max_kb`, `attachment_max_files`, `tutorship_reasons`.
  - `AcademicTerm::current(): ?AcademicTerm`.
  - `Labels::studentStatus(string): array{0:string,1:string}`, `Labels::requestStatus(CommitteeRequest): array{0:string,1:string}`, `Labels::stateLabel(string): string`, `Labels::period(CarbonInterface): string`, `Labels::date(?CarbonInterface): string`, `Labels::termLabel(?AcademicTerm): string`, `Labels::bytes(int): string`.
  - `Navigation::forRole(Role $role, string $active): array` ya filtrado por rol; ítems `solicitudes` → `/solicitudes`, `registrar-tutoria` y `mis-asesorias` → `/mis-asesorias`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Unit/LabelsTest.php

namespace Tests\Unit;

use App\Models\CommitteeRequest;
use App\Support\Labels;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class LabelsTest extends TestCase
{
    public function test_student_status_maps_to_text_and_tone(): void
    {
        $this->assertSame(['Activo', 'success'], Labels::studentStatus('activo'));
        $this->assertSame(['En riesgo', 'danger'], Labels::studentStatus('en_riesgo'));
        $this->assertSame(['Intercambio', 'info'], Labels::studentStatus('intercambio'));
        $this->assertSame(['Algo raro', 'info'], Labels::studentStatus('algo_raro'));
    }

    public function test_request_status_uses_the_decision_once_decided(): void
    {
        $this->assertSame(['Radicada', 'info'], Labels::requestStatus(new CommitteeRequest(['estado' => 'radicada'])));
        $this->assertSame(['En revisión', 'warning'], Labels::requestStatus(new CommitteeRequest(['estado' => 'en_revision'])));
        $this->assertSame(['Aprobada', 'success'], Labels::requestStatus(new CommitteeRequest(['estado' => 'con_decision', 'decision' => 'aprobada'])));
        $this->assertSame(['Rechazada', 'danger'], Labels::requestStatus(new CommitteeRequest(['estado' => 'con_decision', 'decision' => 'rechazada'])));
        $this->assertSame(['Aplazada', 'primary'], Labels::requestStatus(new CommitteeRequest(['estado' => 'con_decision', 'decision' => 'aplazada'])));
    }

    public function test_period_date_and_bytes_formatting(): void
    {
        $this->assertSame('2026-1', Labels::period(Carbon::parse('2026-06-30')));
        $this->assertSame('2026-2', Labels::period(Carbon::parse('2026-07-01')));
        $this->assertSame('12/09/2026', Labels::date(Carbon::parse('2026-09-12')));
        $this->assertSame('—', Labels::date(null));
        $this->assertSame('340 KB', Labels::bytes(348160));
        $this->assertSame('1,2 MB', Labels::bytes(1258291));
        $this->assertSame('Radicada', Labels::stateLabel('radicada'));
        $this->assertSame('En revisión', Labels::stateLabel('en_revision'));
        $this->assertSame('Con decisión', Labels::stateLabel('con_decision'));
    }
}
```

```php
<?php
// tests/Feature/NavigationRolesTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Support\Navigation;
use Tests\TestCase;

class NavigationRolesTest extends TestCase
{
    private function items(Role $role): array
    {
        return collect(Navigation::forRole($role, 'panel'))->pluck('items')->flatten(1)->keyBy('key')->all();
    }

    public function test_tutor_and_profesor_do_not_see_coordination_only_links(): void
    {
        foreach ([Role::Tutor, Role::Profesor] as $role) {
            $items = $this->items($role);
            $this->assertArrayNotHasKey('indicadores', $items);
            $this->assertArrayNotHasKey('usuarios', $items);
            $this->assertArrayNotHasKey('bitacora', $items);
            $this->assertSame('/solicitudes', $items['solicitudes']['href']);
        }
    }

    public function test_only_tutor_gets_the_tutor_extras(): void
    {
        $this->assertSame('/mis-asesorias', $this->items(Role::Tutor)['mis-asesorias']['href']);
        $this->assertSame('/mis-asesorias', $this->items(Role::Tutor)['registrar-tutoria']['href']);
        $this->assertArrayNotHasKey('mis-asesorias', $this->items(Role::Profesor));
        $this->assertArrayNotHasKey('mis-asesorias', $this->items(Role::Coordinacion));
    }

    public function test_coordinacion_keeps_the_full_menu_and_student_keeps_the_portal(): void
    {
        $coord = $this->items(Role::Coordinacion);
        $this->assertSame('/indicadores', $coord['indicadores']['href']);
        $this->assertArrayHasKey('usuarios', $coord);
        $this->assertSame('/solicitudes', $coord['solicitudes']['href']);

        $student = $this->items(Role::Estudiante);
        $this->assertSame('/solicitudes', $student['solicitudes']['href']);
        $this->assertArrayHasKey('practica', $student);
        $this->assertArrayNotHasKey('indicadores', $student);
    }

    public function test_active_item_is_marked(): void
    {
        $items = collect(Navigation::forRole(Role::Tutor, 'mis-asesorias'))->pluck('items')->flatten(1)->keyBy('key');

        $this->assertTrue($items['mis-asesorias']['active']);
        $this->assertFalse($items['panel']['active']);
    }
}
```

```php
<?php
// tests/Feature/Database/AcademicTermCurrentTest.php

namespace Tests\Feature\Database;

use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class AcademicTermCurrentTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    public function test_current_is_null_without_terms(): void
    {
        $this->assertNull(AcademicTerm::current());
    }

    public function test_current_falls_back_to_the_latest_term_when_no_record_exists(): void
    {
        $this->makeTerm('2025-2');
        $this->makeTerm('2026-1');
        AcademicTerm::where('codigo', '2026-1')->update(['fecha_inicio' => '2026-02-01']);
        AcademicTerm::where('codigo', '2025-2')->update(['fecha_inicio' => '2025-08-01']);

        $this->assertSame('2026-1', AcademicTerm::current()->codigo);
    }

    public function test_current_prefers_the_latest_term_that_has_academic_records(): void
    {
        $student = $this->makeStudent();
        $withRecords = $this->makeTerm('2026-1');
        $empty = $this->makeTerm('2026-2');
        $withRecords->update(['fecha_inicio' => '2026-02-01']);
        $empty->update(['fecha_inicio' => '2026-08-01']);
        AcademicRecord::create(['student_id' => $student->id, 'term_id' => $withRecords->id, 'papa' => '4.00']);

        $this->assertSame('2026-1', AcademicTerm::current()->codigo);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='LabelsTest|NavigationRolesTest|AcademicTermCurrentTest'`
Expected: FAIL (`Labels` no existe, `forRole` filtrado no existe, `AcademicTerm::current` no existe).

- [ ] **Step 3: Implement**

`config/program.php`:

```php
<?php

return [
    'papa_alert' => 3.0,
    'page_size' => 8,
    'total_credits' => 160,
    'tutorship_edit_hours' => 24,
    'attachment_max_kb' => 5120,
    'attachment_max_files' => 5,
    'tutorship_reasons' => [
        'Seguimiento a carga académica',
        'Orientación sobre modalidad de grado',
        'Revisión de avance',
        'Seguimiento de solicitud',
        'Alerta temprana',
        'Otro',
    ],
];
```

`config/app.php` línea 86: `'locale' => 'es',` (las fechas relativas de Carbon salen en español; los mensajes de validación que no tengan traducción caen al inglés por `fallback_locale`).

`app/Models/AcademicTerm.php` — añadir al final de la clase:

```php
    public static function current(): ?self
    {
        return static::query()->whereHas('academicRecords')->orderByDesc('fecha_inicio')->first()
            ?? static::query()->orderByDesc('fecha_inicio')->first();
    }
```

`app/Support/Labels.php`:

```php
<?php

namespace App\Support;

use App\Models\AcademicTerm;
use App\Models\CommitteeRequest;
use Carbon\CarbonInterface;

class Labels
{
    /** @return array{0: string, 1: string} */
    public static function studentStatus(string $estado): array
    {
        return match ($estado) {
            'activo' => ['Activo', 'success'],
            'en_riesgo' => ['En riesgo', 'danger'],
            'intercambio' => ['Intercambio', 'info'],
            'egresado' => ['Egresado', 'primary'],
            default => [ucfirst(str_replace('_', ' ', $estado)), 'info'],
        };
    }

    /** @return array{0: string, 1: string} */
    public static function requestStatus(CommitteeRequest $request): array
    {
        return match ($request->estado) {
            'radicada' => ['Radicada', 'info'],
            'en_revision' => ['En revisión', 'warning'],
            default => match ($request->decision) {
                'aprobada' => ['Aprobada', 'success'],
                'rechazada' => ['Rechazada', 'danger'],
                'aplazada' => ['Aplazada', 'primary'],
                default => ['Con decisión', 'success'],
            },
        };
    }

    public static function stateLabel(string $estado): string
    {
        return match ($estado) {
            'radicada' => 'Radicada',
            'en_revision' => 'En revisión',
            'con_decision' => 'Con decisión',
            default => ucfirst(str_replace('_', ' ', $estado)),
        };
    }

    public static function period(CarbonInterface $date): string
    {
        return $date->year.'-'.($date->month <= 6 ? 1 : 2);
    }

    public static function date(?CarbonInterface $date): string
    {
        return $date?->format('d/m/Y') ?? '—';
    }

    public static function termLabel(?AcademicTerm $term): string
    {
        return $term ? $term->codigo.'S' : 'sin periodo';
    }

    public static function bytes(int $bytes): string
    {
        return $bytes < 1048576
            ? round($bytes / 1024).' KB'
            : number_format($bytes / 1048576, 1, ',', '.').' MB';
    }
}
```

`app/Support/Navigation.php` — reemplazar el archivo completo:

```php
<?php

namespace App\Support;

use App\Enums\Role;

class Navigation
{
    public static function forRole(Role $role, string $active): array
    {
        return match ($role) {
            Role::Estudiante => self::studentPortal($active),
            Role::Tutor => self::staff($active, true),
            Role::Profesor => self::staff($active),
            Role::Coordinacion => self::coordinator($active),
        };
    }

    public static function coordinator(string $active): array
    {
        return [
            ['label' => 'GENERAL', 'items' => self::mark(self::coordinatorGeneralItems(), $active)],
            ['label' => 'ADMINISTRACIÓN', 'items' => self::mark(self::adminItems(), $active)],
        ];
    }

    public static function studentPortal(string $active): array
    {
        $items = [
            ['key' => 'panel', 'icon' => 'layout-dashboard', 'label' => 'Panel', 'href' => '/panel'],
            ['key' => 'perfil', 'icon' => 'user', 'label' => 'Mi perfil', 'href' => '#'],
            ['key' => 'solicitudes', 'icon' => 'file-text', 'label' => 'Mis solicitudes', 'href' => '/solicitudes'],
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

    private static function staff(string $active, bool $withTutorExtras = false): array
    {
        $items = array_values(array_filter(
            self::coordinatorGeneralItems(),
            static fn (array $item) => $item['key'] !== 'indicadores'
        ));

        $groups = [['label' => 'GENERAL', 'items' => self::mark($items, $active)]];

        if ($withTutorExtras) {
            $groups[] = [
                'label' => null,
                'items' => self::mark([
                    ['key' => 'registrar-tutoria', 'icon' => 'message-square', 'label' => 'Registrar tutoría', 'href' => '/mis-asesorias'],
                    ['key' => 'mis-asesorias', 'icon' => 'clipboard-list', 'label' => 'Mis asesorías', 'href' => '/mis-asesorias'],
                ], $active),
            ];
        }

        return $groups;
    }

    private static function coordinatorGeneralItems(): array
    {
        return [
            ['key' => 'panel', 'icon' => 'layout-dashboard', 'label' => 'Panel', 'href' => '/panel'],
            ['key' => 'estudiantes', 'icon' => 'users', 'label' => 'Estudiantes', 'href' => '/estudiantes'],
            ['key' => 'solicitudes', 'icon' => 'file-text', 'label' => 'Solicitudes', 'href' => '/solicitudes'],
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

- [ ] **Step 4: Run tests to verify they pass, then the full suite**

Run: `php artisan test --filter='LabelsTest|NavigationRolesTest|AcademicTermCurrentTest' && php artisan test && grep -rn "coordinatorWithTutorExtras" app tests`
Expected: PASS; suite completa en verde; `grep` sin resultados. (Los tests de `ShellUserTest` ya existentes siguen pasando: el tutor conserva "Registrar tutoría"/"Mis asesorías" y Coordinación sigue sin "Mis asesorías".)

- [ ] **Step 5: Commit**

```bash
git add config app tests
git commit -m "feat(nav): agregar configuración del programa, periodo vigente, etiquetas y menú filtrado por rol"
```

---

### Task 2: Componentes compartidos (campo con errores, filtro, paginación, aviso)

**Files:**
- Create: `resources/views/components/filter-select.blade.php`, `resources/views/components/pagination.blade.php`, `tests/Feature/FormComponentsTest.php`
- Modify: `resources/views/components/form-field.blade.php`, `resources/views/components/layouts/app.blade.php:19-21`

**Interfaces:**
- Produces:
  - `<x-form-field … :readonly="bool">`: muestra `old($name, $value)` (nunca en `password`), el primer error de `$errors` bajo el campo y borde de peligro.
  - `<x-filter-select name all :options="[value=>label]" :selected="…">`.
  - `<x-pagination :paginator="$p" noun="estudiantes" />`: texto "Mostrando A–B de N {noun}" + enlaces Anterior/páginas/Siguiente conservando la query string; "Sin resultados" si N = 0.
  - Layout: banner verde si existe `session('status')`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/FormComponentsTest.php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class FormComponentsTest extends TestCase
{
    private function withErrors(array $messages): void
    {
        View::share('errors', (new ViewErrorBag)->put('default', new MessageBag($messages)));
    }

    public function test_form_field_shows_its_error_and_danger_border(): void
    {
        $this->withErrors(['email' => ['Credenciales inválidas.']]);

        $html = Blade::render('<x-form-field label="Correo" name="email" />');

        $this->assertStringContainsString('Credenciales inválidas.', $html);
        $this->assertStringContainsString('border-[var(--color-danger)]', $html);
    }

    public function test_form_field_without_errors_keeps_the_default_border(): void
    {
        $this->withErrors([]);

        $html = Blade::render('<x-form-field label="Correo" name="email" />');

        $this->assertStringNotContainsString('border-[var(--color-danger)]', $html);
    }

    public function test_form_field_repopulates_old_input_but_never_passwords(): void
    {
        $this->withErrors([]);
        $this->app['request']->setLaravelSession($this->app['session.store']);
        $this->app['session.store']->flashInput(['email' => 'ana@unal.edu.co', 'password' => 'secreto']);

        $email = Blade::render('<x-form-field label="Correo" name="email" />');
        $password = Blade::render('<x-form-field label="Clave" name="password" type="password" />');

        $this->assertStringContainsString('value="ana@unal.edu.co"', $email);
        $this->assertStringNotContainsString('secreto', $password);
    }

    public function test_form_field_can_be_readonly_and_textarea_shows_old_text(): void
    {
        $this->withErrors([]);

        $this->assertStringContainsString('readonly', Blade::render('<x-form-field label="Periodo" name="periodo" value="2026-2" :readonly="true" />'));
        $this->assertStringContainsString('Texto previo', Blade::render('<x-form-field label="Descripción" name="d" type="textarea" value="Texto previo" />'));
    }

    public function test_filter_select_marks_the_selected_option(): void
    {
        $html = Blade::render('<x-filter-select name="estado" all="Estado: todos" :options="$options" selected="en_riesgo" />', [
            'options' => ['activo' => 'Activo', 'en_riesgo' => 'En riesgo'],
        ]);

        $this->assertStringContainsString('Estado: todos', $html);
        $this->assertMatchesRegularExpression('/<option value="en_riesgo"\s+selected/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="activo"\s+selected/', $html);
    }

    public function test_pagination_shows_range_and_keeps_the_query_string(): void
    {
        $paginator = (new LengthAwarePaginator(range(1, 8), 20, 8, 2, ['path' => '/estudiantes']))->appends(['q' => 'ana']);

        $html = Blade::render('<x-pagination :paginator="$p" noun="estudiantes" />', ['p' => $paginator]);

        $this->assertStringContainsString('Mostrando 9–16 de 20 estudiantes', $html);
        $this->assertStringContainsString('href="/estudiantes?q=ana&amp;page=1"', $html);
        $this->assertStringContainsString('href="/estudiantes?q=ana&amp;page=3"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function test_pagination_with_no_results_says_so(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 8, 1, ['path' => '/estudiantes']);

        $html = Blade::render('<x-pagination :paginator="$p" noun="estudiantes" />', ['p' => $paginator]);

        $this->assertStringContainsString('Sin resultados', $html);
        $this->assertStringNotContainsString('Siguiente', $html);
    }

    public function test_layout_shows_the_flash_status_banner(): void
    {
        session()->flash('status', 'Solicitud radicada correctamente.');

        $html = Blade::render('<x-layouts.app :user="$u" :nav-groups="[]">x</x-layouts.app>', [
            'u' => ['initials' => 'AR', 'name' => 'Ana', 'role' => 'Coordinación'],
        ]);

        $this->assertStringContainsString('Solicitud radicada correctamente.', $html);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=FormComponentsTest`
Expected: FAIL (componentes inexistentes / sin errores en `form-field`).

- [ ] **Step 3: Implement**

`resources/views/components/form-field.blade.php` — reemplazar el archivo:

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
    'readonly' => false,
])

@php
    $isTextarea = $type === 'textarea';
    $current = $type === 'password' ? null : old($name, $value);
    $error = isset($errors) ? $errors->first($name) : null;
    $border = $error ? 'border-[var(--color-danger)]' : 'border-[var(--border-default)]';
    $wrapperClasses = $isTextarea
        ? "flex w-full items-start gap-2 rounded-[var(--radius-md)] border {$border} bg-[var(--surface-card)] p-3 focus-within:border-[var(--focus-ring)]"
        : "flex h-[var(--control-height)] w-full items-center gap-2 rounded-[var(--radius-md)] border {$border} bg-[var(--surface-card)] px-3 focus-within:border-[var(--focus-ring)]";
@endphp

<div class="flex w-full flex-col gap-[6px]">
    <label for="{{ $name }}" class="text-xs font-semibold text-[var(--text-secondary)]">{{ $label }}</label>
    <div class="{{ $wrapperClasses }}">
        @if($type === 'select')
            <select id="{{ $name }}" name="{{ $name }}" class="w-full flex-1 appearance-none bg-transparent text-sm text-[var(--text-primary)] outline-none">
                {{ $slot }}
            </select>
        @elseif($isTextarea)
            <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" @if($readonly) readonly @endif class="min-h-[80px] w-full flex-1 resize-none bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]">{{ $current }}</textarea>
        @else
            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" placeholder="{{ $placeholder }}" value="{{ $current }}" @if($readonly) readonly @endif class="w-full flex-1 bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
        @endif
        @if($icon)
            <x-dynamic-component :component="'lucide-' . $icon" class="h-4 w-4 shrink-0 text-[var(--color-neutral-400)]" />
        @endif
    </div>
    @if($error)
        <p class="text-[11px] font-medium text-[var(--color-danger)]">{{ $error }}</p>
    @endif
    @if($help)
        <p class="text-[11px] text-[var(--text-muted)]">{{ $help }}</p>
    @endif
</div>
```

`resources/views/components/filter-select.blade.php`:

```blade
@props(['name', 'all', 'options', 'selected' => null])

<select name="{{ $name }}" aria-label="{{ $all }}" class="h-[var(--control-height)] rounded-[var(--radius-md)] border border-[var(--border-default)] bg-[var(--surface-card)] px-3 text-sm text-[var(--text-secondary)]">
    <option value="">{{ $all }}</option>
    @foreach($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
```

`resources/views/components/pagination.blade.php`:

```blade
@props(['paginator', 'noun' => 'registros'])

@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = $paginator->total() > 0 ? range(max(1, $current - 2), min($last, $current + 2)) : [];
@endphp

<nav aria-label="Paginación" class="flex flex-wrap items-center gap-x-2 gap-y-2">
    <span class="flex-1 text-sm text-[var(--text-secondary)]">
        @if($paginator->total() === 0)
            Sin resultados
        @else
            Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }} {{ $noun }}
        @endif
    </span>
    @if($paginator->hasPages())
        @if($paginator->onFirstPage())
            <span class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-muted)]">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)] hover:bg-[var(--surface-sunken)]">Anterior</a>
        @endif
        @foreach($pages as $page)
            @if($page === $current)
                <span aria-current="page" class="rounded-[var(--radius-md)] bg-[var(--color-primary-600)] px-3 py-[7px] text-sm text-white">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)] hover:bg-[var(--surface-sunken)]">{{ $page }}</a>
            @endif
        @endforeach
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-secondary)] hover:bg-[var(--surface-sunken)]">Siguiente</a>
        @else
            <span class="rounded-[var(--radius-md)] px-3 py-[7px] text-sm text-[var(--text-muted)]">Siguiente</span>
        @endif
    @endif
</nav>
```

`resources/views/components/layouts/app.blade.php` — dentro de `<main …>` antes de `{{ $slot }}`:

```blade
                @if(session('status'))
                    <div role="status" class="mx-4 mt-4 flex items-center gap-2 rounded-[var(--radius-md)] bg-[var(--color-success-bg)] p-3 text-sm font-medium text-[var(--color-success)] sm:mx-7 sm:mt-7">
                        <x-lucide-circle-check class="h-4 w-4 shrink-0" />
                        {{ session('status') }}
                    </div>
                @endif
```

- [ ] **Step 4: Run tests to verify they pass, then the full suite**

Run: `php artisan test --filter=FormComponentsTest && php artisan test`
Expected: PASS; suite completa en verde (el test de `x-form-field` select existente y el login siguen pasando).

- [ ] **Step 5: Commit**

```bash
git add resources tests
git commit -m "feat(ui): agregar errores en campos, filtro, paginación y aviso de estado"
```

---

### Task 3: Datos demo de acompañamiento y helper de pruebas con usuarios sembrados

**Files:**
- Modify: `database/seeders/DemoSeeder.php`, `tests/TestCase.php`, `tests/Feature/Database/SeederTest.php`, `tests/Concerns/CreatesStudents.php`

**Interfaces:**
- Produces:
  - `TestCase::signInSeeded(Role $role): User` (usuario sembrado `<rol>@unal.edu.co`, `actingAs`).
  - `CreatesStudents::makeRequest(Student $student, string $tipo = 'Cancelación de asignatura', array $attrs = []): CommitteeRequest`.
  - Datos demo: el tutor `tutor@unal.edu.co` tiene asignados 2020123, 2021045, 2021098, 2022011 y 2022076 en 2026-2; tutorías de Laura (03/07/2026), Mateo (20/05/2026), Diana (01/09/2026) y Sofía (28/08/2026); alertas abiertas de Juan, Laura y Andrés y una atendida de Diana.

- [ ] **Step 1: Write the failing test** (añadir a `tests/Feature/Database/SeederTest.php`)

```php
    public function test_demo_data_includes_the_tutor_assignments_and_alerts_used_by_the_screens(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tutor = User::where('email', 'tutor@unal.edu.co')->firstOrFail();
        $term = AcademicTerm::where('codigo', '2026-2')->firstOrFail();

        $this->assertSame(5, \App\Models\TutorAssignment::where('tutor_id', $tutor->id)->where('term_id', $term->id)->count());
        $this->assertSame(3, \App\Models\Alert::where('atendida', false)->count());
        $this->assertSame(4, \App\Models\Alert::count());
        $this->assertSame(1, Student::where('codigo', '2021098')->firstOrFail()->tutorships()->count());
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_demo_data_includes`
Expected: FAIL (0 asignaciones, 1 alerta).

- [ ] **Step 3: Implement**

En `DemoSeeder::run()`, antes de la llamada a `seedRequests`, añadir `$this->seedAccompaniment($students, $term, $tutor);` y el método:

```php
    private function seedAccompaniment(array $students, AcademicTerm $term, User $tutor): void
    {
        foreach (['2021045', '2021098', '2022011', '2022076'] as $codigo) {
            TutorAssignment::updateOrCreate(
                ['student_id' => $students[$codigo]->id, 'term_id' => $term->id],
                ['tutor_id' => $tutor->id]
            );
        }

        foreach ([
            ['2021045', '2026-07-03', 'Seguimiento de solicitud', 'Se revisó el trámite de reingreso.'],
            ['2021098', '2026-05-20', 'Alerta temprana', 'PAPA por debajo del umbral; se acordó plan de estudio.'],
            ['2022011', '2026-09-01', 'Revisión de avance', 'Avance acorde con el plan.'],
            ['2022076', '2026-08-28', 'Seguimiento a carga académica', 'Mantiene 18 créditos.'],
        ] as [$codigo, $fecha, $motivo, $observaciones]) {
            Tutorship::updateOrCreate(
                ['student_id' => $students[$codigo]->id, 'tutor_id' => $tutor->id, 'fecha' => $fecha],
                ['motivo' => $motivo, 'observaciones' => $observaciones]
            );
        }

        foreach ([
            ['2021045', 'solicitudes_recurrentes', 'Tercera solicitud del mismo tipo en el periodo', '2026-09-10', false],
            ['2019087', 'matricula_sin_avance', 'Matrícula sin avance en créditos', '2026-09-05', false],
            ['2022011', 'sin_tutoria', 'Sin registro de tutoría en dos periodos', '2026-09-01', true],
        ] as [$codigo, $tipo, $detalle, $fecha, $atendida]) {
            Alert::updateOrCreate(
                ['student_id' => $students[$codigo]->id, 'tipo' => $tipo],
                ['detalle' => $detalle, 'fecha_deteccion' => $fecha, 'atendida' => $atendida]
            );
        }
    }
```

`tests/TestCase.php` — añadir método:

```php
    protected function signInSeeded(Role $role): User
    {
        $user = User::where('email', $role->value.'@unal.edu.co')->firstOrFail();
        $this->actingAs($user);

        return $user;
    }
```

`tests/Concerns/CreatesStudents.php` — añadir imports `use App\Models\CommitteeRequest; use App\Models\RequestType;` y método:

```php
    protected function makeRequest(Student $student, string $tipo = 'Cancelación de asignatura', array $attrs = []): CommitteeRequest
    {
        return CommitteeRequest::create($attrs + [
            'student_id' => $student->id,
            'request_type_id' => RequestType::firstOrCreate(['nombre' => $tipo])->id,
            'term_id' => $this->makeTerm()->id,
            'descripcion' => str_repeat('Solicito la cancelación de la asignatura. ', 3),
            'estado' => 'radicada',
            'fecha_radicacion' => now(),
        ]);
    }
```

- [ ] **Step 4: Run tests to verify they pass, luego re-sembrar la base de desarrollo**

Run: `php artisan test --filter=SeederTest && php artisan test && php artisan migrate:fresh --seed`
Expected: PASS; suite completa en verde; el seed corre sin errores.

- [ ] **Step 5: Commit**

```bash
git add database tests
git commit -m "feat(db): ampliar datos demo de acompañamiento y agregar helper de pruebas con usuarios sembrados"
```

---

### Task 4: Listado de estudiantes con datos reales (búsqueda, filtros, paginación)

**Files:**
- Create: `tests/Feature/StudentIndexTest.php`, `tests/Feature/EmptyDatabaseScreensTest.php`
- Modify: `app/Http/Controllers/StudentController.php` (quitar `STUDENTS` y reescribir `index()`; `show()` no cambia en esta tarea), `resources/views/students/index.blade.php`, `tests/Feature/NavigationTest.php`, `tests/Feature/ShellUserTest.php`, `tests/Feature/StudentTest.php` (borrar los 2 tests de `index`)

**Interfaces:**
- Consumes: `AcademicTerm::current()`, `Labels::studentStatus()`, `config('program.*')`, componentes `x-filter-select`, `x-pagination`, `TestCase::signInSeeded()`.
- Produces: `GET /estudiantes?q=&cohorte=&estado=&tutor=&page=` (profesor, tutor, coordinación); la vista recibe `students` (paginador de arrays con claves `id, code, name, cohort, papa, papaLow, advance, status, tone`), `filters`, `cohortOptions`, `statusOptions`, `tutorOptions`, `activeCount`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/StudentIndexTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);
    }

    public function test_roster_shows_the_seeded_students_with_status_and_progress(): void
    {
        $response = $this->get('/estudiantes');

        $response->assertOk();
        $response->assertSee('Ruiz Salazar, Andrés');
        $response->assertSee('Valencia Toro, Ana');
        $response->assertSee('6 estudiantes activos en el programa');
        $response->assertSee('Mostrando 1–8 de 8 estudiantes');
        $response->assertSee('En riesgo');
        $response->assertSee('Intercambio');
        $response->assertSee('width: 88%', false);
    }

    public function test_low_papa_is_highlighted_and_links_go_to_the_profile_by_id(): void
    {
        $juan = Student::where('codigo', '2020123')->firstOrFail();

        $response = $this->get('/estudiantes');

        $response->assertSee('font-semibold text-[var(--color-danger)]', false);
        $response->assertSee('href="'.route('students.show', $juan).'"', false);
    }

    public function test_search_matches_name_code_and_document_case_insensitively(): void
    {
        $this->get('/estudiantes?q=gómez')->assertSee('Gómez Ruiz, Laura')->assertDontSee('Ruiz Salazar, Andrés');
        $this->get('/estudiantes?q=2021098')->assertSee('Cardona Vélez, Mateo')->assertDontSee('Gómez Ruiz, Laura');
        $this->get('/estudiantes?q=102019087')->assertSee('Ruiz Salazar, Andrés')->assertDontSee('Cardona Vélez, Mateo');
    }

    public function test_like_wildcards_in_the_search_are_treated_literally(): void
    {
        $this->get('/estudiantes?q=%25')->assertSee('No hay estudiantes')->assertDontSee('Ruiz Salazar, Andrés');
        $this->get('/estudiantes?q=_')->assertSee('No hay estudiantes')->assertDontSee('Ruiz Salazar, Andrés');
    }

    public function test_filters_by_status_cohort_and_tutor_combine(): void
    {
        $tutor = User::where('email', 'tutor@unal.edu.co')->firstOrFail();

        $this->get('/estudiantes?estado=en_riesgo')->assertSee('Cardona Vélez, Mateo')->assertDontSee('Pérez Rojas, Juan');
        $this->get('/estudiantes?cohorte=2021-1')->assertSee('Gómez Ruiz, Laura')->assertDontSee('Cardona Vélez, Mateo');
        $this->get('/estudiantes?tutor='.$tutor->id)->assertSee('Mostrando 1–5 de 5 estudiantes')->assertDontSee('Ruiz Salazar, Andrés');
        $this->get('/estudiantes?estado=activo&tutor='.$tutor->id)->assertSee('Mostrando 1–4 de 4 estudiantes')->assertDontSee('Cardona Vélez, Mateo');
    }

    public function test_pagination_keeps_the_active_filters(): void
    {
        config(['program.page_size' => 3]);

        $response = $this->get('/estudiantes?estado=activo&page=2');

        $response->assertSee('Mostrando 4–6 de 6 estudiantes');
        $response->assertSee('estado=activo', false);
    }

    public function test_array_query_parameters_do_not_break_the_page(): void
    {
        $this->get('/estudiantes?q[]=x&cohorte[]=y&estado[]=z&tutor[]=1')->assertOk();
    }

    public function test_only_staff_roles_can_open_the_roster(): void
    {
        $this->signInSeeded(Role::Estudiante);
        $this->get('/estudiantes')->assertForbidden();

        foreach ([Role::Profesor, Role::Tutor] as $role) {
            $this->signInSeeded($role);
            $this->get('/estudiantes')->assertOk();
        }
    }
}
```

```php
<?php
// tests/Feature/EmptyDatabaseScreensTest.php

namespace Tests\Feature;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmptyDatabaseScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_roster_renders_with_an_empty_database(): void
    {
        $this->actingAsRole(Role::Coordinacion);

        $this->get('/estudiantes')->assertOk()->assertSee('No hay estudiantes')->assertSee('0 estudiantes activos');
    }
}
```

Actualizar `tests/Feature/NavigationTest.php` (reemplazar el archivo):

```php
<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_internal_screen_links_to_the_other_implemented_screens(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);
        $juan = Student::where('codigo', '2020123')->firstOrFail();

        $this->get('/panel')->assertSee('href="/estudiantes"', false);

        $students = $this->get('/estudiantes');
        $students->assertSee('href="'.route('students.show', $juan).'"', false);
        $students->assertSee('href="/indicadores"', false);

        $this->get('/solicitudes/nueva')->assertSee('href="/panel"', false);
    }

    public function test_all_screens_return_200(): void
    {
        $this->seed(DatabaseSeeder::class);
        $juan = Student::where('codigo', '2020123')->firstOrFail();

        $this->get('/login')->assertOk();

        $this->signInSeeded(Role::Coordinacion);
        foreach (['/panel', '/estudiantes', '/estudiantes/'.$juan->id, '/solicitudes/nueva', '/indicadores'] as $route) {
            $this->get($route)->assertOk();
        }
    }
}
```

En `tests/Feature/ShellUserTest.php` añadir `use Illuminate\Foundation\Testing\RefreshDatabase;` y `use RefreshDatabase;` dentro de la clase (ahora las pantallas consultan la BD). En `tests/Feature/StudentTest.php` borrar `test_students_index_renders_table_and_filters` y su import no usado si queda (los tests de `show` se reemplazan en la Task 5).

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='StudentIndexTest|EmptyDatabaseScreensTest|NavigationTest'`
Expected: FAIL (el listado sigue mostrando datos mock: sin filtros, sin ids, "312").

- [ ] **Step 3: Implement**

`app/Http/Controllers/StudentController.php` — añadir imports `App\Models\AcademicTerm`, `App\Models\Student`, `App\Models\User`, `App\Support\Labels`, `Illuminate\Http\Request`; eliminar la constante `STUDENTS` y reemplazar `index()`:

```php
    public function index(Request $request): View
    {
        $user = $request->user();
        $term = AcademicTerm::current();
        $filters = [
            'q' => $request->string('q')->trim()->toString(),
            'cohorte' => $request->string('cohorte')->toString(),
            'estado' => $request->string('estado')->toString(),
            'tutor' => $request->integer('tutor'),
        ];

        $students = Student::query()
            ->select('students.*')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->with(['user', 'academicRecords' => fn ($q) => $q->where('term_id', $term?->id)])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%'.addcslashes($filters['q'], '\\%_').'%';
                $query->where(fn ($w) => $w
                    ->where('users.name', 'ilike', $like)
                    ->orWhere('students.codigo', 'ilike', $like)
                    ->orWhere('students.documento', 'ilike', $like));
            })
            ->when($filters['cohorte'] !== '', fn ($q) => $q->where('students.cohorte', $filters['cohorte']))
            ->when($filters['estado'] !== '', fn ($q) => $q->where('students.estado', $filters['estado']))
            ->when($filters['tutor'] > 0 && $term, fn ($q) => $q->whereExists(fn ($e) => $e
                ->selectRaw('1')
                ->from('tutor_assignments')
                ->whereColumn('tutor_assignments.student_id', 'students.id')
                ->where('tutor_assignments.term_id', $term->id)
                ->where('tutor_assignments.tutor_id', $filters['tutor'])))
            ->orderBy('users.name')
            ->paginate(config('program.page_size'))
            ->withQueryString();

        return view('students.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'estudiantes'),
            'students' => $students->through(fn (Student $student) => $this->row($student)),
            'filters' => $filters,
            'cohortOptions' => Student::query()->distinct()->orderBy('cohorte')->pluck('cohorte', 'cohorte'),
            'statusOptions' => ['activo' => 'Activo', 'en_riesgo' => 'En riesgo', 'intercambio' => 'Intercambio', 'egresado' => 'Egresado'],
            'tutorOptions' => User::where('role', 'tutor')->orderBy('name')->pluck('name', 'id'),
            'activeCount' => Student::where('estado', 'activo')->count(),
        ]);
    }

    private function row(Student $student): array
    {
        $record = $student->academicRecords->first();
        [$status, $tone] = Labels::studentStatus($student->estado);
        $papa = $record ? (float) $record->papa : null;

        return [
            'id' => $student->id,
            'code' => $student->codigo,
            'name' => $student->user->name,
            'cohort' => $student->cohorte,
            'papa' => $papa === null ? '—' : number_format($papa, 1),
            'papaLow' => $papa !== null && $papa < config('program.papa_alert'),
            'advance' => $record ? (int) round((float) $record->porcentaje_avance) : 0,
            'status' => $status,
            'tone' => $tone,
        ];
    }
```

`resources/views/students/index.blade.php` — reemplazar el archivo:

```blade
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Estudiantes · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Estudiantes</h1>
                <p class="text-sm text-[var(--text-secondary)]">{{ $activeCount }} estudiantes activos en el programa · RF-07</p>
            </div>
            <div class="flex gap-3">
                <x-button variant="subtle" icon="download">Exportar</x-button>
                <x-button variant="primary" icon="upload">Cargar Excel</x-button>
            </div>
        </div>

        <form method="GET" action="{{ route('students.index') }}" class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[14px] lg:flex-row lg:items-center">
            <div class="flex h-[var(--control-height)] flex-1 items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3">
                <x-lucide-search class="h-4 w-4 text-[var(--color-neutral-400)]" />
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por nombre, código o documento" class="w-full bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
            </div>
            <x-filter-select name="cohorte" all="Cohorte: todas" :options="$cohortOptions" :selected="$filters['cohorte']" />
            <x-filter-select name="estado" all="Estado: todos" :options="$statusOptions" :selected="$filters['estado']" />
            <x-filter-select name="tutor" all="Tutor: todos" :options="$tutorOptions" :selected="$filters['tutor']" />
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </form>

        <!-- Desktop table (hidden on mobile) -->
        <div class="hidden min-w-0 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] sm:flex">
            <div class="min-w-0 overflow-x-auto">
                <div class="flex min-w-[620px] gap-3 px-4 py-[11px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                    <span class="w-24">CÓDIGO</span>
                    <span class="flex-1">ESTUDIANTE</span>
                    <span class="w-[92px]">COHORTE</span>
                    <span class="w-16">PAPA</span>
                    <span class="w-[150px]">AVANCE</span>
                    <span class="w-[110px]">ESTADO</span>
                    <span class="w-[76px]"></span>
                </div>
                @forelse($students as $student)
                    <div class="flex min-w-[620px] items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm">
                        <span class="w-24 text-[var(--text-secondary)]">{{ $student['code'] }}</span>
                        <span class="flex-1 text-[var(--text-primary)]">{{ $student['name'] }}</span>
                        <span class="w-[92px] text-[var(--text-secondary)]">{{ $student['cohort'] }}</span>
                        <span class="w-16 {{ $student['papaLow'] ? 'font-semibold text-[var(--color-danger)]' : 'text-[var(--text-secondary)]' }}">{{ $student['papa'] }}</span>
                        <span class="flex w-[150px] items-center gap-2">
                            <span class="h-[7px] flex-1 rounded-full bg-[var(--surface-sunken)]">
                                <span class="block h-[7px] rounded-full bg-[var(--color-primary-500)]" style="width: {{ $student['advance'] }}%"></span>
                            </span>
                            <span class="text-xs text-[var(--text-secondary)]">{{ $student['advance'] }}%</span>
                        </span>
                        <span class="w-[110px]"><x-status-badge :text="$student['status']" :tone="$student['tone']" /></span>
                        <a href="{{ route('students.show', $student['id']) }}" class="w-[76px] text-sm font-medium text-[var(--color-primary-600)]">Ver ficha</a>
                    </div>
                @empty
                    <div class="border-t border-[var(--border-subtle)] px-4 py-8 text-center text-sm text-[var(--text-muted)]">No hay estudiantes que coincidan con los filtros.</div>
                @endforelse
            </div>
        </div>

        <!-- Mobile cards (hidden on desktop), per the 360px mockup -->
        <div class="flex flex-col gap-3 sm:hidden">
            @forelse($students as $student)
                <a href="{{ route('students.show', $student['id']) }}" class="flex flex-col gap-2 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-3">
                    <div class="flex items-center gap-2">
                        <span class="flex-1 text-sm font-medium text-[var(--text-primary)]">{{ $student['name'] }}</span>
                        <x-status-badge :text="$student['status']" :tone="$student['tone']" />
                    </div>
                    <p class="text-xs text-[var(--text-secondary)]">{{ $student['code'] }} · PAPA {{ $student['papa'] }} · avance {{ $student['advance'] }}%</p>
                    <span class="block h-[6px] rounded-full bg-[var(--surface-sunken)]">
                        <span class="block h-[6px] rounded-full bg-[var(--color-primary-500)]" style="width: {{ $student['advance'] }}%"></span>
                    </span>
                </a>
            @empty
                <p class="rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-4 text-center text-sm text-[var(--text-muted)]">No hay estudiantes que coincidan con los filtros.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$students" noun="estudiantes" />
    </div>
</x-layouts.app>
```

- [ ] **Step 4: Run tests to verify they pass, then the full suite**

Run: `php artisan test --filter='StudentIndexTest|EmptyDatabaseScreensTest|NavigationTest' && php artisan test`
Expected: PASS; suite completa en verde (`StudentTest` conserva solo los tests de `show` con datos mock hasta la Task 5).

- [ ] **Step 5: Commit**

```bash
git add app resources tests
git commit -m "feat(students): conectar listado de estudiantes a la bd con búsqueda, filtros y paginación"
```

---

### Task 5: Ficha del estudiante con datos reales

**Files:**
- Create: `app/Support/StudentProfile.php`, `tests/Feature/StudentProfileTest.php`
- Modify: `app/Http/Controllers/StudentController.php` (`show()`), `routes/web.php` (parámetro `{student}` numérico), `resources/views/students/show.blade.php`, `tests/Feature/AuthTest.php`, `tests/Feature/ShellUserTest.php`
- Delete: `tests/Feature/StudentTest.php`

**Interfaces:**
- Consumes: `Labels`, `AcademicTerm::current()`, relaciones de `Student`.
- Produces: `StudentProfile::header(Student): array{id,initials,name,code,cohort,enrollment,tutor,papa}` (reutilizada en Tasks 8 y 11) y `StudentProfile::build(Student, ?User $viewer = null): array` con claves `student, tabs, papaChart, timeline, processes, progress, tutorials`; `GET /estudiantes/{student}` (id numérico; 404 si no existe o no es numérico).

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/StudentProfileTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class StudentProfileTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    public function test_profile_shows_the_real_data_of_a_student(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Tutor);
        $juan = Student::where('codigo', '2020123')->firstOrFail();

        $response = $this->get('/estudiantes/'.$juan->id);

        $response->assertOk();
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Código 2020123');
        $response->assertSee('Cohorte 2020-1');
        $response->assertSee('7.ª matrícula');
        $response->assertSee('Tutor asignado: Carlos Pineda');
        $response->assertSee('2020-1');
        $response->assertSee('2026-2');
        $response->assertSee('3.4');
        $response->assertSee('4.1');
        $response->assertSee('Semillero de investigación SIGMA');
        $response->assertSee('Trabajo de grado · modalidad pasantia');
        $response->assertSee('Práctica empresarial · Softlogic S.A.S.');
        $response->assertSee('115 de 160');
        $response->assertSee('Promedio anterior');
        $response->assertSee('Seguimiento a carga académica');
        $response->assertSee('Información restringida');
    }

    public function test_a_student_without_any_related_data_renders_empty_states(): void
    {
        $this->actingAsRole(Role::Coordinacion);
        $student = $this->makeStudent('2030001');

        $response = $this->get('/estudiantes/'.$student->id);

        $response->assertOk();
        $response->assertSee('Sin tutor asignado');
        $response->assertSee('Sin matrícula registrada');
        $response->assertSee('Sin experiencias registradas.');
        $response->assertSee('Sin procesos en curso.');
        $response->assertSee('Sin tutorías registradas.');
    }

    public function test_deleted_tutorships_are_not_listed(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);
        $juan = Student::where('codigo', '2020123')->firstOrFail();
        $juan->tutorships()->where('motivo', 'Revisión de avance')->firstOrFail()->delete();

        $this->get('/estudiantes/'.$juan->id)
            ->assertSee('Seguimiento a carga académica')
            ->assertDontSee('Sin novedades; continúa según plan.');
    }

    public function test_unknown_or_non_numeric_ids_return_404(): void
    {
        $this->actingAsRole(Role::Coordinacion);

        $this->get('/estudiantes/999999')->assertNotFound();
        $this->get('/estudiantes/abc')->assertNotFound();
    }

    public function test_students_cannot_open_profiles(): void
    {
        $this->seed(DatabaseSeeder::class);
        $juan = Student::where('codigo', '2020123')->firstOrFail();
        $this->signInSeeded(Role::Estudiante);

        $this->get('/estudiantes/'.$juan->id)->assertForbidden();
    }
}
```

Actualizar `tests/Feature/AuthTest.php::test_role_matrix_returns_403_on_foreign_routes_and_200_on_own`: al inicio del método añadir `$this->seed(\Database\Seeders\DatabaseSeeder::class); $juan = \App\Models\Student::where('codigo', '2020123')->firstOrFail();` y cambiar la fila `['/estudiantes/2020123', …]` por `['/estudiantes/'.$juan->id, [Role::Profesor, Role::Tutor, Role::Coordinacion]]`. En `tests/Feature/ShellUserTest.php::test_every_screen_uses_the_session_user_not_the_mock_one` añadir el seed y usar `'/estudiantes/'.Student::where('codigo', '2020123')->firstOrFail()->id` en lugar de `/estudiantes/2020123`.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=StudentProfileTest`
Expected: FAIL (la ficha muestra siempre el mismo perfil mock).

- [ ] **Step 3: Implement**

`app/Support/StudentProfile.php`:

```php
<?php

namespace App\Support;

use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentProfile
{
    private const PAPA_BAR_HEIGHT = 150;

    /** @var array<string, array{0: string, 1: string}> */
    private const CATEGORY_STYLE = [
        'Investigación' => ['sparkles', 'primary'],
        'Vinculación' => ['book-open', 'accent'],
        'Pasantía' => ['plane', 'restricted'],
        'Eventos y publicaciones' => ['mic', 'warning'],
        'Deporte y cultura' => ['trophy', 'success'],
        'Programa' => ['star', 'info'],
    ];

    public static function header(Student $student): array
    {
        $student->loadMissing('user');
        $latest = self::records($student)->last();
        $term = AcademicTerm::current();
        $tutor = $term
            ? $student->tutorAssignments()->where('term_id', $term->id)->with('tutor')->first()?->tutor
            : null;

        return [
            'id' => $student->id,
            'initials' => $student->user?->initials() ?? strtoupper(substr($student->codigo, 0, 2)),
            'name' => $student->user?->name ?? $student->codigo,
            'code' => 'Código '.$student->codigo,
            'cohort' => 'Cohorte '.$student->cohorte,
            'enrollment' => $latest?->numero_matricula ? $latest->numero_matricula.'.ª matrícula' : 'Sin matrícula registrada',
            'tutor' => $tutor ? 'Tutor asignado: '.$tutor->name : 'Sin tutor asignado',
            'papa' => $latest ? number_format((float) $latest->papa, 1) : '—',
        ];
    }

    public static function build(Student $student, ?User $viewer = null): array
    {
        $records = self::records($student);
        $latest = $records->last();
        $previous = $records->count() > 1 ? $records[$records->count() - 2] : null;

        return [
            'student' => self::header($student),
            'tabs' => ['General', 'Académica', 'Experiencias', 'Trabajo de grado', 'Práctica', 'Solicitudes', 'Tutorías'],
            'papaChart' => $records->map(fn (AcademicRecord $r) => [
                'period' => $r->term->codigo,
                'value' => number_format((float) $r->papa, 1),
                'height' => (int) round((float) $r->papa / 5 * self::PAPA_BAR_HEIGHT),
            ])->all(),
            'timeline' => self::timeline($student),
            'processes' => self::processes($student),
            'progress' => [
                'percent' => $latest ? (int) round((float) $latest->porcentaje_avance) : 0,
                'details' => [
                    ['label' => 'Créditos cursados', 'value' => $latest ? $latest->creditos_cursados.' de '.config('program.total_credits') : '—'],
                    ['label' => 'Cupo del periodo', 'value' => $latest?->cupo_creditos ? $latest->cupo_creditos.' créditos' : '—'],
                    ['label' => 'PAPA actual', 'value' => $latest ? number_format((float) $latest->papa, 1) : '—'],
                    ['label' => 'Promedio anterior', 'value' => $previous ? number_format((float) $previous->papa, 1) : '—'],
                ],
            ],
            'tutorials' => $student->tutorships()->latest('fecha')->limit(3)->get()->map(fn ($t) => [
                'date' => Labels::date($t->fecha),
                'reason' => $t->motivo,
                'note' => (string) $t->observaciones,
            ])->all(),
        ];
    }

    private static function records(Student $student): Collection
    {
        return $student->academicRecords()->with('term')->get()
            ->sortBy(fn (AcademicRecord $r) => $r->term->fecha_inicio)
            ->values();
    }

    private static function timeline(Student $student): array
    {
        return $student->experiences()->with('type')->orderBy('fecha_inicio')->get()->map(function ($e) {
            [$icon, $tone] = self::CATEGORY_STYLE[$e->type->categoria] ?? ['sparkles', 'primary'];

            return [
                'icon' => $icon,
                'description' => $e->nombre,
                'period' => Labels::period($e->fecha_inicio),
                'tag' => $e->validada ? $e->type->categoria : 'Pendiente de verificación',
                'tone' => $e->validada ? $tone : 'warning',
            ];
        })->all();
    }

    private static function processes(Student $student): array
    {
        $processes = [];

        foreach ($student->theses()->with(['director', 'term'])->get() as $thesis) {
            [$status, $tone] = match ($thesis->estado) {
                'en_desarrollo' => ['En desarrollo', 'warning'],
                'finalizado' => ['Finalizado', 'success'],
                default => [ucfirst(str_replace('_', ' ', $thesis->estado)), 'info'],
            };
            $processes[] = [
                'title' => 'Trabajo de grado · modalidad '.$thesis->modalidad,
                'meta' => 'Director: '.($thesis->director ? 'Prof. '.$thesis->director->name : 'sin asignar').' · '.$thesis->term->codigo.'S',
                'status' => $status,
                'tone' => $tone,
            ];
        }

        foreach ($student->internships()->with(['company', 'asesor'])->get() as $internship) {
            $finished = $internship->fecha_fin && $internship->fecha_fin->isPast();
            $processes[] = [
                'title' => 'Práctica empresarial · '.$internship->company->nombre,
                'meta' => 'Asesor: '.($internship->asesor ? 'Prof. '.$internship->asesor->name : 'sin asignar')
                    .' · '.$internship->fecha_inicio->isoFormat('MMM YYYY').' – '
                    .($internship->fecha_fin ? $internship->fecha_fin->isoFormat('MMM YYYY') : 'en curso'),
                'status' => $finished ? 'Finalizada' : 'En curso',
                'tone' => $finished ? 'success' : 'info',
            ];
        }

        return $processes;
    }
}
```

`app/Http/Controllers/StudentController.php` — reemplazar `show()` (y quitar imports/usos de datos mock); añadir `use App\Support\StudentProfile;`:

```php
    public function show(Request $request, Student $student): View
    {
        $user = $request->user();

        return view('students.show', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'estudiantes'),
            ...StudentProfile::build($student, $user),
        ]);
    }
```

`routes/web.php` — la ruta de la ficha pasa a `{student}` numérico:

```php
        Route::get('/estudiantes/{student}', [StudentController::class, 'show'])->whereNumber('student')->name('students.show');
```

`resources/views/students/show.blade.php` — estados vacíos (3 inserciones):

1. Dentro de `<div class="flex flex-col border-t border-[var(--border-subtle)] px-4 pb-4">` (timeline), justo antes de `@foreach($timeline as $item)`:
```blade
                        @if(empty($timeline))
                            <p class="py-3 text-sm text-[var(--text-muted)]">Sin experiencias registradas.</p>
                        @endif
```
2. Dentro de `<div class="flex flex-col gap-[10px] border-t border-[var(--border-subtle)] p-4">` de "Procesos en curso", antes de `@foreach($processes as $process)`:
```blade
                        @if(empty($processes))
                            <p class="text-sm text-[var(--text-muted)]">Sin procesos en curso.</p>
                        @endif
```
3. Dentro del bloque "Últimas tutorías", antes de `@foreach($tutorials as $tutorial)`:
```blade
                        @if(empty($tutorials))
                            <p class="text-sm text-[var(--text-muted)]">Sin tutorías registradas.</p>
                        @endif
```
Y en el gráfico de PAPA, antes de `@foreach($papaChart as $bar)`: `@if(empty($papaChart))<p class="text-sm text-[var(--text-muted)]">Sin historia académica registrada.</p>@endif`. El botón "Registrar tutoría" no cambia en esta tarea (se conecta en la Task 11).

Borrar `tests/Feature/StudentTest.php`.

- [ ] **Step 4: Run tests to verify they pass, then the full suite**

Run: `php artisan test --filter='StudentProfileTest|AuthTest|ShellUserTest' && php artisan test`
Expected: PASS; suite completa en verde.

- [ ] **Step 5: Commit**

```bash
git add app routes resources tests
git commit -m "feat(students): conectar la ficha del estudiante a la bd con estados vacíos"
```

---

### Task 6: Panel e Indicadores calculados desde la BD

**Files:**
- Create: `app/Support/ProgramMetrics.php`, `tests/Feature/ProgramMetricsTest.php`
- Modify: `app/Http/Controllers/PanelController.php`, `app/Http/Controllers/IndicatorController.php`, `resources/views/panel/index.blade.php`, `resources/views/indicators/index.blade.php`, `tests/Feature/PanelTest.php`, `tests/Feature/IndicatorTest.php`, `tests/Feature/EmptyDatabaseScreensTest.php`

**Interfaces:**
- Consumes: `AcademicTerm::current()`, `Labels`, modelos de dominio.
- Produces: `ProgramMetrics::term(): ?AcademicTerm`, `::cutoff(): string`, `::panel(): array{indicators,attentionRows,cohortChart,recentActivity}`, `::indicators(): array{keyIndicators,papaHistogram,participation,requestsByType}`. Las vistas reciben además `cutoff` y `termLabel`; cada fila de `attentionRows` trae `url` a la ficha. En esta tarea `/panel` sigue abierto a los 4 roles (la redirección del estudiante llega en la Task 8, cuando exista `/solicitudes`).

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/ProgramMetricsTest.php

namespace Tests\Feature;

use App\Support\ProgramMetrics;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_panel_indicators_are_computed_from_the_current_term(): void
    {
        $this->seed(DatabaseSeeder::class);

        $panel = (new ProgramMetrics)->panel();
        $cards = collect($panel['indicators'])->keyBy('label');

        $this->assertSame('6', $cards['ESTUDIANTES ACTIVOS']['value']);
        $this->assertSame('Matriculados en 2026-2S', $cards['ESTUDIANTES ACTIVOS']['detail']);
        $this->assertSame('3.7', $cards['PAPA PROMEDIO']['value']);
        $this->assertSame('-0.3 frente al periodo anterior', $cards['PAPA PROMEDIO']['detail']);
        $this->assertSame('44%', $cards['AVANCE MEDIO']['value']);
        $this->assertSame('3', $cards['ALERTAS ABIERTAS']['value']);
        $this->assertSame('text-[var(--color-danger)]', $cards['ALERTAS ABIERTAS']['valueClass']);
    }

    public function test_attention_rows_list_alerts_newest_first_with_status_and_link(): void
    {
        $this->seed(DatabaseSeeder::class);

        $rows = (new ProgramMetrics)->panel()['attentionRows'];

        $this->assertCount(4, $rows);
        $this->assertSame('Pérez Rojas, Juan Sebastián', $rows[0]['name']);
        $this->assertSame('Sin atender', $rows[0]['status']);
        $this->assertSame('danger', $rows[0]['tone']);
        $this->assertSame('Martínez Cruz, Diana', $rows[3]['name']);
        $this->assertSame('Atendida', $rows[3]['status']);
        $this->assertStringContainsString('/estudiantes/', $rows[0]['url']);
    }

    public function test_cohort_chart_averages_the_progress_by_entry_year(): void
    {
        $this->seed(DatabaseSeeder::class);

        $chart = collect((new ProgramMetrics)->panel()['cohortChart']);

        $this->assertSame(['2019', '2020', '2021', '2022', '2023'], $chart->pluck('label')->all());
        $this->assertSame(['88%', '72%', '48%', '29%', '17%'], $chart->pluck('value')->all());
        $this->assertLessThanOrEqual(152, $chart->max('height'));
        $this->assertGreaterThanOrEqual(3, $chart->min('height'));
    }

    public function test_recent_activity_mixes_tutorships_requests_and_validated_experiences(): void
    {
        $this->seed(DatabaseSeeder::class);

        $activity = (new ProgramMetrics)->panel()['recentActivity'];

        $this->assertCount(5, $activity);
        foreach ($activity as $item) {
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('meta', $item);
        }
    }

    public function test_indicator_screen_metrics(): void
    {
        $this->seed(DatabaseSeeder::class);

        $data = (new ProgramMetrics)->indicators();
        $cards = collect($data['keyIndicators'])->keyBy('label');

        $this->assertSame('6', $cards['ESTUDIANTES ACTIVOS']['value']);
        $this->assertSame('1', $cards['PRÁCTICAS EN CURSO']['value']);
        $this->assertSame('17% de los activos', $cards['PRÁCTICAS EN CURSO']['detail']);
        $this->assertSame('1', $cards['TRABAJOS DE GRADO']['value']);
        $this->assertSame('2', $cards['SOLICITUDES DEL PERIODO']['value']);
        $this->assertSame('2 aún sin decisión', $cards['SOLICITUDES DEL PERIODO']['detail']);
        $this->assertSame('text-[var(--color-warning)]', $cards['SOLICITUDES DEL PERIODO']['valueClass']);

        $histogram = collect($data['papaHistogram']);
        $this->assertSame(['< 3.0', '3.0–3.3', '3.3–3.6', '3.6–4.0', '4.0–4.3', '> 4.3'], $histogram->pluck('range')->all());
        $this->assertSame(['1', '1', '1', '2', '2', '1'], $histogram->pluck('value')->all());
        $this->assertSame([71, 71, 71, 141, 141, 71], $histogram->pluck('height')->all());

        $participation = collect($data['participation']);
        $this->assertCount(4, $participation);
        $this->assertSame('1 · 17%', $participation->first()['value']);

        $byType = collect($data['requestsByType'])->keyBy('type');
        $this->assertSame(['filed' => '1', 'review' => '0', 'decided' => '0', 'avgTime' => '—'], collect($byType['Cancelación de asignatura'])->only(['filed', 'review', 'decided', 'avgTime'])->all());
        $this->assertSame('1', $byType['Reingreso al programa']['review']);
        $this->assertSame('0', $byType['Otras']['filed']);
    }

    public function test_an_empty_database_yields_zeroes_and_dashes_without_errors(): void
    {
        $metrics = new ProgramMetrics;

        $panel = $metrics->panel();
        $cards = collect($panel['indicators'])->keyBy('label');
        $this->assertSame('0', $cards['ESTUDIANTES ACTIVOS']['value']);
        $this->assertSame('—', $cards['PAPA PROMEDIO']['value']);
        $this->assertSame('—', $cards['AVANCE MEDIO']['value']);
        $this->assertSame('0', $cards['ALERTAS ABIERTAS']['value']);
        $this->assertSame([], $panel['attentionRows']);
        $this->assertSame([], $panel['cohortChart']);
        $this->assertSame([], $panel['recentActivity']);

        $data = $metrics->indicators();
        $this->assertCount(6, $data['papaHistogram']);
        $this->assertSame(['0', '0', '0', '0', '0', '0'], collect($data['papaHistogram'])->pluck('value')->all());
        $this->assertSame([], $data['participation']);
        $this->assertSame([], $data['requestsByType']);
    }
}
```

Reemplazar `tests/Feature/PanelTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_renders_real_indicators_and_attention_table(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $response = $this->get('/panel');

        $response->assertOk();
        $response->assertSee('Panel del programa');
        $response->assertSee('ESTUDIANTES ACTIVOS');
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Sin atender');
        $response->assertSee('Atendida');
        $response->assertSee('Matriculados en 2026-2S');
        $response->assertDontSee('312');
        $response->assertDontSee('20/09/2026');
    }

    public function test_staff_roles_can_open_the_panel(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([Role::Profesor, Role::Tutor, Role::Coordinacion] as $role) {
            $this->signInSeeded($role);
            $this->get('/panel')->assertOk();
        }
    }
}
```

Reemplazar `tests/Feature/IndicatorTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_indicators_page_renders_computed_cards_and_tables(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $response = $this->get('/indicadores');

        $response->assertOk();
        $response->assertSee('Indicadores del programa');
        $response->assertSee('SOLICITUDES DEL PERIODO');
        $response->assertSee('aún sin decisión');
        $response->assertSee('Cancelación de asignatura');
        $response->assertSee('Distribución del PAPA');
        $response->assertSee('Periodo 2026-2S');
        $response->assertDontSee('312');
    }

    public function test_only_coordination_can_open_the_indicators(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([Role::Profesor, Role::Tutor, Role::Estudiante] as $role) {
            $this->signInSeeded($role);
            $this->get('/indicadores')->assertForbidden();
        }
    }
}
```

Añadir a `tests/Feature/EmptyDatabaseScreensTest.php`:

```php
    public function test_panel_and_indicators_render_with_an_empty_database(): void
    {
        $this->actingAsRole(Role::Coordinacion);

        $this->get('/panel')->assertOk()->assertSee('ESTUDIANTES ACTIVOS')->assertSee('sin periodo');
        $this->get('/indicadores')->assertOk()->assertSee('Indicadores del programa');
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='ProgramMetricsTest|PanelTest|IndicatorTest|EmptyDatabaseScreensTest'`
Expected: FAIL (`ProgramMetrics` no existe; las pantallas muestran mock).

- [ ] **Step 3: Implement**

`app/Support/ProgramMetrics.php`:

```php
<?php

namespace App\Support;

use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\Alert;
use App\Models\CommitteeRequest;
use App\Models\Experience;
use App\Models\Internship;
use App\Models\RequestType;
use App\Models\Student;
use App\Models\Thesis;
use App\Models\Tutorship;
use Illuminate\Database\Eloquent\Builder;

class ProgramMetrics
{
    private const COHORT_BAR_HEIGHT = 152;

    private const HISTOGRAM_BAR_HEIGHT = 141;

    private ?AcademicTerm $term = null;

    private bool $termLoaded = false;

    public function term(): ?AcademicTerm
    {
        if (! $this->termLoaded) {
            $this->term = AcademicTerm::current();
            $this->termLoaded = true;
        }

        return $this->term;
    }

    public function cutoff(): string
    {
        return now()->format('d/m/Y');
    }

    public function panel(): array
    {
        $term = $this->term();
        $records = $this->records($term);
        $avgPapa = (clone $records)->avg('papa');
        $avgAdvance = (clone $records)->avg('porcentaje_avance');
        $previous = $this->previousTerm($term);
        $prevPapa = $previous ? $this->records($previous)->avg('papa') : null;
        $open = Alert::where('atendida', false)->count();

        $delta = $avgPapa !== null && $prevPapa !== null
            ? ((float) $avgPapa - (float) $prevPapa >= 0 ? '+' : '').number_format((float) $avgPapa - (float) $prevPapa, 1).' frente al periodo anterior'
            : 'Sin periodo anterior para comparar';

        $alerts = ['label' => 'ALERTAS ABIERTAS', 'value' => (string) $open, 'detail' => 'Requieren atención temprana'];
        if ($open > 0) {
            $alerts['valueClass'] = 'text-[var(--color-danger)]';
        }

        return [
            'indicators' => [
                ['label' => 'ESTUDIANTES ACTIVOS', 'value' => (string) Student::where('estado', 'activo')->count(), 'detail' => 'Matriculados en '.Labels::termLabel($term)],
                ['label' => 'PAPA PROMEDIO', 'value' => $avgPapa !== null ? number_format((float) $avgPapa, 1) : '—', 'detail' => $delta],
                ['label' => 'AVANCE MEDIO', 'value' => $avgAdvance !== null ? round((float) $avgAdvance).'%' : '—', 'detail' => 'Créditos cursados del plan'],
                $alerts,
            ],
            'attentionRows' => $this->attentionRows(),
            'cohortChart' => $this->cohortChart($records),
            'recentActivity' => $this->recentActivity(),
        ];
    }

    public function indicators(): array
    {
        $term = $this->term();
        $active = Student::where('estado', 'activo')->count();
        $share = fn (int $n) => $active > 0 ? round($n / $active * 100).'% de los activos' : 'Sin estudiantes activos';

        $internships = Internship::whereDate('fecha_inicio', '<=', today())
            ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', today()))
            ->count();
        $theses = Thesis::where('estado', 'en_desarrollo')->count();

        $periodRequests = CommitteeRequest::query()->where('term_id', $term?->id ?? 0);
        $filed = (clone $periodRequests)->count();
        $pending = (clone $periodRequests)->where('estado', '!=', 'con_decision')->count();

        return [
            'keyIndicators' => [
                ['label' => 'ESTUDIANTES ACTIVOS', 'value' => (string) $active, 'detail' => 'Matriculados en '.Labels::termLabel($term)],
                ['label' => 'PRÁCTICAS EN CURSO', 'value' => (string) $internships, 'detail' => $share($internships)],
                ['label' => 'TRABAJOS DE GRADO', 'value' => (string) $theses, 'detail' => $share($theses)],
                ['label' => 'SOLICITUDES DEL PERIODO', 'value' => (string) $filed, 'detail' => $pending.' aún sin decisión', 'valueClass' => 'text-[var(--color-warning)]'],
            ],
            'papaHistogram' => $this->papaHistogram($term),
            'participation' => $this->participation($active),
            'requestsByType' => $this->requestsByType($term),
        ];
    }

    private function records(?AcademicTerm $term): Builder
    {
        return AcademicRecord::query()->where('academic_records.term_id', $term?->id ?? 0);
    }

    private function previousTerm(?AcademicTerm $term): ?AcademicTerm
    {
        return $term
            ? AcademicTerm::whereHas('academicRecords')->where('fecha_inicio', '<', $term->fecha_inicio)->orderByDesc('fecha_inicio')->first()
            : null;
    }

    private function attentionRows(): array
    {
        return Alert::with('student.user')->latest('fecha_deteccion')->latest('id')->limit(6)->get()->map(fn (Alert $alert) => [
            'name' => $alert->student->user?->name ?? $alert->student->codigo,
            'issue' => $alert->detalle ?? $alert->tipo,
            'date' => Labels::date($alert->fecha_deteccion),
            'status' => $alert->atendida ? 'Atendida' : 'Sin atender',
            'tone' => $alert->atendida ? 'success' : 'danger',
            'url' => route('students.show', $alert->student_id),
        ])->all();
    }

    private function cohortChart(Builder $records): array
    {
        return (clone $records)
            ->join('students', 'students.id', '=', 'academic_records.student_id')
            ->selectRaw('left(students.cohorte, 4) as anio, avg(academic_records.porcentaje_avance) as avance')
            ->groupBy('anio')
            ->orderBy('anio')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->anio,
                'value' => round((float) $row->avance).'%',
                'height' => max(3, (int) round((float) $row->avance / 100 * self::COHORT_BAR_HEIGHT)),
            ])->all();
    }

    private function recentActivity(): array
    {
        $items = collect();

        foreach (Tutorship::with(['student.user', 'tutor'])->latest()->limit(5)->get() as $t) {
            $items->push(['at' => $t->created_at, 'icon' => 'message-square', 'description' => 'Tutoría registrada a '.($t->student->user?->name ?? $t->student->codigo), 'meta' => $t->created_at->diffForHumans().' · '.$t->tutor->name]);
        }
        foreach (CommitteeRequest::with(['student.user', 'type'])->latest('fecha_radicacion')->limit(5)->get() as $r) {
            $items->push(['at' => $r->fecha_radicacion, 'icon' => 'file-text', 'description' => 'Solicitud de '.mb_strtolower($r->type->nombre).' radicada', 'meta' => $r->fecha_radicacion->diffForHumans().' · '.($r->student->user?->name ?? $r->student->codigo)]);
        }
        foreach (Experience::with('validador')->where('validada', true)->latest('updated_at')->limit(5)->get() as $e) {
            $items->push(['at' => $e->updated_at, 'icon' => 'sparkles', 'description' => 'Experiencia validada: '.$e->nombre, 'meta' => $e->updated_at->diffForHumans().' · '.($e->validador?->name ?? 'Coordinación')]);
        }

        return $items->sortByDesc('at')->take(5)->map(fn (array $i) => collect($i)->except('at')->all())->values()->all();
    }

    private function papaHistogram(?AcademicTerm $term): array
    {
        $buckets = [['< 3.0', 3.0], ['3.0–3.3', 3.3], ['3.3–3.6', 3.6], ['3.6–4.0', 4.0], ['4.0–4.3', 4.3], ['> 4.3', INF]];
        $counts = array_fill(0, count($buckets), 0);

        foreach ($this->records($term)->pluck('papa') as $papa) {
            foreach ($buckets as $i => [, $upper]) {
                if ((float) $papa < $upper) {
                    $counts[$i]++;
                    break;
                }
            }
        }

        $max = max($counts) ?: 1;

        return array_map(fn (array $bucket, int $count) => [
            'range' => $bucket[0],
            'value' => (string) $count,
            'height' => (int) round($count / $max * self::HISTOGRAM_BAR_HEIGHT),
        ], $buckets, $counts);
    }

    private function participation(int $active): array
    {
        return Experience::query()
            ->join('experience_types', 'experience_types.id', '=', 'experiences.experience_type_id')
            ->selectRaw('experience_types.categoria as categoria, count(distinct experiences.student_id) as total')
            ->groupBy('experience_types.categoria')
            ->orderByDesc('total')
            ->orderBy('categoria')
            ->get()
            ->map(function ($row) use ($active) {
                $percent = $active > 0 ? (int) min(100, round($row->total / $active * 100)) : 0;

                return ['label' => $row->categoria, 'value' => $row->total.' · '.$percent.'%', 'percent' => $percent];
            })->all();
    }

    private function requestsByType(?AcademicTerm $term): array
    {
        $requests = CommitteeRequest::with('statusHistory')->where('term_id', $term?->id ?? 0)->get()->groupBy('request_type_id');

        return RequestType::orderBy('nombre')->get()->map(function (RequestType $type) use ($requests) {
            $group = $requests->get($type->id, collect());
            $days = $group->where('estado', 'con_decision')->map(function (CommitteeRequest $r) {
                $decided = $r->statusHistory->firstWhere('estado_nuevo', 'con_decision');

                return $decided ? $r->fecha_radicacion->diffInDays($decided->fecha) : null;
            })->filter(fn ($d) => $d !== null);

            return [
                'type' => $type->nombre,
                'filed' => (string) $group->count(),
                'review' => (string) $group->where('estado', 'en_revision')->count(),
                'decided' => (string) $group->where('estado', 'con_decision')->count(),
                'avgTime' => $days->isEmpty() ? '—' : round($days->avg()).' días',
            ];
        })->all();
    }
}
```

`app/Http/Controllers/PanelController.php` — reemplazar el archivo:

```php
<?php

namespace App\Http\Controllers;

use App\Support\Labels;
use App\Support\Navigation;
use App\Support\ProgramMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(Request $request, ProgramMetrics $metrics): View
    {
        $user = $request->user();

        return view('panel.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'panel'),
            'cutoff' => $metrics->cutoff(),
            'termLabel' => Labels::termLabel($metrics->term()),
            ...$metrics->panel(),
            'quickLinks' => [
                ['icon' => 'upload', 'label' => 'Cargar archivo de Excel'],
                ['icon' => 'file-plus', 'label' => 'Registrar decisión del comité'],
                ['icon' => 'user-plus', 'label' => 'Crear usuario y asignar rol'],
            ],
        ]);
    }
}
```

`app/Http/Controllers/IndicatorController.php` — reemplazar el archivo:

```php
<?php

namespace App\Http\Controllers;

use App\Support\Labels;
use App\Support\Navigation;
use App\Support\ProgramMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndicatorController extends Controller
{
    public function index(Request $request, ProgramMetrics $metrics): View
    {
        $user = $request->user();

        return view('indicators.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'indicadores'),
            'cutoff' => $metrics->cutoff(),
            'termLabel' => Labels::termLabel($metrics->term()),
            ...$metrics->indicators(),
        ]);
    }
}
```

`resources/views/panel/index.blade.php` — cuatro cambios:
1. Reemplazar `Datos al 20/09/2026 · última sincronización con la API hace 2 horas` por `Datos al {{ $cutoff }} · periodo {{ $termLabel }}`.
2. En cada fila de `attentionRows`, reemplazar `<a href="/estudiantes" class="w-[70px] text-sm font-medium text-[var(--color-primary-600)]">Ver ficha</a>` por `<a href="{{ $row['url'] }}" class="w-[70px] text-sm font-medium text-[var(--color-primary-600)]">Ver ficha</a>`.
3. Antes de `@foreach($attentionRows as $row)` añadir `@if(empty($attentionRows))<p class="border-t border-[var(--border-subtle)] px-4 py-6 text-sm text-[var(--text-muted)]">Sin situaciones registradas.</p>@endif`; antes de `@foreach($cohortChart as $bar)` añadir `@if(empty($cohortChart))<p class="text-sm text-[var(--text-muted)]">Sin datos de avance por cohorte.</p>@endif`; antes de `@foreach($recentActivity as $activity)` añadir `@if(empty($recentActivity))<p class="px-4 py-3 text-sm text-[var(--text-muted)]">Sin actividad reciente.</p>@endif`.
4. En los accesos rápidos, el segundo botón pasa a enlace: reemplazar el `<button type="button" …>` del `@foreach($quickLinks …)` por un `<a href="{{ $link['href'] ?? '#' }}" class="…mismas clases…">` y agregar `'href' => '/solicitudes'` al elemento "Registrar decisión del comité" en el controlador.

`resources/views/indicators/index.blade.php` — dos cambios:
1. Reemplazar el bloque de filtros estáticos (el `<div class="flex flex-wrap items-center gap-3 …">` completo con los tres desplegables, "Aplicar" y "Datos al 20/09/2026") por:
```blade
        <div class="flex flex-wrap items-center gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-3">
            <span class="rounded-full bg-[var(--surface-sunken)] px-3 py-1 text-sm text-[var(--text-secondary)]">Periodo {{ $termLabel }}</span>
            <div class="flex-1"></div>
            <span class="text-sm text-[var(--text-muted)]">Datos al {{ $cutoff }}</span>
        </div>
```
2. En "Solicitudes por tipo y estado" reemplazar `Periodo 2026-2S · RF-28` por `Periodo {{ $termLabel }} · RF-28`.

- [ ] **Step 4: Run tests to verify they pass, then the full suite**

Run: `php artisan test --filter='ProgramMetricsTest|PanelTest|IndicatorTest|EmptyDatabaseScreensTest' && php artisan test`
Expected: PASS; suite completa en verde.

- [ ] **Step 5: Commit**

```bash
git add app resources tests
git commit -m "feat(panel): calcular panel e indicadores desde la bd"
```

---

### Task 7: Política de solicitudes y servicio `RequestWorkflow`

**Files:**
- Create: `app/Policies/CommitteeRequestPolicy.php`, `app/Services/RequestWorkflow.php`, `tests/Feature/CommitteeRequestPolicyTest.php`, `tests/Feature/RequestWorkflowTest.php`
- Modify: `app/Providers/AuthServiceProvider.php`

**Interfaces:**
- Consumes: `CreatesStudents::makeStudent/makeTerm/makeRequest`, modelos de solicitudes.
- Produces:
  - Habilidades: `viewAny` (todos), `view` (no estudiante, o dueño), `create` (estudiante, coordinación), `decide` (coordinación) sobre `CommitteeRequest`.
  - `RequestWorkflow::DECISIONS = ['aprobada','rechazada','aplazada']`.
  - `file(Student, RequestType, AcademicTerm, string $descripcion, array $files, User $actor): CommitteeRequest`.
  - `startReview(CommitteeRequest, User): void` (radicada → en_revision), `decide(CommitteeRequest, string $decision, string $numeroActa, ?string $observaciones, User): void` (en_revision → con_decision). Ambos lanzan `\DomainException` con mensaje en español si el estado no lo permite y refrescan el modelo.
  - `repeatedCount(CommitteeRequest): int`, `existingTypeNames(Student, AcademicTerm): array<int,string>`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/CommitteeRequestPolicyTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CommitteeRequestPolicyTest extends TestCase
{
    private function request(int $ownerUserId): CommitteeRequest
    {
        $student = new Student(['codigo' => 'X']);
        $student->user_id = $ownerUserId;
        $request = new CommitteeRequest;
        $request->setRelation('student', $student);

        return $request;
    }

    private function allows(Role $role, string $ability, mixed $argument, int $id = 1): bool
    {
        return Gate::forUser(User::factory()->make(['id' => $id, 'role' => $role]))->allows($ability, $argument);
    }

    public function test_a_student_views_only_their_own_requests(): void
    {
        $this->assertTrue($this->allows(Role::Estudiante, 'view', $this->request(5), 5));
        $this->assertFalse($this->allows(Role::Estudiante, 'view', $this->request(5), 6));
    }

    public function test_staff_roles_view_any_request(): void
    {
        foreach ([Role::Profesor, Role::Tutor, Role::Coordinacion] as $role) {
            $this->assertTrue($this->allows($role, 'view', $this->request(5)));
        }
    }

    public function test_everyone_can_list_but_only_student_and_coordination_create(): void
    {
        foreach (Role::cases() as $role) {
            $this->assertTrue($this->allows($role, 'viewAny', CommitteeRequest::class));
        }
        $this->assertTrue($this->allows(Role::Estudiante, 'create', CommitteeRequest::class));
        $this->assertTrue($this->allows(Role::Coordinacion, 'create', CommitteeRequest::class));
        $this->assertFalse($this->allows(Role::Profesor, 'create', CommitteeRequest::class));
        $this->assertFalse($this->allows(Role::Tutor, 'create', CommitteeRequest::class));
    }

    public function test_only_coordination_decides(): void
    {
        $this->assertTrue($this->allows(Role::Coordinacion, 'decide', $this->request(5)));
        foreach ([Role::Estudiante, Role::Profesor, Role::Tutor] as $role) {
            $this->assertFalse($this->allows($role, 'decide', $this->request(5), 5));
        }
    }
}
```

```php
<?php
// tests/Feature/RequestWorkflowTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\CommitteeMinute;
use App\Models\CommitteeRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Services\RequestWorkflow;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class RequestWorkflowTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private RequestWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->workflow = new RequestWorkflow;
    }

    private function coordinator(): User
    {
        return User::factory()->create(['role' => Role::Coordinacion]);
    }

    public function test_file_creates_the_request_its_attachments_and_the_first_history_row(): void
    {
        $student = $this->makeStudent();
        $type = RequestType::create(['nombre' => 'Reingreso al programa']);

        $request = $this->workflow->file($student, $type, $this->makeTerm(), str_repeat('Motivo. ', 10), [
            UploadedFile::fake()->create('incapacidad.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->image('foto.png'),
        ], $student->user);

        $this->assertSame('radicada', $request->estado);
        $this->assertSame($student->id, $request->student_id);
        $this->assertCount(2, $request->attachments);
        Storage::disk('local')->assertExists($request->attachments->first()->ruta);
        $this->assertSame('incapacidad.pdf', $request->attachments->first()->nombre_original);
        $this->assertCount(1, $request->statusHistory);
        $this->assertNull($request->statusHistory->first()->estado_anterior);
        $this->assertSame('radicada', $request->statusHistory->first()->estado_nuevo);
        $this->assertSame($student->user->id, $request->statusHistory->first()->user_id);
    }

    public function test_a_failure_while_storing_leaves_neither_rows_nor_files(): void
    {
        $student = $this->makeStudent();
        $type = RequestType::create(['nombre' => 'Otras']);

        try {
            $this->workflow->file($student, $type, $this->makeTerm(), str_repeat('Motivo. ', 10), [
                UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
                'esto-no-es-un-archivo',
            ], $student->user);
            $this->fail('Debía fallar al guardar el segundo soporte.');
        } catch (\Throwable) {
            $this->assertSame(0, CommitteeRequest::count());
            $this->assertDatabaseCount('request_attachments', 0);
            $this->assertDatabaseCount('request_status_history', 0);
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    public function test_review_then_decision_moves_through_the_three_states_with_history(): void
    {
        $coordinator = $this->coordinator();
        $request = $this->makeRequest($this->makeStudent('2030002'));
        $this->workflow->startReview($request, $coordinator);
        $this->assertSame('en_revision', $request->estado);

        $this->workflow->decide($request, 'aprobada', '12-2026', 'Procede.', $coordinator);

        $request->refresh();
        $this->assertSame('con_decision', $request->estado);
        $this->assertSame('aprobada', $request->decision);
        $this->assertSame('Procede.', $request->observaciones);
        $this->assertSame('12-2026', $request->minute->numero_acta);
        $this->assertSame(
            [['radicada', 'en_revision'], ['en_revision', 'con_decision']],
            $request->statusHistory()->orderBy('id')->get()->map(fn ($h) => [$h->estado_anterior, $h->estado_nuevo])->all()
        );
        $this->assertSame($coordinator->id, $request->statusHistory()->latest('id')->first()->user_id);
    }

    public function test_the_same_minute_resolves_several_requests(): void
    {
        $coordinator = $this->coordinator();
        foreach (['2030003', '2030004'] as $codigo) {
            $request = $this->makeRequest($this->makeStudent($codigo));
            $this->workflow->startReview($request, $coordinator);
            $this->workflow->decide($request, 'rechazada', '99-2026', null, $coordinator);
        }

        $this->assertSame(1, CommitteeMinute::where('numero_acta', '99-2026')->count());
        $this->assertSame(2, CommitteeRequest::where('minute_id', CommitteeMinute::first()->id)->count());
    }

    public function test_review_cannot_be_started_twice(): void
    {
        $request = $this->makeRequest($this->makeStudent());
        $coordinator = $this->coordinator();
        $this->workflow->startReview($request, $coordinator);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('ya fue pasada a revisión');
        $this->workflow->startReview($request, $coordinator);
    }

    public function test_a_decision_requires_a_prior_review(): void
    {
        $request = $this->makeRequest($this->makeStudent());

        try {
            $this->workflow->decide($request, 'aprobada', '1-2026', null, $this->coordinator());
            $this->fail('Debía exigir la revisión previa.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('antes de registrar la decisión', $e->getMessage());
            $this->assertSame('radicada', $request->fresh()->estado);
            $this->assertCount(0, $request->statusHistory);
        }
    }

    public function test_a_second_decision_is_rejected_and_keeps_the_first(): void
    {
        $request = $this->makeRequest($this->makeStudent());
        $coordinator = $this->coordinator();
        $this->workflow->startReview($request, $coordinator);
        $this->workflow->decide($request, 'aprobada', '1-2026', null, $coordinator);

        try {
            $this->workflow->decide($request, 'rechazada', '2-2026', null, $coordinator);
            $this->fail('Debía rechazar la segunda decisión.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('ya tiene una decisión', $e->getMessage());
            $this->assertSame('aprobada', $request->fresh()->decision);
            $this->assertSame(2, $request->statusHistory()->count());
        }
    }

    public function test_an_unknown_decision_value_is_rejected(): void
    {
        $request = $this->makeRequest($this->makeStudent());
        $this->workflow->startReview($request, $this->coordinator());

        $this->expectException(DomainException::class);
        $this->workflow->decide($request, 'quizás', '1-2026', null, $this->coordinator());
    }

    public function test_repeated_count_ignores_itself_other_types_other_terms_and_deleted_requests(): void
    {
        $student = $this->makeStudent();
        $first = $this->makeRequest($student, 'Cancelación de asignatura');
        $this->assertSame(0, $this->workflow->repeatedCount($first));

        $this->makeRequest($student, 'Cancelación de asignatura');
        $this->makeRequest($student, 'Reingreso al programa');
        $this->makeRequest($student, 'Cancelación de asignatura', ['term_id' => $this->makeTerm('2025-2')->id]);
        $this->makeRequest($student, 'Cancelación de asignatura')->delete();

        $this->assertSame(1, $this->workflow->repeatedCount($first));
        $this->assertSame(
            ['Cancelación de asignatura', 'Reingreso al programa'],
            collect($this->workflow->existingTypeNames($student, AcademicTerm::where('codigo', '2026-2')->first()))->sort()->values()->all()
        );
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='CommitteeRequestPolicyTest|RequestWorkflowTest'`
Expected: FAIL (`RequestWorkflow` y la política no existen).

- [ ] **Step 3: Implement**

`app/Policies/CommitteeRequestPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use App\Models\User;

class CommitteeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CommitteeRequest $request): bool
    {
        return $user->role !== Role::Estudiante || $request->student?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [Role::Estudiante, Role::Coordinacion], true);
    }

    public function decide(User $user, CommitteeRequest $request): bool
    {
        return $user->role === Role::Coordinacion;
    }
}
```

`app/Providers/AuthServiceProvider.php` — imports `App\Models\CommitteeRequest`, `App\Policies\CommitteeRequestPolicy` y:

```php
    protected $policies = [
        CommitteeRequest::class => CommitteeRequestPolicy::class,
    ];
```

`app/Services/RequestWorkflow.php`:

```php
<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\CommitteeMinute;
use App\Models\CommitteeRequest;
use App\Models\RequestStatusHistory;
use App\Models\RequestType;
use App\Models\Student;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RequestWorkflow
{
    public const DECISIONS = ['aprobada', 'rechazada', 'aplazada'];

    /** @param  array<int, UploadedFile>  $files */
    public function file(Student $student, RequestType $type, AcademicTerm $term, string $descripcion, array $files, User $actor): CommitteeRequest
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($student, $type, $term, $descripcion, $files, $actor, &$stored) {
                $request = CommitteeRequest::create([
                    'student_id' => $student->id,
                    'request_type_id' => $type->id,
                    'term_id' => $term->id,
                    'descripcion' => $descripcion,
                    'estado' => 'radicada',
                    'fecha_radicacion' => now(),
                ]);

                foreach ($files as $file) {
                    $path = $file->store("soportes/{$request->id}");
                    if ($path === false) {
                        throw new DomainException('No se pudo guardar un soporte.');
                    }
                    $stored[] = $path;
                    $request->attachments()->create([
                        'ruta' => $path,
                        'nombre_original' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType() ?? 'application/octet-stream',
                        'tamano' => $file->getSize(),
                    ]);
                }

                $this->record($request, null, 'radicada', $actor);

                return $request;
            });
        } catch (Throwable $e) {
            foreach ($stored as $path) {
                Storage::delete($path);
            }

            throw $e;
        }
    }

    public function startReview(CommitteeRequest $request, User $actor): void
    {
        DB::transaction(function () use ($request, $actor) {
            $locked = CommitteeRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->estado !== 'radicada') {
                throw new DomainException('La solicitud ya fue pasada a revisión.');
            }

            $locked->update(['estado' => 'en_revision']);
            $this->record($locked, 'radicada', 'en_revision', $actor);
        });

        $request->refresh();
    }

    public function decide(CommitteeRequest $request, string $decision, string $numeroActa, ?string $observaciones, User $actor): void
    {
        if (! in_array($decision, self::DECISIONS, true)) {
            throw new DomainException('La decisión no es válida.');
        }

        DB::transaction(function () use ($request, $decision, $numeroActa, $observaciones, $actor) {
            $locked = CommitteeRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->estado === 'con_decision') {
                throw new DomainException('La solicitud ya tiene una decisión registrada.');
            }
            if ($locked->estado !== 'en_revision') {
                throw new DomainException('Pase la solicitud a revisión antes de registrar la decisión.');
            }

            $minute = CommitteeMinute::firstOrCreate(
                ['numero_acta' => $numeroActa],
                ['fecha' => today(), 'instancia' => 'Comité asesor']
            );

            $locked->update([
                'estado' => 'con_decision',
                'decision' => $decision,
                'minute_id' => $minute->id,
                'observaciones' => $observaciones,
            ]);
            $this->record($locked, 'en_revision', 'con_decision', $actor);
        });

        $request->refresh();
    }

    public function repeatedCount(CommitteeRequest $request): int
    {
        return CommitteeRequest::where('student_id', $request->student_id)
            ->where('request_type_id', $request->request_type_id)
            ->where('term_id', $request->term_id)
            ->where('id', '!=', $request->id)
            ->count();
    }

    /** @return array<int, string> */
    public function existingTypeNames(Student $student, AcademicTerm $term): array
    {
        return CommitteeRequest::with('type')
            ->where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->get()
            ->pluck('type.nombre')
            ->unique()
            ->values()
            ->all();
    }

    private function record(CommitteeRequest $request, ?string $from, string $to, User $actor): void
    {
        RequestStatusHistory::create([
            'request_id' => $request->id,
            'estado_anterior' => $from,
            'estado_nuevo' => $to,
            'user_id' => $actor->id,
            'fecha' => now(),
        ]);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass, then the full suite**

Run: `php artisan test --filter='CommitteeRequestPolicyTest|RequestWorkflowTest' && php artisan test`
Expected: PASS; suite completa en verde.

- [ ] **Step 5: Commit**

```bash
git add app tests
git commit -m "feat(requests): agregar política de solicitudes y servicio de flujo de estados"
```

---

### Task 8: Listado y detalle de solicitudes con descarga de soportes (C3, C4)

**Files:**
- Create: `resources/views/requests/index.blade.php`, `resources/views/requests/show.blade.php`, `tests/Feature/RequestIndexShowTest.php`
- Modify: `app/Http/Controllers/RequestController.php` (añadir `index`, `show`, `download`; `create`/`store` no cambian en esta tarea), `app/Http/Controllers/PanelController.php`, `routes/web.php`, `tests/Feature/AuthTest.php`, `tests/Feature/ShellUserTest.php`, `tests/Feature/PanelTest.php`, `tests/Feature/EmptyDatabaseScreensTest.php`

**Interfaces:**
- Consumes: `CommitteeRequestPolicy`, `RequestWorkflow::repeatedCount`, `StudentProfile::header`, `Labels`, `x-filter-select`, `x-pagination`.
- Produces: `GET /solicitudes` (`requests.index`), `GET /solicitudes/{solicitud}` (`requests.show`), `GET /solicitudes/{solicitud}/soportes/{soporte}` (`requests.attachments.show`). `/panel` redirige al Estudiante a `requests.index`. La vista `requests.show` recibe `solicitud`, `attachments`, `trail`, `studentCard`, `repeatedWarning`, `decision`, `canReview`, `canDecide`, `decisionOptions`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/RequestIndexShowTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use App\Models\RequestAttachment;
use App\Models\RequestType;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class RequestIndexShowTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private function juanRequest(): CommitteeRequest
    {
        return CommitteeRequest::whereHas('student', fn ($q) => $q->where('codigo', '2020123'))->firstOrFail();
    }

    private function lauraRequest(): CommitteeRequest
    {
        return CommitteeRequest::whereHas('student', fn ($q) => $q->where('codigo', '2021045'))->firstOrFail();
    }

    private function signInAs(string $email): void
    {
        $this->actingAs(User::where('email', $email)->firstOrFail());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    // ---- listado ----

    public function test_coordination_sees_every_request_with_status_and_a_create_button(): void
    {
        $this->signInSeeded(Role::Coordinacion);

        $response = $this->get('/solicitudes');

        $response->assertOk();
        $response->assertSee('Solicitudes');
        $response->assertSee('2 solicitudes en el periodo 2026-2S · 2 sin decisión');
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Gómez Ruiz, Laura');
        $response->assertSee('Cancelación de asignatura');
        $response->assertSee('Reingreso al programa');
        $response->assertSee('Radicada');
        $response->assertSee('En revisión');
        $response->assertSee('Nueva solicitud');
        $response->assertSee('Mostrando 1–2 de 2 solicitudes');
    }

    public function test_a_student_only_sees_their_own_requests(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $response = $this->get('/solicitudes');

        $response->assertOk();
        $response->assertSee('Mis solicitudes');
        $response->assertSee('Cancelación de asignatura');
        $response->assertDontSee('Gómez Ruiz, Laura');
        $response->assertDontSee('Reingreso al programa');
        $response->assertDontSee('ESTUDIANTE');
        $response->assertSee('Mostrando 1–1 de 1 solicitudes');
    }

    public function test_a_student_cannot_widen_the_list_with_the_search_box(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $this->get('/solicitudes?q=Laura')->assertSee('Cancelación de asignatura')->assertDontSee('Gómez Ruiz, Laura');
    }

    public function test_professor_and_tutor_see_all_without_the_create_button(): void
    {
        foreach ([Role::Profesor, Role::Tutor] as $role) {
            $this->signInSeeded($role);
            $this->get('/solicitudes')->assertOk()->assertSee('Gómez Ruiz, Laura')->assertSee('Pérez Rojas, Juan Sebastián')->assertDontSee('Nueva solicitud');
        }
    }

    public function test_filters_by_status_type_and_search(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $reingreso = RequestType::where('nombre', 'Reingreso al programa')->firstOrFail();

        $this->get('/solicitudes?estado=en_revision')->assertSee('Gómez Ruiz, Laura')->assertDontSee('Pérez Rojas, Juan Sebastián');
        $this->get('/solicitudes?estado=radicada')->assertSee('Pérez Rojas, Juan Sebastián')->assertDontSee('Gómez Ruiz, Laura');
        $this->get('/solicitudes?tipo='.$reingreso->id)->assertSee('Gómez Ruiz, Laura')->assertDontSee('Pérez Rojas, Juan Sebastián');
        $this->get('/solicitudes?q=gómez')->assertSee('Gómez Ruiz, Laura')->assertDontSee('Pérez Rojas, Juan Sebastián');
        $this->get('/solicitudes?q=2020123')->assertSee('Pérez Rojas, Juan Sebastián')->assertDontSee('Gómez Ruiz, Laura');
        $this->get('/solicitudes?estado=aprobada')->assertSee('No hay solicitudes');
    }

    public function test_hostile_and_array_query_values_do_not_break_the_list(): void
    {
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/solicitudes?q=%25')->assertSee('No hay solicitudes');
        $this->get('/solicitudes?q[]=x&tipo[]=1&estado[]=z&periodo[]=2')->assertOk();
    }

    public function test_pagination_keeps_filters(): void
    {
        config(['program.page_size' => 1]);
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/solicitudes?page=2')->assertSee('Mostrando 2–2 de 2 solicitudes');
    }

    // ---- detalle ----

    public function test_coordination_opens_the_detail_with_description_and_trail(): void
    {
        $this->signInSeeded(Role::Coordinacion);

        $response = $this->get('/solicitudes/'.$this->juanRequest()->id);

        $response->assertOk();
        $response->assertSee('Cancelación de asignatura');
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Trazabilidad');
        $response->assertSee('Pendiente · la registra Coordinación');
        $response->assertSee('Código 2020123');
    }

    public function test_the_owner_staff_can_open_but_another_student_cannot(): void
    {
        $request = $this->juanRequest();

        $this->signInSeeded(Role::Estudiante);
        $this->get('/solicitudes/'.$request->id)->assertOk()->assertDontSee('Código 2020123');

        foreach ([Role::Profesor, Role::Tutor] as $role) {
            $this->signInSeeded($role);
            $this->get('/solicitudes/'.$request->id)->assertOk();
        }

        $this->signInAs('est2021045@unal.edu.co');
        $this->get('/solicitudes/'.$request->id)->assertForbidden();
    }

    public function test_coordination_is_warned_about_repeated_requests_but_the_student_is_not(): void
    {
        $juan = Student::where('codigo', '2020123')->firstOrFail();
        $this->makeRequest($juan, 'Cancelación de asignatura');
        $request = $this->juanRequest();

        $this->signInSeeded(Role::Coordinacion);
        $this->get('/solicitudes/'.$request->id)->assertSee('2.ª solicitud de este tipo');

        $this->signInSeeded(Role::Estudiante);
        $this->get('/solicitudes/'.$request->id)->assertDontSee('solicitud de este tipo');
    }

    public function test_deleted_unknown_and_non_numeric_requests_return_404(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $request = $this->juanRequest();
        $request->delete();

        $this->get('/solicitudes/'.$request->id)->assertNotFound();
        $this->get('/solicitudes/999999')->assertNotFound();
        $this->get('/solicitudes/abc')->assertNotFound();
    }

    // ---- soportes ----

    private function attach(CommitteeRequest $request): RequestAttachment
    {
        Storage::fake();
        Storage::put('soportes/'.$request->id.'/incapacidad.pdf', 'contenido');

        return $request->attachments()->create([
            'ruta' => 'soportes/'.$request->id.'/incapacidad.pdf', 'nombre_original' => 'incapacidad.pdf',
            'mime' => 'application/pdf', 'tamano' => 9,
        ]);
    }

    public function test_attachments_are_listed_and_downloadable_only_by_those_who_can_see_the_request(): void
    {
        $request = $this->juanRequest();
        $attachment = $this->attach($request);
        $url = '/solicitudes/'.$request->id.'/soportes/'.$attachment->id;

        $this->signInSeeded(Role::Estudiante);
        $this->get('/solicitudes/'.$request->id)->assertSee('incapacidad.pdf');
        $this->get($url)->assertOk()->assertHeader('content-disposition', 'attachment; filename=incapacidad.pdf');

        $this->signInSeeded(Role::Coordinacion);
        $this->get($url)->assertOk();

        $this->signInAs('est2021045@unal.edu.co');
        $this->get($url)->assertForbidden();
    }

    public function test_an_attachment_of_another_request_is_not_found_under_this_one(): void
    {
        $other = $this->attach($this->lauraRequest());
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/solicitudes/'.$this->juanRequest()->id.'/soportes/'.$other->id)->assertNotFound();
    }

    public function test_a_missing_file_returns_404(): void
    {
        $request = $this->juanRequest();
        $attachment = $this->attach($request);
        Storage::delete($attachment->ruta);
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/solicitudes/'.$request->id.'/soportes/'.$attachment->id)->assertNotFound();
    }
}
```

Actualizar `tests/Feature/PanelTest.php` añadiendo:

```php
    public function test_the_student_is_redirected_away_from_the_panel(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $this->get('/panel')->assertRedirect('/solicitudes');
    }
```

En `tests/Feature/AuthTest.php::test_role_matrix_…`: quitar la fila `['/panel', …]` de la matriz, añadir `['/solicitudes', [Role::Estudiante, Role::Profesor, Role::Tutor, Role::Coordinacion]]`, y añadir un test:

```php
    public function test_panel_is_open_to_staff_and_redirects_students_to_their_requests(): void
    {
        foreach ([Role::Profesor, Role::Tutor, Role::Coordinacion] as $role) {
            $this->actingAsRole($role);
            $this->get('/panel')->assertOk();
        }

        $this->actingAsRole(Role::Estudiante);
        $this->get('/panel')->assertRedirect('/solicitudes');
    }
```

En `tests/Feature/ShellUserTest.php::test_menu_depends_on_the_role`: en el bloque del estudiante reemplazar `$this->get('/panel')` por `$this->get('/solicitudes')` (el resto de aserciones iguales). Añadir a `tests/Feature/EmptyDatabaseScreensTest.php`:

```php
    public function test_request_list_renders_with_an_empty_database(): void
    {
        $this->actingAsRole(Role::Coordinacion);

        $this->get('/solicitudes')->assertOk()->assertSee('No hay solicitudes')->assertSee('0 solicitudes');
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='RequestIndexShowTest|PanelTest|AuthTest|EmptyDatabaseScreensTest|ShellUserTest'`
Expected: FAIL (rutas y vistas inexistentes).

- [ ] **Step 3: Implement**

`routes/web.php` — dentro del grupo `auth`, después del grupo `role:estudiante,coordinacion` de `/solicitudes/nueva`:

```php
    Route::get('/solicitudes', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/solicitudes/{solicitud}', [RequestController::class, 'show'])->whereNumber('solicitud')->name('requests.show');
    Route::get('/solicitudes/{solicitud}/soportes/{soporte}', [RequestController::class, 'download'])
        ->whereNumber(['solicitud', 'soporte'])
        ->name('requests.attachments.show');
```

`app/Http/Controllers/PanelController.php` — `index()` pasa a devolver `View|RedirectResponse` y, justo después de `$user = $request->user();`, añade:

```php
        if ($user->role === Role::Estudiante) {
            return redirect()->route('requests.index');
        }
```
(imports `App\Enums\Role` e `Illuminate\Http\RedirectResponse`).

`app/Http/Controllers/RequestController.php` — añadir imports (`App\Enums\Role`, `App\Models\AcademicTerm`, `App\Models\CommitteeRequest`, `App\Models\RequestAttachment`, `App\Models\RequestType`, `App\Services\RequestWorkflow`, `App\Support\Labels`, `App\Support\StudentProfile`, `Illuminate\Support\Facades\Storage`, `Symfony\Component\HttpFoundation\StreamedResponse`) y los métodos:

```php
    public function index(Request $request): View
    {
        $user = $request->user();
        $isStudent = $user->role === Role::Estudiante;
        $term = AcademicTerm::current();
        $filters = [
            'q' => $request->string('q')->trim()->toString(),
            'tipo' => $request->string('tipo')->toString(),
            'estado' => $request->string('estado')->toString(),
            'periodo' => $request->string('periodo')->toString(),
        ];

        $scope = fn () => CommitteeRequest::query()
            ->when($isStudent, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('user_id', $user->id)));

        $requests = $scope()
            ->with(['student.user', 'type', 'term'])
            ->when($filters['q'] !== '' && ! $isStudent, function ($q) use ($filters) {
                $like = '%'.addcslashes($filters['q'], '\\%_').'%';
                $q->whereHas('student', fn ($s) => $s->where(fn ($w) => $w
                    ->where('codigo', 'ilike', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', $like))));
            })
            ->when($filters['tipo'] !== '', fn ($q) => $q->where('request_type_id', (int) $filters['tipo']))
            ->when($filters['periodo'] !== '', fn ($q) => $q->where('term_id', (int) $filters['periodo']))
            ->when($filters['estado'] !== '', fn ($q) => match ($filters['estado']) {
                'radicada', 'en_revision' => $q->where('estado', $filters['estado']),
                'aprobada', 'rechazada', 'aplazada' => $q->where('estado', 'con_decision')->where('decision', $filters['estado']),
                default => $q,
            })
            ->orderByDesc('fecha_radicacion')
            ->orderByDesc('id')
            ->paginate(config('program.page_size'))
            ->withQueryString();

        $inTerm = fn () => $scope()->where('term_id', $term?->id ?? 0);
        $total = $inTerm()->count();
        $pending = $inTerm()->where('estado', '!=', 'con_decision')->count();

        return view('requests.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'solicitudes'),
            'title' => $isStudent ? 'Mis solicitudes' : 'Solicitudes',
            'subtitle' => $total.' solicitudes en el periodo '.Labels::termLabel($term).' · '.$pending.' sin decisión · '.($isStudent ? 'RF-27' : 'RF-28'),
            'isStudent' => $isStudent,
            'canCreate' => $user->can('create', CommitteeRequest::class),
            'requests' => $requests->through(fn (CommitteeRequest $r) => $this->row($r)),
            'filters' => $filters,
            'typeOptions' => RequestType::orderBy('nombre')->pluck('nombre', 'id'),
            'statusOptions' => ['radicada' => 'Radicada', 'en_revision' => 'En revisión', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada', 'aplazada' => 'Aplazada'],
            'termOptions' => AcademicTerm::whereIn('id', CommitteeRequest::query()->select('term_id'))->orderByDesc('fecha_inicio')->pluck('codigo', 'id'),
        ]);
    }

    public function show(Request $request, RequestWorkflow $workflow, CommitteeRequest $solicitud): View
    {
        $this->authorize('view', $solicitud);

        $user = $request->user();
        $isStaff = $user->role !== Role::Estudiante;
        $solicitud->load(['student.user', 'type', 'term', 'attachments', 'statusHistory.user', 'minute']);
        [$status, $tone] = Labels::requestStatus($solicitud);
        $repeated = $user->role === Role::Coordinacion ? $workflow->repeatedCount($solicitud) : 0;

        $dots = [
            'radicada' => 'bg-[var(--color-primary-600)]',
            'en_revision' => 'bg-[var(--color-warning)]',
            'con_decision' => 'bg-[var(--color-success)]',
        ];
        $trail = $solicitud->statusHistory->sortBy('fecha')->map(fn ($h) => [
            'label' => Labels::stateLabel($h->estado_nuevo),
            'meta' => $h->fecha->format('d/m/Y H:i').' · '.$h->user->name,
            'dot' => $dots[$h->estado_nuevo] ?? 'bg-[var(--color-neutral-300)]',
        ])->values()->all();
        if ($solicitud->estado !== 'con_decision') {
            $trail[] = ['label' => 'Con decisión', 'meta' => 'Pendiente · la registra Coordinación', 'dot' => 'bg-[var(--color-neutral-300)]'];
        }

        return view('requests.show', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'solicitudes'),
            'solicitud' => [
                'id' => $solicitud->id,
                'number' => $solicitud->fecha_radicacion->year.'-'.str_pad((string) $solicitud->id, 4, '0', STR_PAD_LEFT),
                'type' => $solicitud->type->nombre,
                'status' => $status,
                'tone' => $tone,
                'filedAt' => $solicitud->fecha_radicacion->format('d/m/Y H:i'),
                'student' => $solicitud->student->user?->name ?? $solicitud->student->codigo,
                'code' => $solicitud->student->codigo,
                'term' => $solicitud->term->codigo,
                'description' => $solicitud->descripcion,
            ],
            'attachments' => $solicitud->attachments->map(fn (RequestAttachment $a) => [
                'name' => $a->nombre_original,
                'size' => Labels::bytes($a->tamano),
                'url' => route('requests.attachments.show', [$solicitud, $a]),
            ])->all(),
            'trail' => $trail,
            'studentCard' => $isStaff ? StudentProfile::header($solicitud->student) : null,
            'repeatedWarning' => $repeated > 0
                ? 'Es la '.($repeated + 1).'.ª solicitud de este tipo del estudiante en el periodo '.$solicitud->term->codigo.' · RF-29'
                : null,
            'decision' => $solicitud->estado === 'con_decision' ? [
                'text' => $status,
                'tone' => $tone,
                'acta' => $solicitud->minute?->numero_acta,
                'observations' => $solicitud->observaciones,
            ] : null,
            'canReview' => $user->role === Role::Coordinacion && $solicitud->estado === 'radicada',
            'canDecide' => $user->role === Role::Coordinacion && $solicitud->estado === 'en_revision',
            'decisionOptions' => ['aprobada' => 'Aprobada', 'rechazada' => 'Rechazada', 'aplazada' => 'Aplazada'],
        ]);
    }

    public function download(CommitteeRequest $solicitud, RequestAttachment $soporte): StreamedResponse
    {
        $this->authorize('view', $solicitud);
        abort_unless($soporte->request_id === $solicitud->id, 404);
        abort_unless(Storage::exists($soporte->ruta), 404);

        return Storage::download($soporte->ruta, $soporte->nombre_original);
    }

    private function row(CommitteeRequest $request): array
    {
        [$status, $tone] = Labels::requestStatus($request);

        return [
            'date' => Labels::date($request->fecha_radicacion),
            'student' => $request->student->user?->name ?? $request->student->codigo,
            'type' => $request->type->nombre,
            'period' => $request->term->codigo,
            'status' => $status,
            'tone' => $tone,
            'url' => route('requests.show', $request),
        ];
    }
```

`resources/views/requests/index.blade.php`:

```blade
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $title }} · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">{{ $title }}</h1>
                <p class="text-sm text-[var(--text-secondary)]">{{ $subtitle }}</p>
            </div>
            @if($canCreate)
                <x-button variant="primary" icon="file-plus" :href="route('requests.create')">Nueva solicitud</x-button>
            @endif
        </div>

        <form method="GET" action="{{ route('requests.index') }}" class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[14px] lg:flex-row lg:items-center">
            @unless($isStudent)
                <div class="flex h-[var(--control-height)] flex-1 items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3">
                    <x-lucide-search class="h-4 w-4 text-[var(--color-neutral-400)]" />
                    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por estudiante o código" class="w-full bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
                </div>
            @endunless
            <x-filter-select name="tipo" all="Tipo: todos" :options="$typeOptions" :selected="$filters['tipo']" />
            <x-filter-select name="estado" all="Estado: todos" :options="$statusOptions" :selected="$filters['estado']" />
            <x-filter-select name="periodo" all="Periodo: todos" :options="$termOptions" :selected="$filters['periodo']" />
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </form>

        <div class="hidden min-w-0 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] sm:flex">
            <div class="min-w-0 overflow-x-auto">
                <div class="flex min-w-[720px] gap-3 px-4 py-[11px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                    <span class="w-[100px]">RADICADA</span>
                    @unless($isStudent)
                        <span class="flex-1">ESTUDIANTE</span>
                    @endunless
                    <span class="{{ $isStudent ? 'flex-1' : 'w-[230px]' }}">TIPO</span>
                    <span class="w-20">PERIODO</span>
                    <span class="w-[130px]">ESTADO</span>
                    <span class="w-[100px]"></span>
                </div>
                @forelse($requests as $row)
                    <div class="flex min-w-[720px] items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm">
                        <span class="w-[100px] text-[var(--text-muted)]">{{ $row['date'] }}</span>
                        @unless($isStudent)
                            <span class="flex-1 font-semibold text-[var(--text-primary)]">{{ $row['student'] }}</span>
                        @endunless
                        <span class="{{ $isStudent ? 'flex-1' : 'w-[230px]' }} text-[var(--text-secondary)]">{{ $row['type'] }}</span>
                        <span class="w-20 text-[var(--text-secondary)]">{{ $row['period'] }}</span>
                        <span class="w-[130px]"><x-status-badge :text="$row['status']" :tone="$row['tone']" /></span>
                        <a href="{{ $row['url'] }}" class="w-[100px] text-sm font-semibold text-[var(--color-primary-600)]">Ver detalle</a>
                    </div>
                @empty
                    <div class="border-t border-[var(--border-subtle)] px-4 py-8 text-center text-sm text-[var(--text-muted)]">No hay solicitudes que coincidan con los filtros.</div>
                @endforelse
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:hidden">
            @forelse($requests as $row)
                <a href="{{ $row['url'] }}" class="flex flex-col gap-2 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-3">
                    <div class="flex items-center gap-2">
                        <span class="flex-1 text-sm font-medium text-[var(--text-primary)]">{{ $row['type'] }}</span>
                        <x-status-badge :text="$row['status']" :tone="$row['tone']" />
                    </div>
                    <p class="text-xs text-[var(--text-secondary)]">{{ $row['date'] }}@unless($isStudent) · {{ $row['student'] }}@endunless · {{ $row['period'] }}</p>
                </a>
            @empty
                <p class="rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-4 text-center text-sm text-[var(--text-muted)]">No hay solicitudes que coincidan con los filtros.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$requests" noun="solicitudes" />
    </div>
</x-layouts.app>
```

`resources/views/requests/show.blade.php`:

```blade
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Solicitud {{ $solicitud['number'] }} · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex items-center gap-[6px] text-sm text-[var(--text-secondary)]">
            <a href="/panel">Inicio</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <a href="{{ route('requests.index') }}">Solicitudes</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <span class="text-[var(--text-primary)]">Solicitud {{ $solicitud['number'] }}</span>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex flex-1 flex-col gap-[6px]">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold text-[var(--text-primary)]">{{ $solicitud['type'] }}</h1>
                    <x-status-badge :text="$solicitud['status']" :tone="$solicitud['tone']" />
                </div>
                <p class="text-sm text-[var(--text-secondary)]">Radicada el {{ $solicitud['filedAt'] }} por {{ $solicitud['student'] }} · Código {{ $solicitud['code'] }} · Periodo {{ $solicitud['term'] }}S</p>
            </div>
            <x-button variant="secondary" :href="route('requests.index')">Volver al listado</x-button>
        </div>

        @if($errors->has('estado'))
            <div class="flex gap-[10px] rounded-[var(--radius-md)] bg-[var(--color-danger-bg)] p-3">
                <x-lucide-circle-alert class="h-4 w-4 shrink-0 text-[var(--color-danger)]" />
                <p class="text-sm font-semibold text-[var(--color-danger)]">{{ $errors->first('estado') }}</p>
            </div>
        @endif

        <div class="flex flex-col gap-5 lg:flex-row">
            <div class="flex min-w-0 flex-1 flex-col gap-5">
                <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Descripción de la solicitud</h2>
                    <p class="whitespace-pre-line text-sm leading-relaxed text-[var(--text-secondary)]">{{ $solicitud['description'] }}</p>
                </div>

                <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Soportes adjuntos ({{ count($attachments) }})</h2>
                    @forelse($attachments as $file)
                        <div class="flex items-center gap-[10px] rounded-[var(--radius-md)] bg-[var(--surface-page)] px-3 py-[10px]">
                            <x-lucide-file-text class="h-4 w-4 text-[var(--color-primary-600)]" />
                            <span class="flex-1 truncate text-sm font-semibold text-[var(--text-primary)]">{{ $file['name'] }}</span>
                            <span class="text-xs text-[var(--text-muted)]">{{ $file['size'] }}</span>
                            <a href="{{ $file['url'] }}" class="text-sm font-semibold text-[var(--color-primary-600)]">Descargar</a>
                        </div>
                    @empty
                        <p class="text-sm text-[var(--text-muted)]">Esta solicitud no tiene soportes adjuntos.</p>
                    @endforelse
                </div>

                <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Trazabilidad de estados · RF-27</h2>
                    @foreach($trail as $step)
                        <div class="flex items-center gap-3">
                            <span class="h-[10px] w-[10px] shrink-0 rounded-full {{ $step['dot'] }}"></span>
                            <div class="flex flex-col gap-[2px]">
                                <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $step['label'] }}</span>
                                <span class="text-xs text-[var(--text-muted)]">{{ $step['meta'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-5 lg:w-[360px]">
                @if($studentCard)
                    <div class="flex flex-col gap-[10px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Estudiante</h2>
                        @foreach([['Nombre', $studentCard['name']], ['Código', $studentCard['code']], ['Cohorte', $studentCard['cohort']], ['PAPA actual', $studentCard['papa']], ['Tutor', $studentCard['tutor']]] as [$label, $value])
                            <div class="flex justify-between gap-3">
                                <span class="text-xs text-[var(--text-muted)]">{{ $label }}</span>
                                <span class="text-right text-sm font-semibold text-[var(--text-primary)]">{{ $value }}</span>
                            </div>
                        @endforeach
                        <a href="{{ route('students.show', $studentCard['id']) }}" class="text-sm font-semibold text-[var(--color-primary-600)]">Ver ficha del estudiante</a>
                    </div>
                @endif

                @if($repeatedWarning)
                    <div class="flex items-start gap-[10px] rounded-[var(--radius-md)] bg-[var(--color-warning-bg)] p-3">
                        <x-lucide-triangle-alert class="h-4 w-4 shrink-0 text-[var(--color-warning)]" />
                        <p class="text-xs text-[var(--color-warning)]">{{ $repeatedWarning }}</p>
                    </div>
                @endif

                @if($decision)
                    <div class="flex flex-col gap-[10px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Decisión del comité</h2>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-[var(--text-muted)]">Decisión</span>
                            <x-status-badge :text="$decision['text']" :tone="$decision['tone']" />
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-[var(--text-muted)]">Acta</span>
                            <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $decision['acta'] ?? '—' }}</span>
                        </div>
                        @if($decision['observations'])
                            <p class="whitespace-pre-line text-sm text-[var(--text-secondary)]">{{ $decision['observations'] }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
```

- [ ] **Step 4: Run tests to verify they pass, then the full suite**

Run: `php artisan test --filter='RequestIndexShowTest|PanelTest|AuthTest|EmptyDatabaseScreensTest|ShellUserTest' && php artisan test`
Expected: PASS; suite completa en verde (`RequestTest` aún usa el formulario mock hasta la Task 9).

- [ ] **Step 5: Commit**

```bash
git add app resources routes tests
git commit -m "feat(requests): agregar listado, detalle y descarga de soportes de solicitudes"
```

---

### Task 9: Radicar solicitud (formulario E1 con soportes)

**Files:**
- Create: `app/Http/Requests/StoreSolicitudRequest.php`, `tests/Feature/RequestStoreTest.php`
- Modify: `app/Http/Controllers/RequestController.php` (reemplazar `create`/`store`), `routes/web.php` (`POST /solicitudes`), `resources/views/requests/create.blade.php`, `tests/Feature/RequestTest.php`, `tests/Feature/EmptyDatabaseScreensTest.php`

**Interfaces:**
- Consumes: `RequestWorkflow::file`, `existingTypeNames`, `CommitteeRequestPolicy::create`, `x-form-field` (errores/old/readonly).
- Produces: `POST /solicitudes` (`requests.store`; campos `tipo` (id), `descripcion`, `soportes[]`, `codigo_estudiante` solo Coordinación) → redirige a `requests.show` con `status`; la vista `requests.create` recibe `requestTypes` (`id => nombre`), `termLabel`, `existing`, `isStaff`, `steps`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/RequestStoreTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use App\Models\RequestType;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->seed(DatabaseSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'tipo' => RequestType::where('nombre', 'Homologación de créditos')->firstOrFail()->id,
            'descripcion' => str_repeat('Solicito la homologación de créditos cursados. ', 3),
        ];
    }

    private function assertNothingWasStored(): void
    {
        $this->assertSame(2, CommitteeRequest::count());
        $this->assertDatabaseCount('request_attachments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_the_form_renders_with_type_ids_term_and_enctype(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $response = $this->get('/solicitudes/nueva');

        $response->assertOk();
        $response->assertSee('Nueva solicitud al comité asesor');
        $response->assertSee('multipart/form-data', false);
        $response->assertSee('2026-2');
        $response->assertSee('name="soportes[]"', false);
        $response->assertSee('Homologación de créditos');
        $response->assertSee('Ya radicó este periodo: Cancelación de asignatura');
        $response->assertDontSee('Guardar borrador');
        $response->assertDontSee('Falta un campo obligatorio');
        $response->assertDontSee('codigo_estudiante');
    }

    public function test_a_student_files_a_request_with_attachments(): void
    {
        $user = $this->signInSeeded(Role::Estudiante);

        $response = $this->post('/solicitudes', $this->payload([
            'soportes' => [
                UploadedFile::fake()->create('incapacidad.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->image('foto.png'),
            ],
        ]));

        $request = CommitteeRequest::latest('id')->firstOrFail();
        $response->assertRedirect(route('requests.show', $request));
        $response->assertSessionHas('status', 'Solicitud radicada correctamente.');
        $this->assertSame('radicada', $request->estado);
        $this->assertSame($user->student->id, $request->student_id);
        $this->assertSame('2026-2', $request->term->codigo);
        $this->assertCount(2, $request->attachments);
        Storage::disk('local')->assertExists($request->attachments->first()->ruta);
        $this->assertCount(1, $request->statusHistory);
        $this->assertSame($user->id, $request->statusHistory->first()->user_id);
    }

    public function test_the_student_always_files_for_themselves_even_if_a_code_is_sent(): void
    {
        $user = $this->signInSeeded(Role::Estudiante);

        $this->post('/solicitudes', $this->payload(['codigo_estudiante' => '2021045']))->assertRedirect();

        $this->assertSame($user->student->id, CommitteeRequest::latest('id')->firstOrFail()->student_id);
    }

    public function test_validation_errors_are_shown_next_to_the_field_and_keep_the_typed_text(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $response = $this->from('/solicitudes/nueva')->post('/solicitudes', ['descripcion' => 'Muy corta']);

        $response->assertRedirect('/solicitudes/nueva');
        $response->assertSessionHasErrors(['tipo' => 'Seleccione el tipo de solicitud antes de continuar.', 'descripcion' => 'La descripción debe tener al menos 50 caracteres.']);
        $this->assertNothingWasStored();

        $this->get('/solicitudes/nueva')
            ->assertSee('Seleccione el tipo de solicitud antes de continuar.')
            ->assertSee('Muy corta')
            ->assertSee('Revise los campos marcados');
    }

    public function test_an_unknown_request_type_is_rejected(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $this->post('/solicitudes', $this->payload(['tipo' => 999999]))->assertSessionHasErrors(['tipo' => 'El tipo de solicitud seleccionado no es válido.']);
        $this->assertNothingWasStored();
    }

    public function test_invalid_attachments_leave_nothing_stored(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $this->post('/solicitudes', $this->payload(['soportes' => [UploadedFile::fake()->create('grande.pdf', 6000, 'application/pdf')]]))
            ->assertSessionHasErrors(['soportes.0' => 'Cada soporte puede pesar máximo 5 MB.']);
        $this->post('/solicitudes', $this->payload(['soportes' => [UploadedFile::fake()->create('virus.exe', 10)]]))
            ->assertSessionHasErrors(['soportes.0' => 'Los soportes deben ser PDF, JPG o PNG.']);
        $this->post('/solicitudes', $this->payload(['soportes' => array_map(fn ($i) => UploadedFile::fake()->create("a{$i}.pdf", 10, 'application/pdf'), range(1, 6))]))
            ->assertSessionHasErrors(['soportes' => 'Puede adjuntar hasta 5 archivos.']);

        $this->assertNothingWasStored();
    }

    public function test_coordination_files_on_behalf_of_a_student_by_code(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $laura = Student::where('codigo', '2021045')->firstOrFail();

        $this->get('/solicitudes/nueva')->assertSee('codigo_estudiante');
        $this->post('/solicitudes', $this->payload())->assertSessionHasErrors(['codigo_estudiante' => 'Indique el código del estudiante.']);
        $this->post('/solicitudes', $this->payload(['codigo_estudiante' => '0000000']))->assertSessionHasErrors(['codigo_estudiante' => 'No existe un estudiante con ese código.']);
        $this->assertSame(2, CommitteeRequest::count());

        $this->post('/solicitudes', $this->payload(['codigo_estudiante' => '2021045']))->assertRedirect();

        $this->assertSame($laura->id, CommitteeRequest::latest('id')->firstOrFail()->student_id);
    }

    public function test_professors_and_tutors_cannot_file_and_guests_are_sent_to_login(): void
    {
        foreach ([Role::Profesor, Role::Tutor] as $role) {
            $this->signInSeeded($role);
            $this->post('/solicitudes', $this->payload())->assertForbidden();
            $this->get('/solicitudes/nueva')->assertForbidden();
        }
        $this->assertSame(2, CommitteeRequest::count());

        auth()->logout();
        $this->post('/solicitudes', $this->payload())->assertRedirect('/login');
    }

    public function test_a_student_user_without_a_student_record_cannot_file(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Estudiante]));

        $this->post('/solicitudes', $this->payload())->assertForbidden();
        $this->assertSame(2, CommitteeRequest::count());
    }
}
```

Añadir a `tests/Feature/EmptyDatabaseScreensTest.php` (con `use Tests\Concerns\CreatesStudents;`, `use App\Models\CommitteeRequest;`, `use App\Models\RequestType;` y `use CreatesStudents, RefreshDatabase;` en la clase):

```php
    public function test_filing_without_a_configured_term_is_refused_with_a_message(): void
    {
        $student = $this->makeStudent();
        $this->actingAs($student->user);
        $type = RequestType::create(['nombre' => 'Otras']);

        $this->post('/solicitudes', ['tipo' => $type->id, 'descripcion' => str_repeat('Motivo válido. ', 6)])
            ->assertSessionHasErrors(['tipo' => 'No hay un periodo académico vigente configurado.']);

        $this->assertSame(0, CommitteeRequest::count());
    }
```

Reemplazar `tests/Feature/RequestTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_form_renders_fields_steps_and_breadcrumb(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $response = $this->get('/solicitudes/nueva');

        $response->assertOk();
        $response->assertSee('Tipo de solicitud *');
        $response->assertSee('Descripción y justificación *');
        $response->assertSee('Radicar solicitud');
        $response->assertSee('¿Qué pasa después?');
        $response->assertSee('Mis solicitudes');
        $response->assertSee('PDF, JPG o PNG · máximo 5 MB por archivo');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='RequestStoreTest|RequestTest'`
Expected: FAIL (el formulario es mock; `POST /solicitudes` no existe).

- [ ] **Step 3: Implement**

`app/Http/Requests/StoreSolicitudRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CommitteeRequest::class);
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'integer', 'exists:request_types,id'],
            'descripcion' => ['required', 'string', 'min:50', 'max:5000'],
            'soportes' => ['nullable', 'array', 'max:'.config('program.attachment_max_files')],
            'soportes.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('program.attachment_max_kb')],
            'codigo_estudiante' => [
                Rule::requiredIf(fn () => $this->user()->role === Role::Coordinacion),
                'nullable', 'string', 'exists:students,codigo',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Seleccione el tipo de solicitud antes de continuar.',
            'tipo.integer' => 'El tipo de solicitud seleccionado no es válido.',
            'tipo.exists' => 'El tipo de solicitud seleccionado no es válido.',
            'descripcion.required' => 'Describa el motivo de la solicitud.',
            'descripcion.min' => 'La descripción debe tener al menos :min caracteres.',
            'descripcion.max' => 'La descripción no puede superar :max caracteres.',
            'soportes.array' => 'Los soportes no tienen un formato válido.',
            'soportes.max' => 'Puede adjuntar hasta :max archivos.',
            'soportes.*.file' => 'No se pudo cargar el archivo; verifique que no supere 5 MB.',
            'soportes.*.uploaded' => 'No se pudo cargar el archivo; verifique que no supere 5 MB.',
            'soportes.*.mimes' => 'Los soportes deben ser PDF, JPG o PNG.',
            'soportes.*.max' => 'Cada soporte puede pesar máximo 5 MB.',
            'codigo_estudiante.required' => 'Indique el código del estudiante.',
            'codigo_estudiante.exists' => 'No existe un estudiante con ese código.',
        ];
    }
}
```

`app/Http/Controllers/RequestController.php` — reemplazar `create()` y `store()` (borrar los datos mock anteriores):

```php
    public function create(Request $request, RequestWorkflow $workflow): View
    {
        $user = $request->user();
        $term = AcademicTerm::current();
        $student = $user->role === Role::Estudiante ? $user->student : null;

        return view('requests.create', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'solicitudes'),
            'isStaff' => $user->role === Role::Coordinacion,
            'requestTypes' => RequestType::orderBy('id')->pluck('nombre', 'id'),
            'termLabel' => $term?->codigo ?? 'Sin periodo',
            'existing' => $student && $term ? $workflow->existingTypeNames($student, $term) : [],
            'steps' => [
                ['number' => 1, 'title' => 'Radicada', 'detail' => 'Queda registrada con fecha y ya no puede editarla.'],
                ['number' => 2, 'title' => 'En revisión', 'detail' => 'El comité asesor estudia el caso.'],
                ['number' => 3, 'title' => 'Con decisión', 'detail' => 'Coordinación carga la decisión y el número de acta.'],
            ],
        ]);
    }

    public function store(StoreSolicitudRequest $request, RequestWorkflow $workflow): RedirectResponse
    {
        $user = $request->user();
        $student = $user->role === Role::Estudiante
            ? $user->student
            : Student::where('codigo', $request->input('codigo_estudiante'))->first();

        abort_if($student === null, 403, 'Su usuario no tiene una ficha de estudiante.');

        $term = AcademicTerm::current();
        if ($term === null) {
            return back()->withInput()->withErrors(['tipo' => 'No hay un periodo académico vigente configurado.']);
        }

        $solicitud = $workflow->file(
            $student,
            RequestType::findOrFail($request->integer('tipo')),
            $term,
            $request->string('descripcion')->toString(),
            $request->file('soportes', []),
            $user,
        );

        return redirect()->route('requests.show', $solicitud)->with('status', 'Solicitud radicada correctamente.');
    }
```
(imports: `App\Http\Requests\StoreSolicitudRequest`, `App\Models\Student`, `Illuminate\Http\RedirectResponse`.)

`routes/web.php` — en el grupo `role:estudiante,coordinacion` cambiar la ruta de guardado:

```php
        Route::post('/solicitudes', [RequestController::class, 'store'])->name('requests.store');
```

`resources/views/requests/create.blade.php` — reemplazar el archivo:

```blade
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Nueva solicitud · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex items-center gap-[6px] text-sm text-[var(--text-secondary)]">
            <a href="/panel">Inicio</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <a href="{{ route('requests.index') }}">Mis solicitudes</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <span class="text-[var(--text-primary)]">Nueva solicitud</span>
        </div>

        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Nueva solicitud al comité asesor</h1>
            <p class="text-sm text-[var(--text-secondary)]">Los campos marcados con * son obligatorios · RF-25</p>
        </div>

        <div class="flex flex-col gap-5 lg:flex-row">
            <form method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data" class="flex flex-1 flex-col gap-[18px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6">
                @csrf
                @if($isStaff)
                    <x-form-field label="Código del estudiante *" name="codigo_estudiante" icon="user" placeholder="p. ej. 2020123" help="La solicitud se radica a nombre de este estudiante." />
                @endif

                <div class="flex flex-col gap-4 sm:flex-row">
                    <div class="flex-1">
                        <x-form-field label="Tipo de solicitud *" name="tipo" type="select" help="Cancelación de asignatura, reingreso, homologación, cupo de créditos…">
                            <option value="">Seleccione…</option>
                            @foreach($requestTypes as $id => $nombre)
                                <option value="{{ $id }}" @selected((string) old('tipo') === (string) $id)>{{ $nombre }}</option>
                            @endforeach
                        </x-form-field>
                        @if(count($existing))
                            <p class="mt-1 text-[11px] font-medium text-[var(--color-warning)]">Ya radicó este periodo: {{ implode(', ', $existing) }}</p>
                        @endif
                    </div>
                    <div class="sm:w-[300px]">
                        <x-form-field label="Periodo académico *" name="periodo" :value="$termLabel" :readonly="true" help="Se toma del periodo activo." />
                    </div>
                </div>

                <x-form-field
                    label="Descripción y justificación *"
                    name="descripcion"
                    type="textarea"
                    placeholder="Describa el motivo de la solicitud y los hechos que la sustentan…"
                    help="Mínimo 50 caracteres."
                />

                @php($fileErrors = collect($errors->get('soportes.*'))->flatten()->merge($errors->get('soportes'))->unique())
                <div class="flex flex-col gap-[6px]">
                    <label for="soportes" class="text-xs font-semibold text-[var(--text-secondary)]">Soportes</label>
                    <div class="flex flex-col items-center justify-center gap-[6px] rounded-[var(--radius-md)] border border-dashed border-[var(--border-default)] p-4">
                        <x-lucide-cloud-upload class="h-[22px] w-[22px] text-[var(--color-neutral-400)]" />
                        <p class="text-sm text-[var(--text-secondary)]">Seleccione los archivos de soporte</p>
                        <input id="soportes" name="soportes[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="text-sm text-[var(--text-secondary)]" />
                        <p class="text-xs text-[var(--text-muted)]">PDF, JPG o PNG · máximo 5 MB por archivo · hasta 5 archivos</p>
                    </div>
                    @foreach($fileErrors as $message)
                        <p class="text-[11px] font-medium text-[var(--color-danger)]">{{ $message }}</p>
                    @endforeach
                </div>

                @if($errors->any())
                    <div class="flex gap-[10px] rounded-[var(--radius-md)] bg-[var(--color-danger-bg)] p-3">
                        <x-lucide-circle-alert class="h-4 w-4 shrink-0 text-[var(--color-danger)]" />
                        <div class="flex flex-col gap-[2px]">
                            <p class="text-sm font-semibold text-[var(--color-danger)]">Revise los campos marcados</p>
                            <ul class="list-disc pl-4 text-xs text-[var(--text-secondary)]">
                                @foreach($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                            <p class="text-xs text-[var(--text-secondary)]">Los datos escritos se conservan (RNF-10).</p>
                        </div>
                    </div>
                @endif

                <div class="flex flex-col gap-[10px] sm:flex-row sm:items-center">
                    <div class="flex-1"></div>
                    <x-button variant="secondary" :href="route('requests.index')">Cancelar</x-button>
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

- [ ] **Step 4: Run tests to verify they pass, then the full suite**

Run: `php artisan test --filter='RequestStoreTest|RequestTest|EmptyDatabaseScreensTest' && php artisan test`
Expected: PASS; suite completa en verde.

- [ ] **Step 5: Commit**

```bash
git add app resources routes tests
git commit -m "feat(requests): radicar solicitudes con validación, soportes y traza inicial"
```

---

### Task 10: Pasar a revisión y registrar la decisión del comité

**Files:**
- Create: `app/Http/Requests/DecideRequestRequest.php`, `tests/Feature/RequestDecisionTest.php`
- Modify: `app/Http/Controllers/RequestController.php` (añadir `startReview`, `decide`), `routes/web.php`, `resources/views/requests/show.blade.php`

**Interfaces:**
- Consumes: `RequestWorkflow::startReview/decide`, `CommitteeRequestPolicy::decide`, `x-form-field` select/textarea.
- Produces: `POST /solicitudes/{solicitud}/revision` (`requests.review`) y `POST /solicitudes/{solicitud}/decision` (`requests.decision`; campos `decision`, `numero_acta`, `observaciones`), solo Coordinación; ambos redirigen al detalle con `status`, o con el error `estado` si el flujo no lo permite.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/RequestDecisionTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CommitteeMinute;
use App\Models\CommitteeRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestDecisionTest extends TestCase
{
    use RefreshDatabase;

    private CommitteeRequest $juan;

    private CommitteeRequest $laura;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->juan = CommitteeRequest::whereHas('student', fn ($q) => $q->where('codigo', '2020123'))->firstOrFail();
        $this->laura = CommitteeRequest::whereHas('student', fn ($q) => $q->where('codigo', '2021045'))->firstOrFail();
    }

    private function decision(array $overrides = []): array
    {
        return $overrides + ['decision' => 'aprobada', 'numero_acta' => '12-2026', 'observaciones' => 'Procede por soporte médico.'];
    }

    public function test_coordination_moves_a_request_through_review_and_decision_with_a_full_trail(): void
    {
        $coordinator = $this->signInSeeded(Role::Coordinacion);
        $url = '/solicitudes/'.$this->juan->id;

        $this->get($url)->assertSee('Pasar a revisión')->assertDontSee('Registrar decisión');

        $this->post($url.'/revision')->assertRedirect($url)->assertSessionHas('status');
        $this->assertSame('en_revision', $this->juan->fresh()->estado);

        $this->get($url)->assertSee('Registrar decisión')->assertDontSee('Pasar a revisión');

        $this->post($url.'/decision', $this->decision())->assertRedirect($url)->assertSessionHas('status');

        $request = $this->juan->fresh();
        $this->assertSame('con_decision', $request->estado);
        $this->assertSame('aprobada', $request->decision);
        $this->assertSame('12-2026', $request->minute->numero_acta);
        $this->assertSame(
            [['radicada', null], ['en_revision', 'radicada'], ['con_decision', 'en_revision']],
            $request->statusHistory()->orderBy('id')->get()->map(fn ($h) => [$h->estado_nuevo, $h->estado_anterior])->all()
        );
        $this->assertSame($coordinator->id, $request->statusHistory()->latest('id')->first()->user_id);

        $this->get($url)->assertSee('Aprobada')->assertSee('12-2026')->assertSee('Procede por soporte médico.')->assertDontSee('Registrar decisión');
    }

    public function test_deciding_without_reviewing_first_is_refused_and_changes_nothing(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $url = '/solicitudes/'.$this->juan->id;

        $this->from($url)->post($url.'/decision', $this->decision())
            ->assertRedirect($url)
            ->assertSessionHasErrors(['estado' => 'Pase la solicitud a revisión antes de registrar la decisión.']);

        $this->assertSame('radicada', $this->juan->fresh()->estado);
        $this->assertSame(1, $this->juan->statusHistory()->count());
        $this->assertSame(0, CommitteeMinute::count());
    }

    public function test_a_second_decision_is_refused_and_keeps_the_first_one(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $url = '/solicitudes/'.$this->laura->id;

        $this->post($url.'/decision', $this->decision())->assertSessionHasNoErrors();
        $this->from($url)->post($url.'/decision', $this->decision(['decision' => 'rechazada', 'numero_acta' => '13-2026']))
            ->assertSessionHasErrors(['estado' => 'La solicitud ya tiene una decisión registrada.']);

        $request = $this->laura->fresh();
        $this->assertSame('aprobada', $request->decision);
        $this->assertSame('12-2026', $request->minute->numero_acta);
        $this->assertSame(2, $request->statusHistory()->count());
        $this->assertSame(1, CommitteeMinute::count());
    }

    public function test_reviewing_twice_is_refused(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $url = '/solicitudes/'.$this->laura->id;

        $this->from($url)->post($url.'/revision')->assertSessionHasErrors(['estado' => 'La solicitud ya fue pasada a revisión.']);
        $this->assertSame(1, $this->laura->statusHistory()->count());
    }

    public function test_invalid_decision_data_is_rejected_with_messages_and_old_input(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $url = '/solicitudes/'.$this->laura->id;

        $this->from($url)->post($url.'/decision', ['decision' => 'quizás', 'observaciones' => 'Texto previo'])
            ->assertRedirect($url)
            ->assertSessionHasErrors(['decision', 'numero_acta']);

        $this->assertSame('en_revision', $this->laura->fresh()->estado);
        $this->get($url)->assertSee('Texto previo')->assertSee('Indique el número de acta.');
    }

    public function test_only_coordination_can_review_or_decide(): void
    {
        $url = '/solicitudes/'.$this->laura->id;

        foreach ([Role::Estudiante, Role::Profesor, Role::Tutor] as $role) {
            $this->signInSeeded($role);
            $this->post($url.'/revision')->assertForbidden();
            $this->post($url.'/decision', $this->decision())->assertForbidden();
        }

        $this->signInAsOtherStudent();
        $this->post($url.'/decision', $this->decision())->assertForbidden();

        $this->assertSame('en_revision', $this->laura->fresh()->estado);
        $this->assertSame(1, $this->laura->statusHistory()->count());
    }

    public function test_guests_are_sent_to_login_and_unknown_requests_return_404(): void
    {
        $this->post('/solicitudes/'.$this->laura->id.'/decision', $this->decision())->assertRedirect('/login');

        $this->signInSeeded(Role::Coordinacion);
        $this->post('/solicitudes/999999/revision')->assertNotFound();
        $this->post('/solicitudes/abc/decision', $this->decision())->assertNotFound();
    }

    private function signInAsOtherStudent(): void
    {
        $this->actingAs(User::where('email', 'est2021045@unal.edu.co')->firstOrFail());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=RequestDecisionTest`
Expected: FAIL (rutas inexistentes; el detalle no tiene formularios).

- [ ] **Step 3: Implement**

`app/Http/Requests/DecideRequestRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Services\RequestWorkflow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('decide', $this->route('solicitud'));
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(RequestWorkflow::DECISIONS)],
            'numero_acta' => ['required', 'string', 'max:30'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Seleccione la decisión del comité.',
            'decision.in' => 'La decisión seleccionada no es válida.',
            'numero_acta.required' => 'Indique el número de acta.',
            'numero_acta.max' => 'El número de acta no puede superar :max caracteres.',
            'observaciones.max' => 'Las observaciones no pueden superar :max caracteres.',
        ];
    }
}
```

`routes/web.php` — dentro del grupo `auth`, junto a las rutas de solicitudes:

```php
    Route::middleware('role:coordinacion')->group(function () {
        Route::post('/solicitudes/{solicitud}/revision', [RequestController::class, 'startReview'])->whereNumber('solicitud')->name('requests.review');
        Route::post('/solicitudes/{solicitud}/decision', [RequestController::class, 'decide'])->whereNumber('solicitud')->name('requests.decision');
    });
```

`app/Http/Controllers/RequestController.php` — imports `App\Http\Requests\DecideRequestRequest`, `DomainException`, y métodos:

```php
    public function startReview(Request $request, RequestWorkflow $workflow, CommitteeRequest $solicitud): RedirectResponse
    {
        $this->authorize('decide', $solicitud);

        try {
            $workflow->startReview($solicitud, $request->user());
        } catch (DomainException $e) {
            return redirect()->route('requests.show', $solicitud)->withErrors(['estado' => $e->getMessage()]);
        }

        return redirect()->route('requests.show', $solicitud)->with('status', 'La solicitud pasó a revisión.');
    }

    public function decide(DecideRequestRequest $request, RequestWorkflow $workflow, CommitteeRequest $solicitud): RedirectResponse
    {
        try {
            $workflow->decide(
                $solicitud,
                $request->string('decision')->toString(),
                $request->string('numero_acta')->trim()->toString(),
                $request->filled('observaciones') ? $request->string('observaciones')->toString() : null,
                $request->user(),
            );
        } catch (DomainException $e) {
            return redirect()->route('requests.show', $solicitud)->withErrors(['estado' => $e->getMessage()])->withInput();
        }

        return redirect()->route('requests.show', $solicitud)->with('status', 'Decisión registrada correctamente.');
    }
```

`resources/views/requests/show.blade.php` — dentro de la columna lateral (`lg:w-[360px]`), después del bloque `@if($decision) … @endif`, añadir:

```blade
                @if($canReview)
                    <form method="POST" action="{{ route('requests.review', $solicitud['id']) }}" class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5">
                        @csrf
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Revisión del comité</h2>
                        <p class="text-xs text-[var(--text-secondary)]">La solicitud está radicada. Pásela a revisión para poder registrar la decisión.</p>
                        <x-button variant="primary" type="submit" class="w-full justify-center">Pasar a revisión</x-button>
                    </form>
                @endif

                @if($canDecide)
                    <form method="POST" action="{{ route('requests.decision', $solicitud['id']) }}" class="flex flex-col gap-4 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-5">
                        @csrf
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-sm font-semibold text-[var(--text-primary)]">Registrar decisión</h2>
                            <span class="inline-flex items-center gap-1 rounded-full bg-[var(--color-restricted-bg)] px-2 py-[3px] text-[11px] font-semibold text-[var(--color-restricted)]">
                                <x-lucide-lock class="h-3 w-3" /> Solo Coordinación
                            </span>
                        </div>
                        <x-form-field label="Decisión *" name="decision" type="select" help="Aprobada, rechazada o aplazada">
                            <option value="">Seleccione…</option>
                            @foreach($decisionOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('decision') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-form-field>
                        <x-form-field label="Número de acta *" name="numero_acta" placeholder="p. ej. 12-2026" help="Acta del comité o del consejo" />
                        <x-form-field label="Observaciones" name="observaciones" type="textarea" :rows="4" placeholder="Motivo de la decisión…" />
                        <x-button variant="primary" type="submit" class="w-full justify-center">Registrar decisión</x-button>
                    </form>
                @endif
```

- [ ] **Step 4: Run tests to verify they pass, then the full suite**

Run: `php artisan test --filter=RequestDecisionTest && php artisan test`
Expected: PASS; suite completa en verde.

- [ ] **Step 5: Commit**

```bash
git add app resources routes tests
git commit -m "feat(requests): agregar paso a revisión y registro de la decisión del comité"
```

---

### Task 11: CRUD de tutorías con ventana de edición de 24 horas (T3)

**Files:**
- Create: `app/Policies/TutorshipPolicy.php`, `app/Http/Requests/StoreTutorshipRequest.php`, `app/Http/Controllers/TutorshipController.php`, `resources/views/tutorships/form.blade.php`, `tests/Feature/TutorshipPolicyTest.php`, `tests/Feature/TutorshipTest.php`
- Modify: `app/Providers/AuthServiceProvider.php`, `routes/web.php`, `app/Http/Controllers/StudentController.php` (`show`), `app/Support/StudentProfile.php` (`tutorials` con `editUrl`), `resources/views/students/show.blade.php`

**Interfaces:**
- Consumes: `StudentProfile::header`, `Labels`, `config('program.tutorship_reasons'|'tutorship_edit_hours')`, `x-form-field`.
- Produces:
  - Habilidades sobre `Tutorship`: `create` (tutor, coordinación); `update`/`delete` solo el autor y solo mientras `created_at` sea posterior a `now() − 24 h` (a las 24 h exactas ya es inmutable).
  - Rutas (`role:tutor,coordinacion`): `GET /estudiantes/{student}/tutorias/crear` (`tutorships.create`), `POST /estudiantes/{student}/tutorias` (`tutorships.store`), `GET /tutorias/{tutoria}/editar` (`tutorships.edit`), `PUT /tutorias/{tutoria}` (`tutorships.update`), `DELETE /tutorias/{tutoria}` (`tutorships.destroy`). Todas redirigen a la ficha del estudiante con `status`.
  - La ficha muestra el botón "Registrar tutoría" solo a quien puede crear y el enlace "Editar" solo en tutorías editables por el usuario.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/TutorshipPolicyTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Tutorship;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TutorshipPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function tutorship(int $tutorId, string $createdAt): Tutorship
    {
        $tutorship = new Tutorship;
        $tutorship->tutor_id = $tutorId;
        $tutorship->created_at = Carbon::parse($createdAt);

        return $tutorship;
    }

    private function allows(Role $role, string $ability, mixed $argument, int $id = 7): bool
    {
        return Gate::forUser(User::factory()->make(['id' => $id, 'role' => $role]))->allows($ability, $argument);
    }

    public function test_only_tutors_and_coordination_create(): void
    {
        $this->assertTrue($this->allows(Role::Tutor, 'create', Tutorship::class));
        $this->assertTrue($this->allows(Role::Coordinacion, 'create', Tutorship::class));
        $this->assertFalse($this->allows(Role::Profesor, 'create', Tutorship::class));
        $this->assertFalse($this->allows(Role::Estudiante, 'create', Tutorship::class));
    }

    public function test_the_author_edits_and_deletes_only_inside_the_24_hour_window(): void
    {
        $this->assertTrue($this->allows(Role::Tutor, 'update', $this->tutorship(7, '2026-10-11 12:01:00')));
        $this->assertTrue($this->allows(Role::Tutor, 'delete', $this->tutorship(7, '2026-10-11 12:01:00')));
        $this->assertFalse($this->allows(Role::Tutor, 'update', $this->tutorship(7, '2026-10-11 12:00:00')));
        $this->assertFalse($this->allows(Role::Tutor, 'update', $this->tutorship(7, '2026-10-11 11:59:00')));
        $this->assertFalse($this->allows(Role::Tutor, 'delete', $this->tutorship(7, '2026-10-11 11:59:00')));
    }

    public function test_nobody_else_edits_even_inside_the_window(): void
    {
        foreach ([Role::Tutor, Role::Coordinacion, Role::Profesor, Role::Estudiante] as $role) {
            $this->assertFalse($this->allows($role, 'update', $this->tutorship(7, '2026-10-12 11:00:00'), 99));
        }
    }
}
```

```php
<?php
// tests/Feature/TutorshipTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use App\Models\Tutorship;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutorshipTest extends TestCase
{
    use RefreshDatabase;

    private Student $juan;

    private User $tutor;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 12:00:00');
        $this->seed(DatabaseSeeder::class);
        $this->juan = Student::where('codigo', '2020123')->firstOrFail();
        $this->tutor = $this->signInSeeded(Role::Tutor);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function valid(array $overrides = []): array
    {
        return $overrides + [
            'fecha' => '2026-10-12',
            'motivo' => 'Revisión de avance',
            'observaciones' => 'Se revisó el avance del semestre.',
            'compromisos' => 'Entregar el informe parcial.',
        ];
    }

    private function createTutorship(): Tutorship
    {
        return Tutorship::create([
            'student_id' => $this->juan->id, 'tutor_id' => $this->tutor->id, 'fecha' => '2026-10-12',
            'motivo' => 'Alerta temprana', 'observaciones' => 'Observación de prueba.',
        ]);
    }

    private function storeUrl(): string
    {
        return '/estudiantes/'.$this->juan->id.'/tutorias';
    }

    // ---- crear ----

    public function test_the_form_shows_the_student_history_and_the_immutability_notice(): void
    {
        $response = $this->get($this->storeUrl().'/crear');

        $response->assertOk();
        $response->assertSee('Registrar tutoría');
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Tutor asignado: Carlos Pineda');
        $response->assertSee('2026-2');
        $response->assertSee('Seguimiento a carga académica');
        $response->assertSee('Podrá corregir esta sesión durante 24 horas');
    }

    public function test_a_tutor_registers_a_tutorship_and_sees_it_in_the_profile(): void
    {
        $response = $this->post($this->storeUrl(), $this->valid());

        $response->assertRedirect(route('students.show', $this->juan));
        $response->assertSessionHas('status', 'Tutoría registrada correctamente.');
        $tutorship = Tutorship::where('observaciones', 'Se revisó el avance del semestre.')->firstOrFail();
        $this->assertSame($this->tutor->id, $tutorship->tutor_id);
        $this->assertSame($this->juan->id, $tutorship->student_id);
        $this->assertSame('Entregar el informe parcial.', $tutorship->compromisos);

        $this->get(route('students.show', $this->juan))->assertSee('Se revisó el avance del semestre.')->assertSee('Tutoría registrada correctamente.');
    }

    public function test_the_author_is_always_the_signed_in_user_even_if_another_is_sent(): void
    {
        $other = User::factory()->create(['role' => Role::Tutor]);

        $this->post($this->storeUrl(), $this->valid(['tutor_id' => $other->id]))->assertRedirect();

        $this->assertSame($this->tutor->id, Tutorship::latest('id')->firstOrFail()->tutor_id);
    }

    public function test_validation_messages_and_old_input_and_nothing_is_stored(): void
    {
        $before = Tutorship::count();

        $this->from($this->storeUrl().'/crear')->post($this->storeUrl(), ['fecha' => '2026-10-13', 'motivo' => 'Inventado', 'observaciones' => 'corta'])
            ->assertSessionHasErrors([
                'fecha' => 'La fecha de la sesión no puede ser posterior a hoy.',
                'motivo' => 'Seleccione un motivo de la lista.',
                'observaciones' => 'Describa lo tratado en la sesión (mínimo 10 caracteres).',
            ]);
        $this->post($this->storeUrl(), [])->assertSessionHasErrors(['fecha', 'motivo', 'observaciones']);
        $this->assertSame($before, Tutorship::count());

        $this->post($this->storeUrl(), ['fecha' => '2026-10-13', 'motivo' => 'Inventado', 'observaciones' => 'corta']);
        $this->get($this->storeUrl().'/crear')->assertSee('corta')->assertSee('La fecha de la sesión no puede ser posterior a hoy.');
    }

    public function test_commitments_are_optional(): void
    {
        $this->post($this->storeUrl(), $this->valid(['compromisos' => null]))->assertSessionHasNoErrors();
    }

    public function test_coordination_can_register_but_professors_and_students_cannot(): void
    {
        $coordinator = $this->signInSeeded(Role::Coordinacion);
        $this->post($this->storeUrl(), $this->valid())->assertRedirect();
        $this->assertSame($coordinator->id, Tutorship::latest('id')->firstOrFail()->tutor_id);

        $count = Tutorship::count();
        foreach ([Role::Profesor, Role::Estudiante] as $role) {
            $this->signInSeeded($role);
            $this->get($this->storeUrl().'/crear')->assertForbidden();
            $this->post($this->storeUrl(), $this->valid())->assertForbidden();
        }
        $this->assertSame($count, Tutorship::count());
    }

    public function test_unknown_and_non_numeric_students_return_404(): void
    {
        $this->get('/estudiantes/999999/tutorias/crear')->assertNotFound();
        $this->post('/estudiantes/999999/tutorias', $this->valid())->assertNotFound();
        $this->get('/estudiantes/abc/tutorias/crear')->assertNotFound();
    }

    // ---- editar / eliminar ----

    public function test_the_author_edits_up_to_the_last_minute_of_the_window(): void
    {
        $tutorship = $this->createTutorship();
        Carbon::setTestNow('2026-10-13 11:59:00');

        $this->get('/tutorias/'.$tutorship->id.'/editar')->assertOk()->assertSee('Observación de prueba.')->assertSee('Editar tutoría');
        $this->put('/tutorias/'.$tutorship->id, $this->valid(['observaciones' => 'Observación corregida a tiempo.']))
            ->assertRedirect(route('students.show', $this->juan))
            ->assertSessionHas('status', 'Tutoría actualizada correctamente.');

        $this->assertSame('Observación corregida a tiempo.', $tutorship->fresh()->observaciones);
        $this->assertSame($this->tutor->id, $tutorship->fresh()->tutor_id);
    }

    public function test_after_24_hours_the_tutorship_is_immutable(): void
    {
        $tutorship = $this->createTutorship();
        Carbon::setTestNow('2026-10-13 12:01:00');

        $this->get('/tutorias/'.$tutorship->id.'/editar')->assertForbidden();
        $this->put('/tutorias/'.$tutorship->id, $this->valid(['observaciones' => 'Intento tardío de corrección.']))->assertForbidden();
        $this->delete('/tutorias/'.$tutorship->id)->assertForbidden();

        $this->assertSame('Observación de prueba.', $tutorship->fresh()->observaciones);
        $this->assertNull($tutorship->fresh()->deleted_at);
    }

    public function test_another_tutor_or_coordination_cannot_touch_it(): void
    {
        $tutorship = $this->createTutorship();

        foreach ([User::factory()->create(['role' => Role::Tutor]), $this->signInSeeded(Role::Coordinacion)] as $user) {
            $this->actingAs($user);
            $this->get('/tutorias/'.$tutorship->id.'/editar')->assertForbidden();
            $this->put('/tutorias/'.$tutorship->id, $this->valid())->assertForbidden();
            $this->delete('/tutorias/'.$tutorship->id)->assertForbidden();
        }

        $this->assertNull($tutorship->fresh()->deleted_at);
    }

    public function test_invalid_edits_are_rejected_and_keep_the_stored_values(): void
    {
        $tutorship = $this->createTutorship();

        $this->from('/tutorias/'.$tutorship->id.'/editar')->put('/tutorias/'.$tutorship->id, $this->valid(['motivo' => 'Inventado']))
            ->assertSessionHasErrors('motivo');

        $this->assertSame('Alerta temprana', $tutorship->fresh()->motivo);
    }

    public function test_deleting_is_logical_and_the_tutorship_disappears_from_the_profile(): void
    {
        $tutorship = $this->createTutorship();

        $this->delete('/tutorias/'.$tutorship->id)->assertRedirect(route('students.show', $this->juan))->assertSessionHas('status', 'Tutoría eliminada.');

        $this->assertSame(1, Tutorship::withTrashed()->whereKey($tutorship->id)->count());
        $this->assertSame(0, Tutorship::whereKey($tutorship->id)->count());
        $this->get(route('students.show', $this->juan))->assertDontSee('Observación de prueba.');
        $this->get('/tutorias/'.$tutorship->id.'/editar')->assertNotFound();
        $this->get('/tutorias/999999/editar')->assertNotFound();
        $this->get('/tutorias/abc/editar')->assertNotFound();
    }

    // ---- ficha ----

    public function test_the_profile_offers_register_and_edit_only_to_those_who_can_use_them(): void
    {
        $create = 'href="'.route('tutorships.create', $this->juan).'"';
        $edit = 'href="'.route('tutorships.edit', Tutorship::where('student_id', $this->juan->id)->firstOrFail()).'"';

        $this->get(route('students.show', $this->juan))->assertSee($create, false)->assertSee($edit, false);

        $this->signInSeeded(Role::Coordinacion);
        $this->get(route('students.show', $this->juan))->assertSee($create, false)->assertDontSee($edit, false);

        $this->signInSeeded(Role::Profesor);
        $this->get(route('students.show', $this->juan))->assertDontSee($create, false)->assertDontSee($edit, false);

        $this->signInSeeded(Role::Tutor);
        Carbon::setTestNow('2026-10-13 12:01:00');
        $this->get(route('students.show', $this->juan))->assertSee($create, false)->assertDontSee($edit, false);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='TutorshipPolicyTest|TutorshipTest'`
Expected: FAIL (política, rutas y vista inexistentes).

- [ ] **Step 3: Implement**

`app/Policies/TutorshipPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Tutorship;
use App\Models\User;

class TutorshipPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, [Role::Tutor, Role::Coordinacion], true);
    }

    public function update(User $user, Tutorship $tutorship): bool
    {
        return $this->isAuthorInsideWindow($user, $tutorship);
    }

    public function delete(User $user, Tutorship $tutorship): bool
    {
        return $this->isAuthorInsideWindow($user, $tutorship);
    }

    private function isAuthorInsideWindow(User $user, Tutorship $tutorship): bool
    {
        return $user->id === $tutorship->tutor_id
            && $tutorship->created_at->gt(now()->subHours(config('program.tutorship_edit_hours')));
    }
}
```

`app/Providers/AuthServiceProvider.php` — añadir a `$policies`: `Tutorship::class => TutorshipPolicy::class,` (imports `App\Models\Tutorship`, `App\Policies\TutorshipPolicy`).

`app/Http/Requests/StoreTutorshipRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Models\Tutorship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTutorshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tutorship = $this->route('tutoria');

        return $tutorship
            ? $this->user()->can('update', $tutorship)
            : $this->user()->can('create', Tutorship::class);
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'motivo' => ['required', Rule::in(config('program.tutorship_reasons'))],
            'observaciones' => ['required', 'string', 'min:10', 'max:2000'],
            'compromisos' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'Indique la fecha de la sesión.',
            'fecha.date' => 'La fecha de la sesión no es válida.',
            'fecha.before_or_equal' => 'La fecha de la sesión no puede ser posterior a hoy.',
            'motivo.required' => 'Seleccione el motivo de la sesión.',
            'motivo.in' => 'Seleccione un motivo de la lista.',
            'observaciones.required' => 'Describa lo tratado en la sesión.',
            'observaciones.min' => 'Describa lo tratado en la sesión (mínimo :min caracteres).',
            'observaciones.max' => 'Las observaciones no pueden superar :max caracteres.',
            'compromisos.max' => 'Los compromisos no pueden superar :max caracteres.',
        ];
    }
}
```

`app/Http/Controllers/TutorshipController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreTutorshipRequest;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\Tutorship;
use App\Support\Labels;
use App\Support\Navigation;
use App\Support\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorshipController extends Controller
{
    public function create(Request $request, Student $student): View
    {
        $this->authorize('create', Tutorship::class);

        return $this->form($request, $student, null);
    }

    public function store(StoreTutorshipRequest $request, Student $student): RedirectResponse
    {
        $student->tutorships()->create($request->validated() + ['tutor_id' => $request->user()->id]);

        return redirect()->route('students.show', $student)->with('status', 'Tutoría registrada correctamente.');
    }

    public function edit(Request $request, Tutorship $tutoria): View
    {
        $this->authorize('update', $tutoria);

        return $this->form($request, $tutoria->student, $tutoria);
    }

    public function update(StoreTutorshipRequest $request, Tutorship $tutoria): RedirectResponse
    {
        $tutoria->update($request->validated());

        return redirect()->route('students.show', $tutoria->student_id)->with('status', 'Tutoría actualizada correctamente.');
    }

    public function destroy(Request $request, Tutorship $tutoria): RedirectResponse
    {
        $this->authorize('delete', $tutoria);
        $tutoria->delete();

        return redirect()->route('students.show', $tutoria->student_id)->with('status', 'Tutoría eliminada.');
    }

    private function form(Request $request, Student $student, ?Tutorship $tutorship): View
    {
        $user = $request->user();

        return view('tutorships.form', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, $user->role === Role::Tutor ? 'registrar-tutoria' : 'estudiantes'),
            'student' => StudentProfile::header($student),
            'tutorship' => $tutorship,
            'reasons' => config('program.tutorship_reasons'),
            'history' => $student->tutorships()->latest('fecha')->limit(3)->get()
                ->map(fn (Tutorship $t) => ['date' => Labels::date($t->fecha), 'reason' => $t->motivo])->all(),
            'termLabel' => AcademicTerm::current()?->codigo ?? 'Sin periodo',
            'hours' => config('program.tutorship_edit_hours'),
        ]);
    }
}
```

`routes/web.php` — dentro de `auth`:

```php
    Route::middleware('role:tutor,coordinacion')->group(function () {
        Route::get('/estudiantes/{student}/tutorias/crear', [TutorshipController::class, 'create'])->whereNumber('student')->name('tutorships.create');
        Route::post('/estudiantes/{student}/tutorias', [TutorshipController::class, 'store'])->whereNumber('student')->name('tutorships.store');
        Route::get('/tutorias/{tutoria}/editar', [TutorshipController::class, 'edit'])->whereNumber('tutoria')->name('tutorships.edit');
        Route::put('/tutorias/{tutoria}', [TutorshipController::class, 'update'])->whereNumber('tutoria')->name('tutorships.update');
        Route::delete('/tutorias/{tutoria}', [TutorshipController::class, 'destroy'])->whereNumber('tutoria')->name('tutorships.destroy');
    });
```
(import `App\Http\Controllers\TutorshipController`).

`resources/views/tutorships/form.blade.php`:

```blade
@php($editing = $tutorship !== null)
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $editing ? 'Editar tutoría' : 'Registrar tutoría' }} · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex items-center gap-[6px] text-sm text-[var(--text-secondary)]">
            <a href="/panel">Inicio</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <a href="{{ route('students.index') }}">Estudiantes</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <a href="{{ route('students.show', $student['id']) }}">{{ $student['name'] }}</a>
            <x-lucide-chevron-right class="h-[13px] w-[13px]" />
            <span class="text-[var(--text-primary)]">{{ $editing ? 'Editar tutoría' : 'Registrar tutoría' }}</span>
        </div>

        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">{{ $editing ? 'Editar tutoría' : 'Registrar tutoría' }}</h1>
            <p class="text-sm text-[var(--text-secondary)]">Queda asociada al estudiante y a su tutor, y no podrá editarse pasadas {{ $hours }} horas · RF-34</p>
        </div>

        <div class="flex flex-col gap-5 lg:flex-row">
            <div class="flex min-w-0 flex-1 flex-col gap-4">
                <form method="POST" action="{{ $editing ? route('tutorships.update', $tutorship) : route('tutorships.store', $student['id']) }}" class="flex flex-col gap-[18px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6">
                    @csrf
                    @if($editing)
                        @method('PUT')
                    @endif

                    <div class="flex items-center gap-3 rounded-[var(--radius-md)] bg-[var(--surface-page)] p-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-xs font-semibold text-[var(--color-primary-700)]">{{ $student['initials'] }}</div>
                        <div class="flex flex-col gap-[2px]">
                            <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $student['name'] }}</span>
                            <span class="text-xs text-[var(--text-muted)]">{{ $student['code'] }} · {{ $student['cohort'] }} · {{ $student['tutor'] }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="flex-1">
                            <x-form-field label="Fecha de la sesión *" name="fecha" type="date" icon="calendar" :value="$tutorship?->fecha?->format('Y-m-d') ?? now()->format('Y-m-d')" help="No puede ser posterior a hoy" />
                        </div>
                        <div class="flex-1">
                            <x-form-field label="Periodo académico" name="periodo" :value="$termLabel" :readonly="true" icon="lock" help="Se toma del calendario vigente" />
                        </div>
                    </div>

                    <x-form-field label="Motivo *" name="motivo" type="select" help="Seguimiento, orientación, revisión de avance, otro">
                        <option value="">Seleccione el motivo de la sesión</option>
                        @foreach($reasons as $reason)
                            <option value="{{ $reason }}" @selected(old('motivo', $tutorship?->motivo) === $reason)>{{ $reason }}</option>
                        @endforeach
                    </x-form-field>
                    <x-form-field label="Observaciones *" name="observaciones" type="textarea" :rows="6" :value="$tutorship?->observaciones" placeholder="Describa lo tratado en la sesión…" help="Mínimo 10 caracteres" />
                    <x-form-field label="Compromisos" name="compromisos" type="textarea" :rows="4" :value="$tutorship?->compromisos" placeholder="Acuerdos del estudiante y del tutor…" />

                    <div class="flex flex-col gap-[10px] sm:flex-row sm:items-center">
                        <div class="flex-1"></div>
                        <x-button variant="secondary" :href="route('students.show', $student['id'])">Cancelar</x-button>
                        <x-button variant="primary" type="submit">{{ $editing ? 'Guardar cambios' : 'Registrar tutoría' }}</x-button>
                    </div>
                </form>

                @if($editing)
                    <form method="POST" action="{{ route('tutorships.destroy', $tutorship) }}" class="flex justify-end">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('¿Eliminar esta tutoría?')" class="text-sm font-semibold text-[var(--color-danger)]">Eliminar tutoría</button>
                    </form>
                @endif
            </div>

            <div class="flex flex-col gap-4 lg:w-[320px]">
                <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[18px]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Historial reciente · RF-35</h2>
                    @forelse($history as $item)
                        <div class="flex flex-col gap-[2px] border-l-2 border-[var(--border-default)] pl-[10px]">
                            <span class="text-xs font-semibold text-[var(--text-primary)]">{{ $item['date'] }}</span>
                            <span class="text-xs text-[var(--text-muted)]">{{ $item['reason'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-[var(--text-muted)]">Sin tutorías registradas.</p>
                    @endforelse
                    <a href="{{ route('students.show', $student['id']) }}" class="text-sm font-semibold text-[var(--color-primary-600)]">Ver historial completo</a>
                </div>

                <div class="flex items-start gap-[10px] rounded-[var(--radius-lg)] bg-[var(--color-info-bg)] p-[14px]">
                    <x-lucide-info class="h-4 w-4 shrink-0 text-[var(--color-info)]" />
                    <p class="text-sm text-[var(--text-secondary)]">Podrá corregir esta sesión durante {{ $hours }} horas. Después queda inmutable para conservar la continuidad del acompañamiento.</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
```

`app/Support/StudentProfile.php` — en `build()` reemplazar el mapeo de `tutorials`:

```php
            'tutorials' => $student->tutorships()->latest('fecha')->limit(3)->get()->map(fn ($t) => [
                'date' => Labels::date($t->fecha),
                'reason' => $t->motivo,
                'note' => (string) $t->observaciones,
                'editUrl' => $viewer && $viewer->can('update', $t) ? route('tutorships.edit', $t) : null,
            ])->all(),
```

`app/Http/Controllers/StudentController.php::show` — añadir al array de la vista `'canRegisterTutorship' => $user->can('create', \App\Models\Tutorship::class),` (con `use App\Models\Tutorship;`).

`resources/views/students/show.blade.php`:
1. Reemplazar `<x-button variant="primary">Registrar tutoría</x-button>` por:
```blade
                @if($canRegisterTutorship)
                    <x-button variant="primary" :href="route('tutorships.create', $student['id'])">Registrar tutoría</x-button>
                @endif
```
2. En el bloque de cada tutoría (`@foreach($tutorials as $tutorial)`), después de `<span class="text-xs text-[var(--text-secondary)]">{{ $tutorial['note'] }}</span>` añadir:
```blade
                                @if($tutorial['editUrl'])
                                    <a href="{{ $tutorial['editUrl'] }}" class="text-xs font-semibold text-[var(--color-primary-600)]">Editar</a>
                                @endif
```

- [ ] **Step 4: Run tests to verify they pass, then the full suite**

Run: `php artisan test --filter='TutorshipPolicyTest|TutorshipTest' && php artisan test`
Expected: PASS; suite completa en verde.

- [ ] **Step 5: Commit**

```bash
git add app resources routes tests
git commit -m "feat(tutorships): agregar crud de tutorías con ventana de edición de 24 horas"
```

---

### Task 12: Mis asesorías (T1)

**Files:**
- Create: `app/Http/Controllers/AdvisoryController.php`, `resources/views/advisories/index.blade.php`, `tests/Feature/AdvisoryTest.php`
- Modify: `routes/web.php`, `tests/Feature/AuthTest.php`, `tests/Feature/EmptyDatabaseScreensTest.php`

**Interfaces:**
- Consumes: `AcademicTerm::current()`, `Labels`, `x-filter-select`, `x-pagination`, `tutorships.create`.
- Produces: `GET /mis-asesorias?q=&alertas=con|sin&page=` (`advisories.index`, solo tutor). Cada fila: `id, name, cohort, last, papa, papaLow, alert, tone, profileUrl, registerUrl`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/AdvisoryTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvisoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 12:00:00');
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Tutor);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_tutor_sees_only_the_students_assigned_to_them(): void
    {
        $response = $this->get('/mis-asesorias');

        $response->assertOk();
        $response->assertSee('Mis asesorías');
        $response->assertSee('5 estudiantes asignados en 2026-2S');
        foreach (['Pérez Rojas, Juan Sebastián', 'Gómez Ruiz, Laura', 'Cardona Vélez, Mateo', 'Martínez Cruz, Diana', 'Ospina Lara, Sofía'] as $name) {
            $response->assertSee($name);
        }
        foreach (['Ruiz Salazar, Andrés', 'Herrera Niño, Camilo', 'Valencia Toro, Ana'] as $name) {
            $response->assertDontSee($name);
        }
        $response->assertSee('Mostrando 1–5 de 5 estudiantes asignados');
    }

    public function test_each_row_shows_the_last_tutorship_and_an_alert_summary(): void
    {
        $response = $this->get('/mis-asesorias');

        $response->assertSee('12/09/2026');
        $response->assertSee('03/07/2026');
        $response->assertSee('1 alerta abierta');
        $response->assertSee('Sin tutoría reciente');
        $response->assertSee('Sin alertas');
        $response->assertSee('font-semibold text-[var(--color-danger)]', false);
    }

    public function test_a_deleted_tutorship_no_longer_counts_as_the_last_one(): void
    {
        Student::where('codigo', '2020123')->firstOrFail()->tutorships()->where('fecha', '2026-09-12')->firstOrFail()->delete();

        $this->get('/mis-asesorias')->assertDontSee('12/09/2026')->assertSee('03/05/2026');
    }

    public function test_filters_by_alerts_and_search(): void
    {
        $this->get('/mis-asesorias?alertas=con')->assertSee('Mostrando 1–2 de 2')->assertSee('Pérez Rojas, Juan Sebastián')->assertSee('Gómez Ruiz, Laura')->assertDontSee('Cardona Vélez, Mateo');
        $this->get('/mis-asesorias?alertas=sin')->assertSee('Mostrando 1–3 de 3')->assertSee('Cardona Vélez, Mateo')->assertDontSee('Gómez Ruiz, Laura');
        $this->get('/mis-asesorias?q=cardona')->assertSee('Cardona Vélez, Mateo')->assertDontSee('Gómez Ruiz, Laura');
        $this->get('/mis-asesorias?q=%25')->assertSee('No hay estudiantes');
        $this->get('/mis-asesorias?q[]=x&alertas[]=y')->assertOk();
    }

    public function test_rows_link_to_the_profile_and_to_the_register_form(): void
    {
        $juan = Student::where('codigo', '2020123')->firstOrFail();

        $response = $this->get('/mis-asesorias');

        $response->assertSee('href="'.route('students.show', $juan).'"', false);
        $response->assertSee('href="'.route('tutorships.create', $juan).'"', false);
    }

    public function test_a_tutor_without_assignments_sees_the_empty_state(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Tutor]));

        $this->get('/mis-asesorias')->assertOk()->assertSee('0 estudiantes asignados')->assertSee('No hay estudiantes que coincidan');
    }

    public function test_only_tutors_can_open_it(): void
    {
        foreach ([Role::Coordinacion, Role::Profesor, Role::Estudiante] as $role) {
            $this->signInSeeded($role);
            $this->get('/mis-asesorias')->assertForbidden();
        }
    }
}
```

Añadir a la matriz de `tests/Feature/AuthTest.php::test_role_matrix_…` la fila `['/mis-asesorias', [Role::Tutor]]`. Añadir a `tests/Feature/EmptyDatabaseScreensTest.php`:

```php
    public function test_advisories_render_with_an_empty_database(): void
    {
        $this->actingAsRole(Role::Tutor);

        $this->get('/mis-asesorias')->assertOk()->assertSee('0 estudiantes asignados en sin periodo');
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='AdvisoryTest|AuthTest|EmptyDatabaseScreensTest'`
Expected: FAIL (ruta inexistente).

- [ ] **Step 3: Implement**

`routes/web.php` — dentro de `auth`:

```php
    Route::get('/mis-asesorias', [AdvisoryController::class, 'index'])->middleware('role:tutor')->name('advisories.index');
```
(import `App\Http\Controllers\AdvisoryController`).

`app/Http/Controllers/AdvisoryController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\TutorAssignment;
use App\Support\Labels;
use App\Support\Navigation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdvisoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $term = AcademicTerm::current();
        $termId = $term?->id ?? 0;
        $filters = [
            'q' => $request->string('q')->trim()->toString(),
            'alertas' => $request->string('alertas')->toString(),
        ];

        $students = Student::query()
            ->select('students.*')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->whereExists(fn ($e) => $e->selectRaw('1')
                ->from('tutor_assignments')
                ->whereColumn('tutor_assignments.student_id', 'students.id')
                ->where('tutor_assignments.term_id', $termId)
                ->where('tutor_assignments.tutor_id', $user->id))
            ->with(['user', 'academicRecords' => fn ($q) => $q->where('term_id', $termId)])
            ->withCount(['alerts as open_alerts_count' => fn ($q) => $q->where('atendida', false)])
            ->withMax('tutorships as last_tutorship_at', 'fecha')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%'.addcslashes($filters['q'], '\\%_').'%';
                $query->where(fn ($w) => $w->where('users.name', 'ilike', $like)->orWhere('students.codigo', 'ilike', $like));
            })
            ->when($filters['alertas'] === 'con', fn ($q) => $q->whereHas('alerts', fn ($a) => $a->where('atendida', false)))
            ->when($filters['alertas'] === 'sin', fn ($q) => $q->whereDoesntHave('alerts', fn ($a) => $a->where('atendida', false)))
            ->orderBy('users.name')
            ->paginate(config('program.page_size'))
            ->withQueryString();

        return view('advisories.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'mis-asesorias'),
            'total' => TutorAssignment::where('tutor_id', $user->id)->where('term_id', $termId)->count(),
            'termLabel' => Labels::termLabel($term),
            'students' => $students->through(fn (Student $s) => $this->row($s)),
            'filters' => $filters,
            'alertOptions' => ['con' => 'Con alertas abiertas', 'sin' => 'Sin alertas abiertas'],
        ]);
    }

    private function row(Student $student): array
    {
        $record = $student->academicRecords->first();
        $papa = $record ? (float) $record->papa : null;
        $last = $student->last_tutorship_at ? Carbon::parse($student->last_tutorship_at) : null;
        $open = (int) $student->open_alerts_count;

        [$alert, $tone] = match (true) {
            $open > 0 => [$open.' '.($open === 1 ? 'alerta abierta' : 'alertas abiertas'), 'warning'],
            $last === null => ['Sin tutorías', 'warning'],
            $last->lt(now()->subDays(90)) => ['Sin tutoría reciente', 'warning'],
            default => ['Sin alertas', 'success'],
        };

        return [
            'name' => $student->user->name,
            'cohort' => $student->cohorte,
            'last' => Labels::date($last),
            'papa' => $papa === null ? '—' : number_format($papa, 1),
            'papaLow' => $papa !== null && $papa < config('program.papa_alert'),
            'alert' => $alert,
            'tone' => $tone,
            'profileUrl' => route('students.show', $student),
            'registerUrl' => route('tutorships.create', $student),
        ];
    }
}
```

`resources/views/advisories/index.blade.php`:

```blade
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mis asesorías · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Mis asesorías</h1>
            <p class="text-sm text-[var(--text-secondary)]">{{ $total }} estudiantes asignados en {{ $termLabel }} · RF-35</p>
        </div>

        <form method="GET" action="{{ route('advisories.index') }}" class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[14px] lg:flex-row lg:items-center">
            <div class="flex h-[var(--control-height)] flex-1 items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3">
                <x-lucide-search class="h-4 w-4 text-[var(--color-neutral-400)]" />
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por nombre o código" class="w-full bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
            </div>
            <x-filter-select name="alertas" all="Alertas: todas" :options="$alertOptions" :selected="$filters['alertas']" />
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </form>

        <div class="hidden min-w-0 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] sm:flex">
            <div class="min-w-0 overflow-x-auto">
                <div class="flex min-w-[760px] gap-3 px-4 py-[11px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                    <span class="flex-1">ESTUDIANTE</span>
                    <span class="w-[90px]">COHORTE</span>
                    <span class="w-[140px]">ÚLTIMA TUTORÍA</span>
                    <span class="w-16">PAPA</span>
                    <span class="w-[170px]">ALERTAS</span>
                    <span class="w-[150px]"></span>
                </div>
                @forelse($students as $row)
                    <div class="flex min-w-[760px] items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm">
                        <a href="{{ $row['profileUrl'] }}" class="flex-1 font-semibold text-[var(--text-primary)]">{{ $row['name'] }}</a>
                        <span class="w-[90px] text-[var(--text-secondary)]">{{ $row['cohort'] }}</span>
                        <span class="w-[140px] text-[var(--text-secondary)]">{{ $row['last'] }}</span>
                        <span class="w-16 {{ $row['papaLow'] ? 'font-semibold text-[var(--color-danger)]' : 'text-[var(--text-secondary)]' }}">{{ $row['papa'] }}</span>
                        <span class="w-[170px]"><x-status-badge :text="$row['alert']" :tone="$row['tone']" /></span>
                        <a href="{{ $row['registerUrl'] }}" class="w-[150px] text-sm font-semibold text-[var(--color-primary-600)]">Registrar tutoría</a>
                    </div>
                @empty
                    <div class="border-t border-[var(--border-subtle)] px-4 py-8 text-center text-sm text-[var(--text-muted)]">No hay estudiantes que coincidan con los filtros.</div>
                @endforelse
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:hidden">
            @forelse($students as $row)
                <div class="flex flex-col gap-2 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-3">
                    <div class="flex items-center gap-2">
                        <a href="{{ $row['profileUrl'] }}" class="flex-1 text-sm font-medium text-[var(--text-primary)]">{{ $row['name'] }}</a>
                        <x-status-badge :text="$row['alert']" :tone="$row['tone']" />
                    </div>
                    <p class="text-xs text-[var(--text-secondary)]">{{ $row['cohort'] }} · PAPA {{ $row['papa'] }} · última tutoría {{ $row['last'] }}</p>
                    <a href="{{ $row['registerUrl'] }}" class="text-sm font-semibold text-[var(--color-primary-600)]">Registrar tutoría</a>
                </div>
            @empty
                <p class="rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-4 text-center text-sm text-[var(--text-muted)]">No hay estudiantes que coincidan con los filtros.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$students" noun="estudiantes asignados" />
    </div>
</x-layouts.app>
```

- [ ] **Step 4: Run tests to verify they pass, then the full suite**

Run: `php artisan test --filter='AdvisoryTest|AuthTest|EmptyDatabaseScreensTest' && php artisan test`
Expected: PASS; suite completa en verde.

- [ ] **Step 5: Commit**

```bash
git add app resources routes tests
git commit -m "feat(tutorships): agregar listado de mis asesorías para el tutor"
```

---

### Task 13: Documentación, sincronización de Pencil y verificación de punta a punta

**Files:**
- Modify: `CLAUDE.md`, `README.md`; `mokup/trayectoria-estudiantil.pen` (solo con las herramientas MCP de Pencil; archivo ignorado por git)

**Interfaces:**
- Consumes: todo lo anterior.
- Produces: documentación al día y evidencia de que el flujo completo funciona con servidor y BD reales.

- [ ] **Step 1: Verificar el flujo completo con servidor real (antes de documentar)**

```bash
php artisan migrate:fresh --seed
(php artisan serve --port=8766 >/dev/null 2>&1 &) ; sleep 2
B=http://127.0.0.1:8766; J=$(mktemp); D=$(mktemp -d); printf '%%PDF-1.4 soporte de prueba' > "$D/soporte.pdf"
tok() { curl -s -b "$J" -c "$J" "$B$1" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
login() { rm -f "$J"; T=$(tok /login); curl -s -b "$J" -c "$J" -o /dev/null -w "login $1 -> %{http_code}\n" --data-urlencode "_token=$T" --data-urlencode "email=$1" --data-urlencode "password=password" "$B/login"; }
TIPO=$(psql -d trayectoria_estudiantil -Atc "select id from request_types where nombre='Homologación de créditos'")
JUAN=$(psql -d trayectoria_estudiantil -Atc "select id from students where codigo='2020123'")

login estudiante@unal.edu.co
T=$(tok /solicitudes/nueva)
LOC=$(curl -s -b "$J" -c "$J" -o /dev/null -w '%{redirect_url}' -F "_token=$T" -F "tipo=$TIPO" -F "descripcion=Solicito homologar los créditos cursados en la pasantía de intercambio del periodo anterior." -F "soportes[]=@$D/soporte.pdf;type=application/pdf" "$B/solicitudes")
echo "radicada -> $LOC"; REQ=${LOC##*/}
curl -s -b "$J" "$B/solicitudes/$REQ" | grep -c "soporte.pdf"
curl -s -b "$J" -o /dev/null -w "estudiante ve solicitud ajena (esperado 403) -> %{http_code}\n" "$B/solicitudes/$(psql -d trayectoria_estudiantil -Atc "select r.id from requests r join students s on s.id=r.student_id where s.codigo='2021045'")"

login coordinacion@unal.edu.co
T=$(tok /solicitudes/$REQ); curl -s -b "$J" -c "$J" -o /dev/null -w "revision -> %{http_code}\n" -d "_token=$T" "$B/solicitudes/$REQ/revision"
T=$(tok /solicitudes/$REQ); curl -s -b "$J" -c "$J" -o /dev/null -w "decision -> %{http_code}\n" -d "_token=$T" -d "decision=aprobada" -d "numero_acta=20-2026" -d "observaciones=Procede." "$B/solicitudes/$REQ/decision"
curl -s -b "$J" "$B/solicitudes/$REQ" | grep -o "Aprobada\|20-2026" | sort -u

login tutor@unal.edu.co
T=$(tok /estudiantes/$JUAN/tutorias/crear); curl -s -b "$J" -c "$J" -o /dev/null -w "tutoria -> %{http_code}\n" -d "_token=$T" -d "fecha=$(date +%F)" --data-urlencode "motivo=Revisión de avance" --data-urlencode "observaciones=Sesión de seguimiento del semestre." "$B/estudiantes/$JUAN/tutorias"
curl -s -b "$J" "$B/mis-asesorias" | grep -c "Pérez Rojas"
pkill -f "artisan serve --port=8766"; php artisan migrate:fresh --seed >/dev/null
```
Expected: logins 302; `radicada -> http://127.0.0.1:8766/solicitudes/<n>`; el detalle del estudiante contiene `soporte.pdf`; la solicitud ajena responde 403; `revision`/`decision` → 302 y el detalle muestra `Aprobada` y `20-2026`; `tutoria` → 302 y `Mis asesorías` lista a Pérez Rojas. Si algo difiere, es un defecto: corregir con test primero antes de seguir.

- [ ] **Step 2: Verificar que Tailwind compila las clases nuevas**

Run: `npm run build 2>&1 | tail -5`
Expected: build exitoso (sin errores). Las clases de color de las barras de trazabilidad (`bg-[var(--color-…)]`) están como literales completas en `RequestController`, así que se incluyen.

- [ ] **Step 3: Actualizar `CLAUDE.md` y `README.md`**

`CLAUDE.md`:
- En **Project/Current scope** reemplazar "Screens still show mock data hardcoded in controllers, except the user and the sidebar menu … wiring each screen to the database is the next sub-project" por: las 6 pantallas originales y las nuevas (Solicitudes, Detalle, Registrar tutoría, Mis asesorías) leen de PostgreSQL; solicitudes y tutorías tienen CRUD con validación en español, permisos por rol/propiedad (Policies) y trazabilidad; quedan fuera `/mi-*`, `/experiencias` y `/admin/*`.
- En **Architecture** añadir un apartado "Data flow": controladores delgados que arman los arrays que consumen las vistas; `App\Services\RequestWorkflow` (radicar/revisar/decidir con historial), `App\Support\{ProgramMetrics,StudentProfile,Labels,Navigation}`, `App\Policies\{CommitteeRequest,Tutorship}Policy`, `config/program.php` (umbrales); tabla de rutas por rol (la de la Task 8–12) y la regla de las 24 h.
- En **Commands/tests** añadir: todo test que toque controladores usa `RefreshDatabase` y `$this->seed(DatabaseSeeder::class)` con `signInSeeded(Role::X)`; los soportes se prueban con `Storage::fake()`; la guarda de `TestCase::setUpTraits` aborta si la BD no termina en `_test`.
- Añadir una nota **Pencil canvas**: `mokup/trayectoria-estudiantil.pen` está organizado en carriles por rol (Fundamentos, Acceso, Coordinación C1–C5, Tutor T1–T3, Estudiante E1, Móvil); las pantallas nuevas se insertan al final del carril de su rol a 1600 px de la anterior y el nombre del marco lleva el código de carril y la ruta.

`README.md`: actualizar la tabla de **Pantallas** con las rutas nuevas (`/solicitudes`, `/solicitudes/{id}`, `/solicitudes/nueva`, `/mis-asesorias`, `/estudiantes/{id}/tutorias/crear`) y el rol de cada una, y la nota de estado ("las pantallas leen de PostgreSQL; pendientes `/mi-*`, `/experiencias` y `/admin/*`").

- [ ] **Step 4: Sincronizar Pencil con lo construido**

Con las herramientas MCP de Pencil (`mcp__pencil__execute` sobre `mokup/trayectoria-estudiantil.pen`):
1. Marco C3 (`K2NGu3`): cambiar el texto del botón principal a "Nueva solicitud": `Update("T0GfcM/u8HtU", {content: "Nueva solicitud"})`.
2. Marco E1 (`KG0lV`): localizar el texto "Guardar borrador" con `Get("KG0lV", n => n.type === "text" && n.content === "Guardar borrador" && Print(n.id))` y eliminar su botón contenedor con `Delete`.
3. `TakeScreenshot` de ambos marcos para confirmar que no quedaron huecos.

Si algún id ya no existe, releer con el visitor por nombre/contenido en lugar de recrear nada.

- [ ] **Step 5: Regresión completa y commit**

Run: `php artisan test && php artisan migrate:fresh --seed && git status --short`
Expected: suite completa en verde; `git status` solo muestra `CLAUDE.md` y `README.md`.

```bash
git add CLAUDE.md README.md
git commit -m "docs(claude): documentar el flujo de datos, rutas por rol y convenciones del lienzo de pencil"
```

---

## Self-Review (hecha)

- **Cobertura del spec:**
  - Estudiantes: listado con búsqueda/filtros/paginación (T4), ficha real con 404 (T5).
  - Solicitudes: radicar con soportes (T9), listar con filtros (T8), detalle, traza y descarga (T8), revisión y decisión con acta (T10), advertencia de repetidas RF-29 (T8, T9).
  - Tutorías: registrar, editar y eliminar dentro de 24 h (T11), Mis asesorías (T12).
  - Panel e Indicadores desde BD con fecha de corte (T6).
  - Policies (T7, T11), `RequestWorkflow` y `ProgramMetrics` (T6, T7).
  - `x-form-field` con errores, `old()` y archivos (T2, T9).
  - Menú por rol (T1).
  - Panel del estudiante redirigido (T8).
  - Criterios de éxito 1–5: T9/T10 (flujo completo, verificado de punta a punta en T13), T8/T10 (403 de estudiante), T11 (24 h), T4–T6 (BD y 404), suite verde en cada tarea.
- **Desviaciones deliberadas del spec/mockup (a confirmar por el usuario):** (1) en C3 el botón principal es "Nueva solicitud" (la decisión se registra en el detalle; no hay decisión masiva); (2) en E1 se elimina "Guardar borrador" (no hay borradores en el alcance); (3) en Indicadores se retiran los desplegables estáticos Cohorte/Estado/"Aplicar" (no eran funcionales) y queda el periodo y la fecha de corte; (4) en el menú del Tutor "Registrar tutoría" y "Mis asesorías" apuntan a `/mis-asesorias` (registrar requiere elegir estudiante); (5) "Exportar" y "Cargar Excel" siguen sin función (fuera de alcance).
- **Consistencia de tipos:** `Labels::{studentStatus,requestStatus,stateLabel,period,date,termLabel,bytes}`, `StudentProfile::{header,build}`, `ProgramMetrics::{term,cutoff,panel,indicators}`, `RequestWorkflow::{file,startReview,decide,repeatedCount,existingTypeNames}`, nombres de rutas (`requests.*`, `tutorships.*`, `advisories.index`, `students.*`) y claves de arrays coinciden entre tareas y vistas.
- **Placeholders:** ninguno; los pocos ajustes que dependen de leer la salida real (p. ej. `followRedirects`) se resolvieron con `get()` explícito.
- **Riesgos conocidos:** (1) las aserciones de `ProgramMetricsTest` dependen de los datos del seeder y de `Carbon::setTestNow('2026-10-12')`; si se cambian los seeders hay que ajustar las cifras (documentadas junto a cada test); (2) `lockForUpdate` en `RequestWorkflow` solo se ejerce con concurrencia real: los tests cubren la lógica de estados, no la carrera; (3) `Storage::download` en pruebas depende del disco `local` fake: si cambia `FILESYSTEM_DISK`, ajustar `Storage::fake()`; (4) la prueba de humo de la Task 13 modifica la BD de desarrollo y la deja re-sembrada al final.
