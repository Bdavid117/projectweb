# Base de datos PostgreSQL y autenticación con roles — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reemplazar los datos mock y el login simulado por una base PostgreSQL real (25 tablas del PDF §5.3, modelos, seeders) y un login con 4 roles y permisos aplicados en el servidor.

**Architecture:** Migraciones por tabla en orden de dependencias, un modelo Eloquent por tabla, rol como enum PHP en `users.role`, middleware `role:` por ruta, sesión nativa de Laravel. Las pantallas siguen con datos mock salvo el usuario y el menú, que salen de la sesión.

**Tech Stack:** Laravel 10.50 (PHP 8.1), PostgreSQL (`pdo_pgsql` ya instalado), PHPUnit, Blade + Tailwind v4.

**Spec:** `docs/superpowers/specs/2026-10-12-base-de-datos-y-auth-design.md`

## Global Constraints

- PostgreSQL, base `trayectoria_estudiantil`; tests contra `trayectoria_estudiantil_test`.
- PHP 8.1 / Laravel 10: enums sí; `readonly` classes no.
- Tablas en inglés, columnas en español exactamente como PDF §5.3.
- Roles: `estudiante`, `profesor`, `tutor`, `coordinacion` en `users.role` (enum `App\Enums\Role`).
- Contraseñas con bcrypt (cast `hashed`), CSRF activo, error de login genérico "Credenciales inválidas." (RF-01).
- Historia no se borra en cascada (`restrictOnDelete`); `cascadeOnDelete` solo en `request_attachments` e `internship_follow_ups`. `SoftDeletes` en `tutorships`, `requests`, `interventions`.
- Sin `style=` inline; clases Tailwind literales con variables CSS del proyecto (CLAUDE.md).
- Commits: Conventional Commits con scope, descripción breve en español y minúsculas (`feat(db): …`).
- Los tests de pantalla existentes no deben necesitar BD (usan `actingAsRole`, que no persiste).

## Review Focus

1. Login con campos vacíos o ausentes → error genérico, nunca excepción (Task 9).
2. Correo con mayúsculas o espacios (`" Ana@UNAL.edu.co "`) debe autenticar igual (Task 9).
3. Re-ejecutar `db:seed` no duplica ni falla (Task 8).
4. Borrar un estudiante con historia académica debe fallar por FK; la historia no se pierde (Task 4).
5. Registros con soft delete (solicitudes, tutorías) no aparecen en consultas normales (Tasks 6 y 7).

---

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `app/Enums/Role.php` | Roles, etiqueta legible y ruta de inicio |
| `app/Models/*.php` (25) | Un modelo por tabla con relaciones de PDF §5.2 |
| `database/migrations/2026_10_12_0000NN_*.php` (24) + `2014_…_create_users_table.php` editada | Esquema |
| `database/seeders/{Catalog,Demo}Seeder.php` | Catálogos y datos demo |
| `app/Http/Middleware/EnsureRole.php` | 403 por rol |
| `app/Http/Controllers/LoginController.php` | login/logout reales |
| `app/Support/Navigation.php` | + `forRole()` |
| `tests/Concerns/CreatesStudents.php` | Helpers de fixtures para tests de BD |
| `tests/Feature/Database/*`, `tests/Feature/AuthTest.php`, `tests/Feature/ShellUserTest.php` | Tests nuevos |

---

### Task 1: Configurar PostgreSQL y crear las bases

**Files:**
- Modify: `.env` (no versionado), `.env.example:11-16`, `phpunit.xml:19-22`
- Test: `tests/Feature/Database/ConnectionTest.php`

**Interfaces:**
- Produces: conexión por defecto `pgsql`; tests corren contra `trayectoria_estudiantil_test`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Database/ConnectionTest.php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConnectionTest extends TestCase
{
    public function test_tests_run_against_the_dedicated_postgres_test_database(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('trayectoria_estudiantil_test', DB::connection()->getDatabaseName());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ConnectionTest`
Expected: FAIL (driver `mysql` / no conexión).

- [ ] **Step 3: Crear las bases y configurar**

```bash
psql -d postgres -c 'CREATE DATABASE trayectoria_estudiantil'
psql -d postgres -c 'CREATE DATABASE trayectoria_estudiantil_test'
```

En `.env` y `.env.example` reemplazar el bloque DB por:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=trayectoria_estudiantil
DB_USERNAME=whoamy
DB_PASSWORD=
```

(En `.env.example` usar `DB_USERNAME=postgres`.) En `phpunit.xml`, reemplazar las dos líneas comentadas de sqlite por:

```xml
        <env name="DB_CONNECTION" value="pgsql"/>
        <env name="DB_DATABASE" value="trayectoria_estudiantil_test"/>
```

Si la conexión TCP pide contraseña (`php artisan migrate:status` falla con "password authentication failed"), usar el socket: `DB_HOST=/tmp` en `.env`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ConnectionTest && php artisan migrate:status`
Expected: PASS; `migrate:status` conecta (aún sin migraciones corridas).

- [ ] **Step 5: Commit**

```bash
git add .env.example phpunit.xml tests/Feature/Database/ConnectionTest.php
git commit -m "chore(db): configurar postgres y base de datos de pruebas"
```

---

### Task 2: Enum `Role`, tabla `users`, modelo `User` y helper de tests

**Files:**
- Create: `app/Enums/Role.php`, `tests/Unit/RoleTest.php`, `tests/Feature/Database/UserTest.php`
- Modify: `database/migrations/2014_10_12_000000_create_users_table.php`, `app/Models/User.php`, `database/factories/UserFactory.php`, `tests/TestCase.php`

**Interfaces:**
- Produces:
  - `App\Enums\Role` cases `Estudiante|Profesor|Tutor|Coordinacion` (valores `estudiante|profesor|tutor|coordinacion`); `label(): string`; `homeRoute(): string` (`requests.create` para Estudiante, `panel.index` para el resto).
  - `User::$role` (cast a `Role`), `User::$active` (bool), `User::initials(): string`, `User::viewData(): array{initials:string,name:string,role:string}`.
  - `TestCase::actingAsRole(Role $role, array $attributes = []): User` (no persiste).

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Unit/RoleTest.php

namespace Tests\Unit;

use App\Enums\Role;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function test_labels_and_home_routes(): void
    {
        $this->assertSame('Coordinación', Role::Coordinacion->label());
        $this->assertSame('Estudiante', Role::Estudiante->label());
        $this->assertSame('requests.create', Role::Estudiante->homeRoute());
        $this->assertSame('panel.index', Role::Tutor->homeRoute());
        $this->assertSame('panel.index', Role::Profesor->homeRoute());
        $this->assertSame('panel.index', Role::Coordinacion->homeRoute());
    }
}
```

```php
<?php
// tests/Feature/Database/UserTest.php

namespace Tests\Feature\Database;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_is_persisted_and_cast_to_enum(): void
    {
        $user = User::factory()->create(['role' => Role::Tutor]);

        $this->assertSame(Role::Tutor, $user->fresh()->role);
        $this->assertTrue($user->fresh()->active);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'tutor']);
    }

    public function test_password_is_stored_as_a_hash(): void
    {
        $user = User::factory()->create(['password' => 'secreto123']);

        $this->assertNotSame('secreto123', $user->getRawOriginal('password'));
        $this->assertStringStartsWith('$2y$', $user->getRawOriginal('password'));
    }

    public function test_initials_and_view_data(): void
    {
        $this->assertSame('AR', User::factory()->make(['name' => 'Ana Restrepo'])->initials());
        $this->assertSame('JP', User::factory()->make(['name' => 'Pérez Rojas, Juan Sebastián'])->initials());

        $this->assertSame(
            ['initials' => 'AR', 'name' => 'Ana Restrepo', 'role' => 'Coordinación'],
            User::factory()->make(['name' => 'Ana Restrepo', 'role' => Role::Coordinacion])->viewData()
        );
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='RoleTest|Database.UserTest'`
Expected: FAIL (`Role` y columnas no existen).

- [ ] **Step 3: Implementar**

`app/Enums/Role.php`:

```php
<?php

namespace App\Enums;

enum Role: string
{
    case Estudiante = 'estudiante';
    case Profesor = 'profesor';
    case Tutor = 'tutor';
    case Coordinacion = 'coordinacion';

    public function label(): string
    {
        return match ($this) {
            self::Estudiante => 'Estudiante',
            self::Profesor => 'Profesor',
            self::Tutor => 'Tutor',
            self::Coordinacion => 'Coordinación',
        };
    }

    public function homeRoute(): string
    {
        return match ($this) {
            self::Estudiante => 'requests.create',
            default => 'panel.index',
        };
    }
}
```

Reemplazar el cuerpo de `up()` en `database/migrations/2014_10_12_000000_create_users_table.php`:

```php
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('estudiante');
            $table->boolean('active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
```

`app/Models/User.php` — reemplazar el contenido de la clase (mantener `use`s y añadir `use App\Enums\Role;`):

```php
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'active'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => Role::class,
        'active' => 'boolean',
    ];

    public function student(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function initials(): string
    {
        $name = $this->name;

        if (str_contains($name, ',')) {
            [$last, $first] = array_map('trim', explode(',', $name, 2));
            $name = "$first $last";
        }

        return collect(preg_split('/\s+/', trim($name)))
            ->take(2)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    /** @return array{initials: string, name: string, role: string} */
    public function viewData(): array
    {
        return [
            'initials' => $this->initials(),
            'name' => $this->name,
            'role' => $this->role->label(),
        ];
    }
```

`UserFactory::definition()` — añadir `'role' => Role::Estudiante,` y `'active' => true,` (con `use App\Enums\Role;`).

`tests/TestCase.php` — añadir imports `use App\Enums\Role; use App\Models\User;` y método:

```php
    protected function actingAsRole(Role $role, array $attributes = []): User
    {
        $user = User::factory()->make($attributes + ['id' => 1, 'role' => $role]);
        $this->actingAs($user);

        return $user;
    }
```

(`Student` aún no existe: la relación solo se resuelve al usarla; se crea en Task 4.)

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter='RoleTest|Database.UserTest'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Enums app/Models/User.php database tests
git commit -m "feat(db): agregar enum de roles, columnas de rol y estado en users y helper de tests"
```

---

### Task 3: Esquema y modelos de catálogos

**Files:**
- Create: migraciones `2026_10_12_000001_create_academic_terms_table.php`, `…000002_create_schools_table.php`, `…000003_create_companies_table.php`, `…000004_create_request_types_table.php`, `…000005_create_experience_types_table.php`, `…000006_create_committee_minutes_table.php`; modelos `AcademicTerm`, `School`, `Company`, `RequestType`, `ExperienceType`, `CommitteeMinute`
- Test: `tests/Feature/Database/CatalogSchemaTest.php`

**Interfaces:**
- Produces: modelos en `App\Models` con `$fillable` igual a las columnas listadas abajo. `AcademicTerm` (`codigo`, `fecha_inicio`, `fecha_fin`), `School` (`nombre`, `caracter`, `municipio`), `Company` (`nombre`, `nit`, `sector`, `tamano`, `ciudad`), `RequestType` (`nombre`, `descripcion`), `ExperienceType` (`nombre`, `categoria`), `CommitteeMinute` (`numero_acta`, `fecha`, `instancia`, `url`).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Database/CatalogSchemaTest.php

namespace Tests\Feature\Database;

use App\Models\AcademicTerm;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_tables_have_the_columns_from_the_analysis_document(): void
    {
        $expected = [
            'academic_terms' => ['codigo', 'fecha_inicio', 'fecha_fin'],
            'schools' => ['nombre', 'caracter', 'municipio'],
            'companies' => ['nombre', 'nit', 'sector', 'tamano', 'ciudad'],
            'request_types' => ['nombre', 'descripcion'],
            'experience_types' => ['nombre', 'categoria'],
            'committee_minutes' => ['numero_acta', 'fecha', 'instancia', 'url'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasColumns($table, $columns), "Faltan columnas en $table");
        }
    }

    public function test_term_codigo_is_unique(): void
    {
        $attrs = ['codigo' => '2026-2', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-12'];
        AcademicTerm::create($attrs);

        $this->expectException(QueryException::class);
        AcademicTerm::create($attrs);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CatalogSchemaTest`
Expected: FAIL (tablas no existen).

- [ ] **Step 3: Crear migraciones y modelos**

Cada migración usa esta estructura (`down()` = `Schema::dropIfExists('<tabla>')`):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};
```

Cuerpos de `Schema::create` para el resto:

```php
// 000002 schools
$table->id();
$table->string('nombre');
$table->string('caracter'); // publico | privado
$table->string('municipio')->nullable();
$table->timestamps();

// 000003 companies
$table->id();
$table->string('nombre');
$table->string('nit')->nullable()->unique();
$table->string('sector')->nullable();
$table->string('tamano')->nullable();
$table->string('ciudad')->nullable();
$table->timestamps();

// 000004 request_types
$table->id();
$table->string('nombre')->unique();
$table->text('descripcion')->nullable();
$table->timestamps();

// 000005 experience_types
$table->id();
$table->string('nombre')->unique();
$table->string('categoria');
$table->timestamps();

// 000006 committee_minutes
$table->id();
$table->string('numero_acta')->unique();
$table->date('fecha');
$table->string('instancia');
$table->string('url')->nullable();
$table->timestamps();
```

Modelos (mismo patrón; `AcademicTerm` y `CommitteeMinute` castean fechas):

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicTerm extends Model
{
    protected $fillable = ['codigo', 'fecha_inicio', 'fecha_fin'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];
}
```

```php
class School extends Model
{
    protected $fillable = ['nombre', 'caracter', 'municipio'];
}

class Company extends Model
{
    protected $fillable = ['nombre', 'nit', 'sector', 'tamano', 'ciudad'];
}

class RequestType extends Model
{
    protected $fillable = ['nombre', 'descripcion'];
}

class ExperienceType extends Model
{
    protected $fillable = ['nombre', 'categoria'];
}

class CommitteeMinute extends Model
{
    protected $fillable = ['numero_acta', 'fecha', 'instancia', 'url'];

    protected $casts = ['fecha' => 'date'];
}
```

Cada clase en su archivo `app/Models/<Clase>.php` con `namespace App\Models; use Illuminate\Database\Eloquent\Model;`. Las relaciones inversas (`hasMany`) se agregan en las tareas donde nace la tabla hija.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=CatalogSchemaTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations app/Models tests/Feature/Database/CatalogSchemaTest.php
git commit -m "feat(db): agregar tablas y modelos de catálogos"
```

---

### Task 4: Núcleo del estudiante e historia académica

**Files:**
- Create: migraciones `…000007_create_students_table.php`, `…000008_create_admission_profiles_table.php`, `…000009_create_health_profiles_table.php`, `…000010_create_saber_pro_results_table.php`, `…000011_create_academic_records_table.php`, `…000012_create_tutor_assignments_table.php`; modelos `Student`, `AdmissionProfile`, `HealthProfile`, `SaberProResult`, `AcademicRecord`, `TutorAssignment`; `tests/Concerns/CreatesStudents.php`
- Modify: `AcademicTerm`, `School` (relaciones inversas)
- Test: `tests/Feature/Database/StudentSchemaTest.php`

**Interfaces:**
- Consumes: `User`, `Role`, `AcademicTerm`, `School` (Tasks 2–3).
- Produces:
  - `Student` (`user_id`, `codigo`, `documento`, `cohorte`, `estado`, `fecha_ingreso`) con `user()`, `admissionProfile()`, `healthProfile()`, `saberProResults()`, `academicRecords()`, `tutorAssignments()`.
  - `AcademicRecord` (`student_id`, `term_id`, `papa`, `promedio_periodo`, `cupo_creditos`, `creditos_cursados`, `porcentaje_avance`, `numero_matricula`, `sincronizado_en`) con `student()`, `term()`.
  - `TutorAssignment` (`student_id`, `tutor_id`, `term_id`) con `student()`, `tutor()`, `term()`.
  - `Tests\Concerns\CreatesStudents::makeStudent(string $codigo = '2020123'): Student` y `makeTerm(string $codigo = '2026-2'): AcademicTerm`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Concerns/CreatesStudents.php

namespace Tests\Concerns;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\User;

trait CreatesStudents
{
    protected function makeStudent(string $codigo = '2020123'): Student
    {
        $user = User::factory()->create(['role' => Role::Estudiante]);

        return Student::create([
            'user_id' => $user->id,
            'codigo' => $codigo,
            'documento' => '10'.$codigo,
            'cohorte' => '2020-1',
            'estado' => 'activo',
            'fecha_ingreso' => '2020-02-03',
        ]);
    }

    protected function makeTerm(string $codigo = '2026-2'): AcademicTerm
    {
        return AcademicTerm::firstOrCreate(
            ['codigo' => $codigo],
            ['fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-12']
        );
    }
}
```

```php
<?php
// tests/Feature/Database/StudentSchemaTest.php

namespace Tests\Feature\Database;

use App\Models\AcademicRecord;
use App\Models\TutorAssignment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class StudentSchemaTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private function record(int $studentId, int $termId, string $papa = '4.10'): AcademicRecord
    {
        return AcademicRecord::create([
            'student_id' => $studentId, 'term_id' => $termId, 'papa' => $papa,
            'promedio_periodo' => '4.00', 'cupo_creditos' => 21, 'creditos_cursados' => 116,
            'porcentaje_avance' => '72.00', 'numero_matricula' => 7,
        ]);
    }

    public function test_tables_have_the_columns_from_the_analysis_document(): void
    {
        $expected = [
            'students' => ['user_id', 'codigo', 'documento', 'cohorte', 'estado', 'fecha_ingreso'],
            'admission_profiles' => ['student_id', 'school_id', 'puntaje_saber11', 'puntaje_admision', 'hobby_formal'],
            'health_profiles' => ['student_id', 'resultado_oso', 'resultado_salud_mental', 'resultado_salud_fisica', 'observaciones'],
            'saber_pro_results' => ['student_id', 'fecha_presentacion', 'puntaje_global', 'resultados_json'],
            'academic_records' => ['student_id', 'term_id', 'papa', 'promedio_periodo', 'cupo_creditos', 'creditos_cursados', 'porcentaje_avance', 'numero_matricula', 'sincronizado_en'],
            'tutor_assignments' => ['student_id', 'tutor_id', 'term_id'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasColumns($table, $columns), "Faltan columnas en $table");
        }
    }

    public function test_one_academic_record_per_student_and_term(): void
    {
        $student = $this->makeStudent();
        $term = $this->makeTerm();
        $this->record($student->id, $term->id);

        $this->expectException(QueryException::class);
        $this->record($student->id, $term->id, '3.00');
    }

    public function test_a_new_term_adds_a_record_without_overwriting_history(): void
    {
        $student = $this->makeStudent();
        $this->record($student->id, $this->makeTerm('2026-1')->id, '3.90');
        $this->record($student->id, $this->makeTerm('2026-2')->id, '4.10');

        $this->assertSame(2, $student->academicRecords()->count());
        $this->assertSame('3.90', $student->academicRecords()->whereHas('term', fn ($q) => $q->where('codigo', '2026-1'))->first()->papa);
    }

    public function test_one_tutor_per_student_per_term(): void
    {
        $student = $this->makeStudent();
        $term = $this->makeTerm();
        $tutor = User::factory()->create();
        TutorAssignment::create(['student_id' => $student->id, 'tutor_id' => $tutor->id, 'term_id' => $term->id]);

        $this->expectException(QueryException::class);
        TutorAssignment::create(['student_id' => $student->id, 'tutor_id' => User::factory()->create()->id, 'term_id' => $term->id]);
    }

    public function test_student_with_academic_history_cannot_be_deleted(): void
    {
        $student = $this->makeStudent();
        $this->record($student->id, $this->makeTerm()->id);

        try {
            $student->delete();
            $this->fail('La historia académica no debe borrarse en cascada.');
        } catch (QueryException) {
            $this->assertDatabaseCount('academic_records', 1);
        }
    }

    public function test_documento_and_codigo_are_unique(): void
    {
        $this->makeStudent('2020123');

        $this->expectException(QueryException::class);
        $this->makeStudent('2020123');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=StudentSchemaTest`
Expected: FAIL (tablas y modelos no existen).

- [ ] **Step 3: Crear migraciones y modelos**

Cuerpos de `Schema::create` (misma estructura de archivo que Task 3):

```php
// 000007 students
$table->id();
$table->foreignId('user_id')->nullable()->unique()->constrained()->restrictOnDelete();
$table->string('codigo')->unique();
$table->string('documento')->unique();
$table->string('cohorte');
$table->string('estado')->default('activo'); // activo | en_riesgo | intercambio | egresado
$table->date('fecha_ingreso')->nullable();
$table->timestamps();

// 000008 admission_profiles
$table->id();
$table->foreignId('student_id')->unique()->constrained()->restrictOnDelete();
$table->foreignId('school_id')->nullable()->constrained()->restrictOnDelete();
$table->decimal('puntaje_saber11', 6, 2)->nullable();
$table->decimal('puntaje_admision', 6, 2)->nullable();
$table->string('hobby_formal')->nullable();
$table->timestamps();

// 000009 health_profiles
$table->id();
$table->foreignId('student_id')->unique()->constrained()->restrictOnDelete();
$table->text('resultado_oso')->nullable();
$table->text('resultado_salud_mental')->nullable();
$table->text('resultado_salud_fisica')->nullable();
$table->text('observaciones')->nullable();
$table->timestamps();

// 000010 saber_pro_results
$table->id();
$table->foreignId('student_id')->constrained()->restrictOnDelete();
$table->date('fecha_presentacion');
$table->decimal('puntaje_global', 6, 2)->nullable();
$table->jsonb('resultados_json')->nullable();
$table->timestamps();

// 000011 academic_records
$table->id();
$table->foreignId('student_id')->constrained()->restrictOnDelete();
$table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
$table->decimal('papa', 3, 2);
$table->decimal('promedio_periodo', 3, 2)->nullable();
$table->unsignedSmallInteger('cupo_creditos')->nullable();
$table->unsignedSmallInteger('creditos_cursados')->default(0);
$table->decimal('porcentaje_avance', 5, 2)->default(0);
$table->unsignedSmallInteger('numero_matricula')->nullable();
$table->timestamp('sincronizado_en')->nullable();
$table->timestamps();
$table->unique(['student_id', 'term_id']);

// 000012 tutor_assignments
$table->id();
$table->foreignId('student_id')->constrained()->restrictOnDelete();
$table->foreignId('tutor_id')->constrained('users')->restrictOnDelete();
$table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
$table->timestamps();
$table->unique(['student_id', 'term_id']);
```

Modelos (namespace `App\Models`; `use Illuminate\Database\Eloquent\Model;` y los tipos de relación):

```php
class Student extends Model
{
    protected $fillable = ['user_id', 'codigo', 'documento', 'cohorte', 'estado', 'fecha_ingreso'];

    protected $casts = ['fecha_ingreso' => 'date'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function admissionProfile(): HasOne { return $this->hasOne(AdmissionProfile::class); }
    public function healthProfile(): HasOne { return $this->hasOne(HealthProfile::class); }
    public function saberProResults(): HasMany { return $this->hasMany(SaberProResult::class); }
    public function academicRecords(): HasMany { return $this->hasMany(AcademicRecord::class); }
    public function tutorAssignments(): HasMany { return $this->hasMany(TutorAssignment::class); }
}

class AdmissionProfile extends Model
{
    protected $fillable = ['student_id', 'school_id', 'puntaje_saber11', 'puntaje_admision', 'hobby_formal'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
}

class HealthProfile extends Model
{
    protected $fillable = ['student_id', 'resultado_oso', 'resultado_salud_mental', 'resultado_salud_fisica', 'observaciones'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}

class SaberProResult extends Model
{
    protected $fillable = ['student_id', 'fecha_presentacion', 'puntaje_global', 'resultados_json'];

    protected $casts = ['fecha_presentacion' => 'date', 'resultados_json' => 'array'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}

class AcademicRecord extends Model
{
    protected $fillable = ['student_id', 'term_id', 'papa', 'promedio_periodo', 'cupo_creditos', 'creditos_cursados', 'porcentaje_avance', 'numero_matricula', 'sincronizado_en'];

    protected $casts = ['sincronizado_en' => 'datetime'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function term(): BelongsTo { return $this->belongsTo(AcademicTerm::class, 'term_id'); }
}

class TutorAssignment extends Model
{
    protected $fillable = ['student_id', 'tutor_id', 'term_id'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function tutor(): BelongsTo { return $this->belongsTo(User::class, 'tutor_id'); }
    public function term(): BelongsTo { return $this->belongsTo(AcademicTerm::class, 'term_id'); }
}
```

Añadir en `AcademicTerm`: `public function academicRecords(): HasMany { return $this->hasMany(AcademicRecord::class, 'term_id'); }` y en `School`: `public function admissionProfiles(): HasMany { return $this->hasMany(AdmissionProfile::class); }`. Importar `BelongsTo`, `HasMany`, `HasOne` de `Illuminate\Database\Eloquent\Relations`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter='StudentSchemaTest|CatalogSchemaTest'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations app/Models tests
git commit -m "feat(db): agregar estudiantes, perfiles, historia académica y asignación de tutores"
```

---

### Task 5: Trabajo de grado y práctica empresarial

**Files:**
- Create: migraciones `…000013_create_theses_table.php`, `…000014_create_internships_table.php`, `…000015_create_internship_follow_ups_table.php`; modelos `Thesis`, `Internship`, `InternshipFollowUp`
- Modify: `app/Models/Student.php` (relaciones), `app/Models/Company.php` (relación)
- Test: `tests/Feature/Database/ThesisInternshipSchemaTest.php`

**Interfaces:**
- Consumes: `Student`, `User`, `Company`, `AcademicTerm`, `CreatesStudents`.
- Produces: `Thesis` (`student_id`, `modalidad`, `estado`, `titulo`, `director_id`, `codirector_id`, `term_id`, `repositorio_url`; `$table` por defecto `theses`), `Internship` (`student_id`, `company_id`, `jefe_nombre`, `jefe_cargo`, `asesor_id`, `fecha_inicio`, `fecha_fin`, `actividades`, `apreciacion_jefe`, `apreciacion_asesor`) con `followUps()`, `InternshipFollowUp` (`internship_id`, `fecha`, `observacion`, `autor_id`); `Student::theses()`, `Student::internships()`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Database/ThesisInternshipSchemaTest.php

namespace Tests\Feature\Database;

use App\Models\Company;
use App\Models\Internship;
use App\Models\InternshipFollowUp;
use App\Models\Thesis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class ThesisInternshipSchemaTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    public function test_tables_have_the_columns_from_the_analysis_document(): void
    {
        $expected = [
            'theses' => ['student_id', 'modalidad', 'estado', 'titulo', 'director_id', 'codirector_id', 'term_id', 'repositorio_url'],
            'internships' => ['student_id', 'company_id', 'jefe_nombre', 'jefe_cargo', 'asesor_id', 'fecha_inicio', 'fecha_fin', 'actividades', 'apreciacion_jefe', 'apreciacion_asesor'],
            'internship_follow_ups' => ['internship_id', 'fecha', 'observacion', 'autor_id'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasColumns($table, $columns), "Faltan columnas en $table");
        }
    }

    public function test_student_can_have_a_thesis_and_an_internship_with_accumulated_follow_ups(): void
    {
        $student = $this->makeStudent();
        $advisor = User::factory()->create();

        Thesis::create([
            'student_id' => $student->id, 'modalidad' => 'pasantia', 'estado' => 'en_desarrollo',
            'director_id' => $advisor->id, 'term_id' => $this->makeTerm()->id,
        ]);
        $internship = Internship::create([
            'student_id' => $student->id,
            'company_id' => Company::create(['nombre' => 'Softlogic S.A.S.'])->id,
            'asesor_id' => $advisor->id, 'fecha_inicio' => '2026-07-01', 'fecha_fin' => '2026-12-15',
        ]);
        foreach (['2026-08-01', '2026-09-01'] as $fecha) {
            InternshipFollowUp::create(['internship_id' => $internship->id, 'fecha' => $fecha, 'observacion' => 'Avance normal', 'autor_id' => $advisor->id]);
        }

        $this->assertSame(1, $student->theses()->count());
        $this->assertSame(1, $student->internships()->count());
        $this->assertSame(2, $internship->followUps()->count());
        $this->assertSame('Softlogic S.A.S.', $internship->company->nombre);
    }

    public function test_follow_ups_are_removed_with_their_internship(): void
    {
        $student = $this->makeStudent();
        $advisor = User::factory()->create();
        $internship = Internship::create([
            'student_id' => $student->id,
            'company_id' => Company::create(['nombre' => 'ACME'])->id,
            'asesor_id' => $advisor->id, 'fecha_inicio' => '2026-07-01',
        ]);
        InternshipFollowUp::create(['internship_id' => $internship->id, 'fecha' => '2026-08-01', 'observacion' => 'x', 'autor_id' => $advisor->id]);

        $internship->delete();

        $this->assertDatabaseCount('internship_follow_ups', 0);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ThesisInternshipSchemaTest`
Expected: FAIL.

- [ ] **Step 3: Crear migraciones y modelos**

```php
// 000013 theses
$table->id();
$table->foreignId('student_id')->constrained()->restrictOnDelete();
$table->string('modalidad');
$table->string('estado');
$table->string('titulo')->nullable();
$table->foreignId('director_id')->nullable()->constrained('users')->restrictOnDelete();
$table->foreignId('codirector_id')->nullable()->constrained('users')->restrictOnDelete();
$table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
$table->string('repositorio_url')->nullable();
$table->timestamps();

// 000014 internships
$table->id();
$table->foreignId('student_id')->constrained()->restrictOnDelete();
$table->foreignId('company_id')->constrained()->restrictOnDelete();
$table->string('jefe_nombre')->nullable();
$table->string('jefe_cargo')->nullable();
$table->foreignId('asesor_id')->nullable()->constrained('users')->restrictOnDelete();
$table->date('fecha_inicio');
$table->date('fecha_fin')->nullable();
$table->text('actividades')->nullable();
$table->text('apreciacion_jefe')->nullable();
$table->text('apreciacion_asesor')->nullable();
$table->timestamps();

// 000015 internship_follow_ups
$table->id();
$table->foreignId('internship_id')->constrained()->cascadeOnDelete();
$table->date('fecha');
$table->text('observacion');
$table->foreignId('autor_id')->constrained('users')->restrictOnDelete();
$table->timestamps();
```

Modelos:

```php
class Thesis extends Model
{
    protected $fillable = ['student_id', 'modalidad', 'estado', 'titulo', 'director_id', 'codirector_id', 'term_id', 'repositorio_url'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function director(): BelongsTo { return $this->belongsTo(User::class, 'director_id'); }
    public function codirector(): BelongsTo { return $this->belongsTo(User::class, 'codirector_id'); }
    public function term(): BelongsTo { return $this->belongsTo(AcademicTerm::class, 'term_id'); }
}

class Internship extends Model
{
    protected $fillable = ['student_id', 'company_id', 'jefe_nombre', 'jefe_cargo', 'asesor_id', 'fecha_inicio', 'fecha_fin', 'actividades', 'apreciacion_jefe', 'apreciacion_asesor'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function asesor(): BelongsTo { return $this->belongsTo(User::class, 'asesor_id'); }
    public function followUps(): HasMany { return $this->hasMany(InternshipFollowUp::class); }
}

class InternshipFollowUp extends Model
{
    protected $fillable = ['internship_id', 'fecha', 'observacion', 'autor_id'];

    protected $casts = ['fecha' => 'date'];

    public function internship(): BelongsTo { return $this->belongsTo(Internship::class); }
    public function autor(): BelongsTo { return $this->belongsTo(User::class, 'autor_id'); }
}
```

Añadir en `Student`: `theses(): HasMany` (`Thesis::class`) e `internships(): HasMany` (`Internship::class`); en `Company`: `internships(): HasMany`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ThesisInternshipSchemaTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations app/Models tests
git commit -m "feat(db): agregar trabajo de grado, práctica empresarial y seguimientos"
```

---

### Task 6: Solicitudes al comité asesor

**Files:**
- Create: migraciones `…000016_create_requests_table.php`, `…000017_create_request_attachments_table.php`, `…000018_create_request_status_history_table.php`; modelos `CommitteeRequest`, `RequestAttachment`, `RequestStatusHistory`
- Modify: `Student`, `RequestType`, `CommitteeMinute` (relaciones)
- Test: `tests/Feature/Database/RequestSchemaTest.php`

**Interfaces:**
- Consumes: `Student`, `RequestType`, `CommitteeMinute`, `AcademicTerm`, `User`, `CreatesStudents`.
- Produces: `CommitteeRequest` (tabla `requests`, `SoftDeletes`; `student_id`, `request_type_id`, `term_id`, `descripcion`, `estado`, `fecha_radicacion`, `minute_id`, `decision`, `observaciones`) con `student()`, `type()`, `term()`, `minute()`, `attachments()`, `statusHistory()`; `RequestAttachment` (`request_id`, `ruta`, `nombre_original`, `mime`, `tamano`); `RequestStatusHistory` (tabla `request_status_history`; `request_id`, `estado_anterior`, `estado_nuevo`, `user_id`, `fecha`); `Student::requests()`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Database/RequestSchemaTest.php

namespace Tests\Feature\Database;

use App\Models\CommitteeMinute;
use App\Models\CommitteeRequest;
use App\Models\RequestAttachment;
use App\Models\RequestStatusHistory;
use App\Models\RequestType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class RequestSchemaTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private function makeRequest(): CommitteeRequest
    {
        return CommitteeRequest::create([
            'student_id' => $this->makeStudent()->id,
            'request_type_id' => RequestType::create(['nombre' => 'Cancelación de asignatura'])->id,
            'term_id' => $this->makeTerm()->id,
            'descripcion' => 'Solicito cancelar la asignatura.',
            'estado' => 'radicada',
            'fecha_radicacion' => '2026-09-10',
        ]);
    }

    public function test_tables_have_the_columns_from_the_analysis_document(): void
    {
        $expected = [
            'requests' => ['student_id', 'request_type_id', 'term_id', 'descripcion', 'estado', 'fecha_radicacion', 'minute_id', 'decision', 'observaciones', 'deleted_at'],
            'request_attachments' => ['request_id', 'ruta', 'nombre_original', 'mime', 'tamano'],
            'request_status_history' => ['request_id', 'estado_anterior', 'estado_nuevo', 'user_id', 'fecha'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasColumns($table, $columns), "Faltan columnas en $table");
        }
    }

    public function test_request_keeps_status_trail_and_can_be_resolved_by_a_shared_minute(): void
    {
        $request = $this->makeRequest();
        $user = User::factory()->create();
        RequestStatusHistory::create(['request_id' => $request->id, 'estado_anterior' => null, 'estado_nuevo' => 'radicada', 'user_id' => $user->id, 'fecha' => now()]);
        RequestStatusHistory::create(['request_id' => $request->id, 'estado_anterior' => 'radicada', 'estado_nuevo' => 'en_revision', 'user_id' => $user->id, 'fecha' => now()]);

        $minute = CommitteeMinute::create(['numero_acta' => '12-2026', 'fecha' => '2026-09-20', 'instancia' => 'Comité asesor']);
        $request->update(['minute_id' => $minute->id, 'decision' => 'aprobada']);

        $this->assertSame(2, $request->statusHistory()->count());
        $this->assertSame('12-2026', $request->fresh()->minute->numero_acta);
        $this->assertSame(1, $minute->requests()->count());
    }

    public function test_soft_deleted_requests_are_hidden_but_kept(): void
    {
        $request = $this->makeRequest();

        $request->delete();

        $this->assertSame(0, CommitteeRequest::count());
        $this->assertSame(1, CommitteeRequest::withTrashed()->count());
    }

    public function test_attachments_are_removed_with_their_request_row(): void
    {
        $request = $this->makeRequest();
        RequestAttachment::create(['request_id' => $request->id, 'ruta' => 'soportes/a.pdf', 'nombre_original' => 'a.pdf', 'mime' => 'application/pdf', 'tamano' => 1024]);

        $request->forceDelete();

        $this->assertDatabaseCount('request_attachments', 0);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RequestSchemaTest`
Expected: FAIL.

- [ ] **Step 3: Crear migraciones y modelos**

```php
// 000016 requests
$table->id();
$table->foreignId('student_id')->constrained()->restrictOnDelete();
$table->foreignId('request_type_id')->constrained()->restrictOnDelete();
$table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
$table->text('descripcion');
$table->string('estado')->default('radicada'); // radicada | en_revision | con_decision
$table->date('fecha_radicacion');
$table->foreignId('minute_id')->nullable()->constrained('committee_minutes')->restrictOnDelete();
$table->string('decision')->nullable();
$table->text('observaciones')->nullable();
$table->timestamps();
$table->softDeletes();

// 000017 request_attachments
$table->id();
$table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
$table->string('ruta');
$table->string('nombre_original');
$table->string('mime');
$table->unsignedInteger('tamano');
$table->timestamps();

// 000018 request_status_history
$table->id();
$table->foreignId('request_id')->constrained('requests')->restrictOnDelete();
$table->string('estado_anterior')->nullable();
$table->string('estado_nuevo');
$table->foreignId('user_id')->constrained()->restrictOnDelete();
$table->timestamp('fecha');
$table->timestamps();
```

Modelos:

```php
class CommitteeRequest extends Model
{
    use SoftDeletes;

    protected $table = 'requests';

    protected $fillable = ['student_id', 'request_type_id', 'term_id', 'descripcion', 'estado', 'fecha_radicacion', 'minute_id', 'decision', 'observaciones'];

    protected $casts = ['fecha_radicacion' => 'date'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function type(): BelongsTo { return $this->belongsTo(RequestType::class, 'request_type_id'); }
    public function term(): BelongsTo { return $this->belongsTo(AcademicTerm::class, 'term_id'); }
    public function minute(): BelongsTo { return $this->belongsTo(CommitteeMinute::class, 'minute_id'); }
    public function attachments(): HasMany { return $this->hasMany(RequestAttachment::class, 'request_id'); }
    public function statusHistory(): HasMany { return $this->hasMany(RequestStatusHistory::class, 'request_id'); }
}

class RequestAttachment extends Model
{
    protected $fillable = ['request_id', 'ruta', 'nombre_original', 'mime', 'tamano'];

    public function request(): BelongsTo { return $this->belongsTo(CommitteeRequest::class, 'request_id'); }
}

class RequestStatusHistory extends Model
{
    protected $table = 'request_status_history';

    protected $fillable = ['request_id', 'estado_anterior', 'estado_nuevo', 'user_id', 'fecha'];

    protected $casts = ['fecha' => 'datetime'];

    public function request(): BelongsTo { return $this->belongsTo(CommitteeRequest::class, 'request_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
```

Añadir `requests(): HasMany` (`CommitteeRequest::class`) en `Student`; `requests(): HasMany` con FK `'request_type_id'` en `RequestType`; `requests(): HasMany` con FK `'minute_id'` en `CommitteeMinute`. `use Illuminate\Database\Eloquent\SoftDeletes;` en `CommitteeRequest`.

Nota: `forceDelete()` en el test de adjuntos funciona porque `request_status_history` no tiene filas en ese test.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=RequestSchemaTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations app/Models tests
git commit -m "feat(db): agregar solicitudes, soportes e historial de estados"
```

---

### Task 7: Experiencias, tutorías, intervenciones, alertas y trazabilidad

**Files:**
- Create: migraciones `…000019_create_experiences_table.php`, `…000020_create_tutorships_table.php`, `…000021_create_interventions_table.php`, `…000022_create_alerts_table.php`, `…000023_create_import_batches_table.php`, `…000024_create_audit_logs_table.php`; modelos `Experience`, `Tutorship`, `Intervention`, `Alert`, `ImportBatch`, `AuditLog`
- Modify: `Student` (relaciones)
- Test: `tests/Feature/Database/SupportSchemaTest.php`

**Interfaces:**
- Consumes: `Student`, `ExperienceType`, `User`, `CreatesStudents`.
- Produces: `Experience` (`student_id`, `experience_type_id`, `nombre`, `descripcion`, `fecha_inicio`, `fecha_fin`, `alcance`, `institucion`, `convenio`, `docente_id`, `rol`, `validada` bool default false, `validada_por`), `Tutorship` (SoftDeletes; `student_id`, `tutor_id`, `fecha`, `motivo`, `observaciones`, `compromisos`), `Intervention` (SoftDeletes; `student_id`, `origen`, `descripcion`, `responsable_id`, `fecha`, `resultado`, `estado`), `Alert` (`student_id`, `tipo`, `detalle`, `fecha_deteccion`, `atendida`), `ImportBatch` (`tipo`, `archivo`, `user_id`, `filas_ok`, `filas_error`, `log`; solo `created_at`), `AuditLog` (`user_id`, `auditable_type`, `auditable_id`, `accion`, `valores_anteriores`, `valores_nuevos`, `ip`; solo `created_at`; `auditable(): MorphTo`); `Student::experiences()`, `tutorships()`, `interventions()`, `alerts()`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Database/SupportSchemaTest.php

namespace Tests\Feature\Database;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Models\ImportBatch;
use App\Models\Intervention;
use App\Models\Tutorship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class SupportSchemaTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    public function test_tables_have_the_columns_from_the_analysis_document(): void
    {
        $expected = [
            'experiences' => ['student_id', 'experience_type_id', 'nombre', 'descripcion', 'fecha_inicio', 'fecha_fin', 'alcance', 'institucion', 'convenio', 'docente_id', 'rol', 'validada', 'validada_por'],
            'tutorships' => ['student_id', 'tutor_id', 'fecha', 'motivo', 'observaciones', 'compromisos', 'deleted_at'],
            'interventions' => ['student_id', 'origen', 'descripcion', 'responsable_id', 'fecha', 'resultado', 'estado', 'deleted_at'],
            'alerts' => ['student_id', 'tipo', 'detalle', 'fecha_deteccion', 'atendida'],
            'import_batches' => ['tipo', 'archivo', 'user_id', 'filas_ok', 'filas_error', 'log', 'created_at'],
            'audit_logs' => ['user_id', 'auditable_type', 'auditable_id', 'accion', 'valores_anteriores', 'valores_nuevos', 'ip', 'created_at'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasColumns($table, $columns), "Faltan columnas en $table");
        }
    }

    public function test_experience_is_pending_validation_by_default(): void
    {
        $student = $this->makeStudent();
        $experience = Experience::create([
            'student_id' => $student->id,
            'experience_type_id' => ExperienceType::create(['nombre' => 'Semillero', 'categoria' => 'Investigación'])->id,
            'nombre' => 'Semillero SIGMA', 'fecha_inicio' => '2022-02-01',
        ]);

        $this->assertFalse($experience->fresh()->validada);
        $this->assertSame(1, $student->experiences()->count());
    }

    public function test_soft_deleted_tutorships_and_interventions_are_hidden_but_kept(): void
    {
        $student = $this->makeStudent();
        $tutor = User::factory()->create();
        $tutorship = Tutorship::create(['student_id' => $student->id, 'tutor_id' => $tutor->id, 'fecha' => '2026-09-12', 'motivo' => 'Seguimiento']);
        $intervention = Intervention::create(['student_id' => $student->id, 'origen' => 'tutoria', 'descripcion' => 'Plan de apoyo', 'responsable_id' => $tutor->id, 'fecha' => '2026-09-13']);

        $tutorship->delete();
        $intervention->delete();

        $this->assertSame(0, $student->tutorships()->count());
        $this->assertSame(0, $student->interventions()->count());
        $this->assertSame(1, Tutorship::withTrashed()->count());
        $this->assertSame(1, Intervention::withTrashed()->count());
    }

    public function test_audit_log_is_polymorphic_and_stores_json(): void
    {
        $student = $this->makeStudent();
        $log = AuditLog::create([
            'user_id' => User::factory()->create()->id,
            'auditable_type' => $student->getMorphClass(), 'auditable_id' => $student->id,
            'accion' => 'update', 'valores_anteriores' => ['estado' => 'activo'], 'valores_nuevos' => ['estado' => 'en_riesgo'],
            'ip' => '127.0.0.1',
        ]);

        $log = $log->fresh();
        $this->assertTrue($log->auditable->is($student));
        $this->assertSame(['estado' => 'en_riesgo'], $log->valores_nuevos);
        $this->assertNotNull($log->created_at);
    }

    public function test_alerts_and_import_batches_can_be_created(): void
    {
        $student = $this->makeStudent();
        Alert::create(['student_id' => $student->id, 'tipo' => 'caida_papa', 'detalle' => 'Caída del PAPA', 'fecha_deteccion' => '2026-09-12']);
        $batch = ImportBatch::create(['tipo' => 'estudiantes', 'archivo' => 'estudiantes.xlsx', 'user_id' => User::factory()->create()->id, 'filas_ok' => 40, 'filas_error' => 2]);

        $this->assertFalse($student->alerts()->first()->atendida);
        $this->assertNotNull($batch->fresh()->created_at);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SupportSchemaTest`
Expected: FAIL.

- [ ] **Step 3: Crear migraciones y modelos**

```php
// 000019 experiences
$table->id();
$table->foreignId('student_id')->constrained()->restrictOnDelete();
$table->foreignId('experience_type_id')->constrained()->restrictOnDelete();
$table->string('nombre');
$table->text('descripcion')->nullable();
$table->date('fecha_inicio');
$table->date('fecha_fin')->nullable();
$table->string('alcance')->nullable();
$table->string('institucion')->nullable();
$table->string('convenio')->nullable();
$table->foreignId('docente_id')->nullable()->constrained('users')->restrictOnDelete();
$table->string('rol')->nullable();
$table->boolean('validada')->default(false);
$table->foreignId('validada_por')->nullable()->constrained('users')->restrictOnDelete();
$table->timestamps();

// 000020 tutorships
$table->id();
$table->foreignId('student_id')->constrained()->restrictOnDelete();
$table->foreignId('tutor_id')->constrained('users')->restrictOnDelete();
$table->date('fecha');
$table->string('motivo');
$table->text('observaciones')->nullable();
$table->text('compromisos')->nullable();
$table->timestamps();
$table->softDeletes();

// 000021 interventions
$table->id();
$table->foreignId('student_id')->constrained()->restrictOnDelete();
$table->string('origen'); // solicitud | alerta | tutoria | observacion_profesor
$table->text('descripcion');
$table->foreignId('responsable_id')->constrained('users')->restrictOnDelete();
$table->date('fecha');
$table->text('resultado')->nullable();
$table->string('estado')->default('abierta');
$table->timestamps();
$table->softDeletes();

// 000022 alerts
$table->id();
$table->foreignId('student_id')->constrained()->restrictOnDelete();
$table->string('tipo');
$table->text('detalle')->nullable();
$table->date('fecha_deteccion');
$table->boolean('atendida')->default(false);
$table->timestamps();

// 000023 import_batches
$table->id();
$table->string('tipo');
$table->string('archivo');
$table->foreignId('user_id')->constrained()->restrictOnDelete();
$table->unsignedInteger('filas_ok')->default(0);
$table->unsignedInteger('filas_error')->default(0);
$table->text('log')->nullable();
$table->timestamp('created_at')->useCurrent();

// 000024 audit_logs
$table->id();
$table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
$table->morphs('auditable');
$table->string('accion');
$table->jsonb('valores_anteriores')->nullable();
$table->jsonb('valores_nuevos')->nullable();
$table->string('ip', 45)->nullable();
$table->timestamp('created_at')->useCurrent();
```

Modelos:

```php
class Experience extends Model
{
    protected $fillable = ['student_id', 'experience_type_id', 'nombre', 'descripcion', 'fecha_inicio', 'fecha_fin', 'alcance', 'institucion', 'convenio', 'docente_id', 'rol', 'validada', 'validada_por'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'validada' => 'boolean'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function type(): BelongsTo { return $this->belongsTo(ExperienceType::class, 'experience_type_id'); }
    public function docente(): BelongsTo { return $this->belongsTo(User::class, 'docente_id'); }
    public function validador(): BelongsTo { return $this->belongsTo(User::class, 'validada_por'); }
}

class Tutorship extends Model
{
    use SoftDeletes;

    protected $fillable = ['student_id', 'tutor_id', 'fecha', 'motivo', 'observaciones', 'compromisos'];

    protected $casts = ['fecha' => 'date'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function tutor(): BelongsTo { return $this->belongsTo(User::class, 'tutor_id'); }
}

class Intervention extends Model
{
    use SoftDeletes;

    protected $fillable = ['student_id', 'origen', 'descripcion', 'responsable_id', 'fecha', 'resultado', 'estado'];

    protected $casts = ['fecha' => 'date'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function responsable(): BelongsTo { return $this->belongsTo(User::class, 'responsable_id'); }
}

class Alert extends Model
{
    protected $fillable = ['student_id', 'tipo', 'detalle', 'fecha_deteccion', 'atendida'];

    protected $casts = ['fecha_deteccion' => 'date', 'atendida' => 'boolean'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}

class ImportBatch extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['tipo', 'archivo', 'user_id', 'filas_ok', 'filas_error', 'log'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'auditable_type', 'auditable_id', 'accion', 'valores_anteriores', 'valores_nuevos', 'ip'];

    protected $casts = ['valores_anteriores' => 'array', 'valores_nuevos' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function auditable(): MorphTo { return $this->morphTo(); }
}
```

Añadir en `Student`: `experiences()`, `tutorships()`, `interventions()`, `alerts()` (`hasMany`). Imports: `SoftDeletes`, `MorphTo`, `BelongsTo`, `HasMany`. `ExperienceType`: `experiences(): HasMany`.

- [ ] **Step 4: Run test to verify it passes, luego toda la suite de BD**

Run: `php artisan test --filter=SupportSchemaTest && php artisan test tests/Feature/Database`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations app/Models tests
git commit -m "feat(db): agregar experiencias, tutorías, intervenciones, alertas y bitácora"
```

---

### Task 8: Seeders de catálogos y datos demo

**Files:**
- Create: `database/seeders/CatalogSeeder.php`, `database/seeders/DemoSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Database/SeederTest.php`

**Interfaces:**
- Consumes: todos los modelos de Tasks 2–7.
- Produces: usuarios demo (contraseña `password`): `coordinacion@unal.edu.co` (Ana Restrepo), `tutor@unal.edu.co` (Carlos Pineda), `profesor@unal.edu.co` (María Londoño), `estudiante@unal.edu.co` (Pérez Rojas, Juan Sebastián, código 2020123); 7 estudiantes más con `est<codigo>@unal.edu.co`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/Database/SeederTest.php

namespace Tests\Feature\Database;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_one_demo_user_per_role_with_a_known_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (Role::cases() as $role) {
            $this->assertTrue(User::where('role', $role->value)->exists(), "Sin usuario {$role->value}");
        }
        $this->assertTrue(Hash::check('password', User::where('email', 'coordinacion@unal.edu.co')->value('password')));
    }

    public function test_seeders_create_the_roster_and_juan_perez_history(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(8, Student::count());
        $juan = Student::where('codigo', '2020123')->firstOrFail();
        $this->assertSame(7, $juan->academicRecords()->count());
        $this->assertSame('4.10', $juan->academicRecords()->whereHas('term', fn ($q) => $q->where('codigo', '2026-2'))->first()->papa);
        $this->assertSame(3, $juan->tutorships()->count());
        $this->assertGreaterThanOrEqual(4, $juan->experiences()->count());
        $this->assertTrue(AcademicTerm::where('codigo', '2019-1')->exists());
    }

    public function test_seeding_twice_does_not_duplicate_or_fail(): void
    {
        $this->seed(DatabaseSeeder::class);
        $counts = [User::count(), Student::count(), AcademicTerm::count(), Student::where('codigo', '2020123')->firstOrFail()->tutorships()->count()];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts, [User::count(), Student::count(), AcademicTerm::count(), Student::where('codigo', '2020123')->firstOrFail()->tutorships()->count()]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SeederTest`
Expected: FAIL (seeders vacíos).

- [ ] **Step 3: Implementar seeders**

`database/seeders/DatabaseSeeder.php` — `run()`:

```php
        $this->call([CatalogSeeder::class, DemoSeeder::class]);
```

`database/seeders/CatalogSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\Company;
use App\Models\ExperienceType;
use App\Models\RequestType;
use App\Models\School;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(2019, 2026) as $year) {
            AcademicTerm::updateOrCreate(['codigo' => "$year-1"], ['fecha_inicio' => "$year-02-01", 'fecha_fin' => "$year-06-15"]);
            AcademicTerm::updateOrCreate(['codigo' => "$year-2"], ['fecha_inicio' => "$year-08-01", 'fecha_fin' => "$year-12-15"]);
        }

        foreach ([
            'Cancelación de asignatura', 'Reingreso al programa', 'Homologación de créditos',
            'Ampliación de cupo de créditos', 'Otras',
        ] as $nombre) {
            RequestType::updateOrCreate(['nombre' => $nombre]);
        }

        foreach ([
            ['Pasantía', 'Pasantía'],
            ['Semillero o grupo de investigación', 'Investigación'],
            ['Proyecto de investigación o extensión', 'Investigación'],
            ['Grupo deportivo o cultural', 'Deporte y cultura'],
            ['Asistencia o ponencia en evento', 'Eventos y publicaciones'],
            ['Publicación o certificación', 'Eventos y publicaciones'],
            ['Vinculación (auxiliar, becario o monitor)', 'Vinculación'],
            ['Programa SUMA', 'Programa'],
        ] as [$nombre, $categoria]) {
            ExperienceType::updateOrCreate(['nombre' => $nombre], ['categoria' => $categoria]);
        }

        School::updateOrCreate(['nombre' => 'Colegio Nacional de Manizales'], ['caracter' => 'publico', 'municipio' => 'Manizales']);
        School::updateOrCreate(['nombre' => 'Colegio Los Ángeles'], ['caracter' => 'privado', 'municipio' => 'Manizales']);

        Company::updateOrCreate(['nombre' => 'Softlogic S.A.S.'], ['nit' => '900123456-1', 'sector' => 'Tecnología', 'tamano' => 'Mediana', 'ciudad' => 'Manizales']);
    }
}
```

`database/seeders/DemoSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\AdmissionProfile;
use App\Models\Alert;
use App\Models\Company;
use App\Models\CommitteeRequest;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Models\Internship;
use App\Models\RequestStatusHistory;
use App\Models\RequestType;
use App\Models\School;
use App\Models\Student;
use App\Models\Thesis;
use App\Models\TutorAssignment;
use App\Models\Tutorship;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $coordinacion = $this->user('Ana Restrepo', 'coordinacion@unal.edu.co', Role::Coordinacion);
        $tutor = $this->user('Carlos Pineda', 'tutor@unal.edu.co', Role::Tutor);
        $profesor = $this->user('María Londoño', 'profesor@unal.edu.co', Role::Profesor);

        $term = AcademicTerm::where('codigo', '2026-2')->firstOrFail();

        // [codigo, nombre, cohorte, papa, avance, estado]
        $roster = [
            ['2019087', 'Ruiz Salazar, Andrés', '2019-2', '3.20', 88, 'activo'],
            ['2020123', 'Pérez Rojas, Juan Sebastián', '2020-1', '4.10', 72, 'activo'],
            ['2021045', 'Gómez Ruiz, Laura', '2021-1', '3.70', 55, 'activo'],
            ['2021098', 'Cardona Vélez, Mateo', '2021-2', '2.90', 41, 'en_riesgo'],
            ['2022011', 'Martínez Cruz, Diana', '2022-1', '4.40', 31, 'activo'],
            ['2022076', 'Ospina Lara, Sofía', '2022-2', '3.50', 27, 'activo'],
            ['2023019', 'Herrera Niño, Camilo', '2023-1', '3.80', 19, 'activo'],
            ['2023044', 'Valencia Toro, Ana', '2023-2', '4.00', 15, 'intercambio'],
        ];

        $students = [];
        foreach ($roster as [$codigo, $nombre, $cohorte, $papa, $avance, $estado]) {
            $email = $codigo === '2020123' ? 'estudiante@unal.edu.co' : "est{$codigo}@unal.edu.co";
            $user = $this->user($nombre, $email, Role::Estudiante);

            $student = Student::updateOrCreate(['codigo' => $codigo], [
                'user_id' => $user->id, 'documento' => '10'.$codigo, 'cohorte' => $cohorte,
                'estado' => $estado, 'fecha_ingreso' => substr($cohorte, 0, 4).(str_ends_with($cohorte, '-1') ? '-02-03' : '-08-03'),
            ]);
            $students[$codigo] = $student;

            AcademicRecord::updateOrCreate(['student_id' => $student->id, 'term_id' => $term->id], [
                'papa' => $papa, 'promedio_periodo' => $papa, 'cupo_creditos' => 21,
                'creditos_cursados' => (int) round($avance * 160 / 100), 'porcentaje_avance' => $avance,
                'numero_matricula' => 7, 'sincronizado_en' => now(),
            ]);
        }

        $juan = $students['2020123'];
        $this->seedJuanHistory($juan, $term, $tutor, $profesor);
        $this->seedRequests($students, $term, $coordinacion);
    }

    private function seedJuanHistory(Student $juan, AcademicTerm $term, User $tutor, User $profesor): void
    {
        $juan->academicRecords()->where('term_id', $term->id)->update(['promedio_periodo' => '4.00']);

        foreach ([['2020-1', '3.40', 16], ['2021-1', '3.60', 32], ['2021-2', '3.50', 48], ['2022-1', '3.90', 66], ['2022-2', '4.20', 84], ['2023-1', '4.00', 100]] as [$periodo, $papa, $creditos]) {
            AcademicRecord::updateOrCreate(
                ['student_id' => $juan->id, 'term_id' => AcademicTerm::where('codigo', $periodo)->value('id')],
                ['papa' => $papa, 'promedio_periodo' => $papa, 'cupo_creditos' => 21, 'creditos_cursados' => $creditos,
                    'porcentaje_avance' => round($creditos / 160 * 100, 2), 'numero_matricula' => 1]
            );
        }

        AdmissionProfile::updateOrCreate(['student_id' => $juan->id], [
            'school_id' => School::where('nombre', 'Colegio Nacional de Manizales')->value('id'),
            'puntaje_saber11' => 340.5, 'puntaje_admision' => 78.4,
        ]);

        TutorAssignment::updateOrCreate(['student_id' => $juan->id, 'term_id' => $term->id], ['tutor_id' => $tutor->id]);

        foreach ([
            ['2026-09-12', 'Seguimiento a carga académica', 'Acordó reducir a 15 créditos el próximo periodo.'],
            ['2026-05-03', 'Orientación sobre modalidad de grado', 'Interesado en pasantía; se remitió a Coordinación.'],
            ['2025-11-18', 'Revisión de avance', 'Sin novedades; continúa según plan.'],
        ] as [$fecha, $motivo, $observaciones]) {
            Tutorship::updateOrCreate(
                ['student_id' => $juan->id, 'tutor_id' => $tutor->id, 'fecha' => $fecha],
                ['motivo' => $motivo, 'observaciones' => $observaciones]
            );
        }

        foreach ([
            ['Semillero de investigación SIGMA', 'Semillero o grupo de investigación', '2022-02-01', '2022-06-15'],
            ['Monitoría de Bases de Datos', 'Vinculación (auxiliar, becario o monitor)', '2023-08-01', '2023-12-15'],
            ['Pasantía Sígueme · Univ. de Antioquia', 'Pasantía', '2024-02-01', '2024-06-15'],
            ['Ponencia en Congreso Colombiano de Computación', 'Asistencia o ponencia en evento', '2025-08-20', '2025-08-22'],
        ] as [$nombre, $tipo, $inicio, $fin]) {
            Experience::updateOrCreate(
                ['student_id' => $juan->id, 'nombre' => $nombre],
                ['experience_type_id' => ExperienceType::where('nombre', $tipo)->value('id'), 'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'validada' => true]
            );
        }

        Thesis::updateOrCreate(['student_id' => $juan->id, 'term_id' => $term->id], [
            'modalidad' => 'pasantia', 'estado' => 'en_desarrollo', 'director_id' => $profesor->id,
        ]);

        Internship::updateOrCreate(
            ['student_id' => $juan->id, 'company_id' => Company::where('nombre', 'Softlogic S.A.S.')->value('id')],
            ['asesor_id' => $tutor->id, 'fecha_inicio' => '2026-07-01', 'fecha_fin' => '2026-12-15']
        );

        Alert::updateOrCreate(['student_id' => $juan->id, 'tipo' => 'caida_papa'], [
            'detalle' => 'Caída del PAPA frente al periodo anterior', 'fecha_deteccion' => '2026-09-12',
        ]);
    }

    private function seedRequests(array $students, AcademicTerm $term, User $coordinacion): void
    {
        foreach ([
            ['2020123', 'Cancelación de asignatura', 'radicada', '2026-09-10'],
            ['2021045', 'Reingreso al programa', 'en_revision', '2026-09-05'],
        ] as [$codigo, $tipo, $estado, $fecha]) {
            $request = CommitteeRequest::updateOrCreate(
                ['student_id' => $students[$codigo]->id, 'request_type_id' => RequestType::where('nombre', $tipo)->value('id'), 'term_id' => $term->id],
                ['descripcion' => "Solicitud de ejemplo: $tipo.", 'estado' => $estado, 'fecha_radicacion' => $fecha]
            );

            RequestStatusHistory::firstOrCreate(
                ['request_id' => $request->id, 'estado_nuevo' => 'radicada'],
                ['estado_anterior' => null, 'user_id' => $request->student->user_id, 'fecha' => $fecha]
            );
        }
    }

    private function user(string $name, string $email, Role $role): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'role' => $role, 'password' => 'password', 'active' => true]
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes y probar contra la BD real**

Run: `php artisan test --filter=SeederTest`
Expected: PASS.

Run: `php artisan migrate:fresh --seed && psql -d trayectoria_estudiantil -c '\dt' | grep -c 'public'`
Expected: sin errores; el conteo debe ser 29 (25 de dominio + `migrations`, `password_reset_tokens`, `failed_jobs`, `personal_access_tokens`). Si el número es otro, listar con `\dt` y reconciliar contra la lista del spec.

- [ ] **Step 5: Commit**

```bash
git add database/seeders tests/Feature/Database/SeederTest.php
git commit -m "feat(db): agregar seeders de catálogos y datos demo"
```

---

### Task 9: Autenticación real, middleware de roles y rutas protegidas

**Files:**
- Create: `app/Http/Middleware/EnsureRole.php`, `tests/Feature/AuthTest.php`
- Modify: `app/Http/Kernel.php:62-72`, `app/Http/Middleware/Authenticate.php:18`, `app/Providers/RouteServiceProvider.php:20`, `app/Http/Controllers/LoginController.php`, `routes/web.php`, `resources/views/auth/login.blade.php:35-36`, `resources/views/components/topbar.blade.php`, `tests/Feature/{LoginTest,PanelTest,IndicatorTest,StudentTest,RequestTest,NavigationTest}.php`

**Interfaces:**
- Consumes: `Role`, `User` (con `homeRoute()`), `actingAsRole()`, rutas `login.show`, `panel.index`, `requests.create` ya existentes.
- Produces: alias de middleware `role:<roles>`; ruta `POST /logout` (`logout`); login con error de sesión `email` = `Credenciales inválidas.`; `RouteServiceProvider::HOME = '/panel'`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/AuthTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function user(Role $role, array $attrs = []): User
    {
        return User::factory()->create($attrs + ['role' => $role, 'email' => $role->value.'@unal.edu.co', 'password' => 'password']);
    }

    public function test_valid_login_redirects_by_role(): void
    {
        $this->user(Role::Coordinacion);
        $this->post('/login', ['email' => 'coordinacion@unal.edu.co', 'password' => 'password'])->assertRedirect('/panel');
        $this->assertAuthenticated();

        $this->post('/logout');
        $this->user(Role::Estudiante);
        $this->post('/login', ['email' => 'estudiante@unal.edu.co', 'password' => 'password'])->assertRedirect('/solicitudes/nueva');
    }

    public function test_unknown_email_and_wrong_password_give_the_same_generic_error(): void
    {
        $this->user(Role::Tutor);

        $unknown = $this->from('/login')->post('/login', ['email' => 'nadie@unal.edu.co', 'password' => 'password']);
        $wrong = $this->from('/login')->post('/login', ['email' => 'tutor@unal.edu.co', 'password' => 'incorrecta']);

        $unknown->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $wrong->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->assertGuest();
    }

    public function test_empty_or_missing_fields_give_the_generic_error_instead_of_failing(): void
    {
        $this->from('/login')->post('/login', [])->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->from('/login')->post('/login', ['email' => 'tutor@unal.edu.co'])->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->from('/login')->post('/login', ['email' => '', 'password' => ''])->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->assertGuest();
    }

    public function test_email_is_trimmed_and_case_insensitive(): void
    {
        $this->user(Role::Coordinacion);

        $this->post('/login', ['email' => '  Coordinacion@UNAL.edu.co ', 'password' => 'password'])->assertRedirect('/panel');
        $this->assertAuthenticated();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $this->user(Role::Profesor, ['active' => false]);

        $this->from('/login')->post('/login', ['email' => 'profesor@unal.edu.co', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->assertGuest();
    }

    public function test_failed_login_keeps_the_typed_email(): void
    {
        $this->followingRedirects()->from('/login')
            ->post('/login', ['email' => 'alguien@unal.edu.co', 'password' => 'x'])
            ->assertSee('alguien@unal.edu.co')
            ->assertSee('Credenciales inválidas.');
    }

    public function test_guests_are_redirected_to_login_and_authenticated_users_leave_login(): void
    {
        $this->get('/panel')->assertRedirect('/login');
        $this->get('/estudiantes')->assertRedirect('/login');
        $this->post('/logout')->assertRedirect('/login');

        $this->actingAsRole(Role::Tutor);
        $this->get('/login')->assertRedirect('/panel');
    }

    public function test_logout_ends_the_session(): void
    {
        $this->user(Role::Coordinacion);
        $this->post('/login', ['email' => 'coordinacion@unal.edu.co', 'password' => 'password']);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_role_matrix_returns_403_on_foreign_routes_and_200_on_own(): void
    {
        $matrix = [
            ['/panel', [Role::Estudiante, Role::Profesor, Role::Tutor, Role::Coordinacion]],
            ['/estudiantes', [Role::Profesor, Role::Tutor, Role::Coordinacion]],
            ['/estudiantes/2020123', [Role::Profesor, Role::Tutor, Role::Coordinacion]],
            ['/solicitudes/nueva', [Role::Estudiante, Role::Coordinacion]],
            ['/indicadores', [Role::Coordinacion]],
        ];

        foreach ($matrix as [$url, $allowed]) {
            foreach (Role::cases() as $role) {
                $this->actingAsRole($role);
                in_array($role, $allowed, true)
                    ? $this->get($url)->assertOk()
                    : $this->get($url)->assertForbidden();
            }
        }
    }

    public function test_login_post_without_csrf_token_returns_419(): void
    {
        // Laravel omite la verificación CSRF cuando el entorno es "testing".
        $this->app['env'] = 'production';

        $this->post('/login', ['email' => 'a@b.co', 'password' => 'x'])->assertStatus(419);
    }
}
```

Actualizar tests existentes (cada `test_*` que haga `$this->get(...)` autenticado): en `PanelTest` añadir al inicio del test `$this->actingAsRole(Role::Coordinacion);`; en `IndicatorTest` lo mismo; en `StudentTest` (los 3 tests) `$this->actingAsRole(Role::Tutor);`; en `RequestTest` `$this->actingAsRole(Role::Estudiante);`; con `use App\Enums\Role;` en cada archivo. En `LoginTest` eliminar `test_submitting_login_redirects_to_panel_without_validation` (lo reemplaza `AuthTest`). Reescribir `NavigationTest`:

```php
<?php
// tests/Feature/NavigationTest.php

namespace Tests\Feature;

use App\Enums\Role;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    public function test_every_internal_screen_links_to_the_other_implemented_screens(): void
    {
        $this->actingAsRole(Role::Coordinacion);

        $this->get('/panel')->assertSee('href="/estudiantes"', false);

        $students = $this->get('/estudiantes');
        $students->assertSee('href="/estudiantes/2019087"', false);
        $students->assertSee('href="/indicadores"', false);

        $this->get('/solicitudes/nueva')->assertSee('href="/panel"', false);
    }

    public function test_all_six_screens_return_200(): void
    {
        $this->get('/login')->assertOk();

        $this->actingAsRole(Role::Coordinacion);
        foreach (['/panel', '/estudiantes', '/estudiantes/2019087', '/solicitudes/nueva', '/indicadores'] as $route) {
            $this->get($route)->assertOk();
        }
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AuthTest`
Expected: FAIL (login simulado, sin middleware ni logout).

- [ ] **Step 3: Implementar**

`app/Http/Middleware/EnsureRole.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role->value, $roles, true), 403);

        return $next($request);
    }
}
```

`app/Http/Kernel.php` — añadir a `$middlewareAliases`: `'role' => \App\Http\Middleware\EnsureRole::class,`.
`app/Http/Middleware/Authenticate.php` — `route('login')` → `route('login.show')`.
`app/Providers/RouteServiceProvider.php` — `HOME = '/panel'`.

`app/Http/Controllers/LoginController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = [
            'email' => Str::lower(trim((string) $request->input('email'))),
            'password' => (string) $request->input('password'),
            'active' => true,
        ];

        if ($credentials['email'] === '' || $credentials['password'] === '' || ! Auth::attempt($credentials)) {
            return back()
                ->withInput(['email' => $credentials['email']])
                ->withErrors(['email' => 'Credenciales inválidas.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route(Auth::user()->role->homeRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.show');
    }
}
```

`routes/web.php` — reemplazar desde la ruta de login hasta el final por:

```php
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login.show');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/panel', [PanelController::class, 'index'])->name('panel.index');

    Route::middleware('role:profesor,tutor,coordinacion')->group(function () {
        Route::get('/estudiantes', [StudentController::class, 'index'])->name('students.index');
        Route::get('/estudiantes/{id}', [StudentController::class, 'show'])->name('students.show');
    });

    Route::middleware('role:estudiante,coordinacion')->group(function () {
        Route::get('/solicitudes/nueva', [RequestController::class, 'create'])->name('requests.create');
        Route::post('/solicitudes/nueva', [RequestController::class, 'store'])->name('requests.store');
    });

    Route::get('/indicadores', [IndicatorController::class, 'index'])
        ->middleware('role:coordinacion')
        ->name('indicators.index');
});
```

`resources/views/auth/login.blade.php` — reemplazar las dos líneas de `x-form-field` (35-36) por:

```blade
                <x-form-field label="Correo institucional" name="email" type="email" placeholder="usuario@unal.edu.co" icon="mail" :value="old('email')" />
                <x-form-field label="Contraseña" name="password" type="password" placeholder="••••••••••" icon="eye-off" />

                @error('email')
                    <p class="text-sm font-medium text-[var(--color-danger)]">{{ $message }}</p>
                @enderror
```

`resources/views/components/topbar.blade.php` — después del bloque del avatar/usuario (antes de `</header>`), añadir:

```blade
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex items-center gap-1 rounded-[var(--radius-md)] px-2 py-1 text-xs font-medium text-[var(--text-secondary)] hover:bg-[var(--surface-sunken)]" aria-label="Cerrar sesión">
            <x-lucide-log-out class="h-4 w-4" />
            <span class="hidden sm:inline">Salir</span>
        </button>
    </form>
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test`
Expected: toda la suite PASS. Si `test_login_post_without_csrf_token_returns_419` falla porque el cambio de `env` no se refleja, usar en su lugar `$this->withMiddleware()` y una subclase de `VerifyCsrfToken` que sobrescriba `runningUnitTests(): bool { return false; }` enlazada con `$this->app->bind(\App\Http\Middleware\VerifyCsrfToken::class, …)`; reportar el cambio en el commit.

- [ ] **Step 5: Commit**

```bash
git add app routes resources tests
git commit -m "feat(auth): agregar login real, logout y permisos por rol"
```

---

### Task 10: Usuario y menú reales en las pantallas, y documentación

**Files:**
- Modify: `app/Support/Navigation.php`, `app/Http/Controllers/{Panel,Student,Indicator,Request}Controller.php`, `CLAUDE.md`
- Test: `tests/Feature/ShellUserTest.php`

**Interfaces:**
- Consumes: `User::viewData()`, `Role`, `Navigation::{coordinator,coordinatorWithTutorExtras,studentPortal}`.
- Produces: `Navigation::forRole(Role $role, string $active): array`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/ShellUserTest.php

namespace Tests\Feature;

use App\Enums\Role;
use App\Support\Navigation;
use Tests\TestCase;

class ShellUserTest extends TestCase
{
    public function test_topbar_shows_the_authenticated_user_and_role(): void
    {
        $this->actingAsRole(Role::Coordinacion, ['name' => 'Laura Mejía']);

        $this->get('/panel')->assertSee('Laura Mejía')->assertSee('Coordinación')->assertSee('Cerrar sesión', false);
    }

    public function test_every_screen_uses_the_session_user_not_the_mock_one(): void
    {
        $this->actingAsRole(Role::Tutor, ['name' => 'Tomás Gil']);
        $this->get('/estudiantes')->assertSee('Tomás Gil')->assertDontSee('Ana Restrepo');
        $this->get('/estudiantes/2020123')->assertSee('Tomás Gil')->assertDontSee('Carlos Pineda</span>', false);

        $this->actingAsRole(Role::Coordinacion, ['name' => 'Laura Mejía']);
        $this->get('/indicadores')->assertSee('Laura Mejía')->assertDontSee('Ana Restrepo');

        $this->actingAsRole(Role::Estudiante, ['name' => 'Sara Díaz']);
        $this->get('/solicitudes/nueva')->assertSee('Sara Díaz')->assertDontSee('Juan Pérez');
    }

    public function test_menu_depends_on_the_role(): void
    {
        $this->actingAsRole(Role::Estudiante);
        $this->get('/panel')->assertSee('Mis solicitudes')->assertDontSee('Indicadores');

        $this->actingAsRole(Role::Tutor);
        $this->get('/estudiantes')->assertSee('Registrar tutoría')->assertSee('Mis asesorías');

        $this->actingAsRole(Role::Coordinacion);
        $this->get('/panel')->assertSee('Indicadores')->assertSee('Usuarios y roles')->assertDontSee('Mis asesorías');
    }

    public function test_for_role_picks_the_matching_navigation(): void
    {
        $labels = fn (array $groups) => collect($groups)->pluck('items')->flatten(1)->pluck('label')->all();

        $this->assertContains('Mi práctica', $labels(Navigation::forRole(Role::Estudiante, 'panel')));
        $this->assertContains('Mis asesorías', $labels(Navigation::forRole(Role::Tutor, 'panel')));
        $this->assertNotContains('Mis asesorías', $labels(Navigation::forRole(Role::Coordinacion, 'panel')));
        $this->assertNotContains('Mis asesorías', $labels(Navigation::forRole(Role::Profesor, 'panel')));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ShellUserTest`
Expected: FAIL (los controladores siguen usando usuarios mock).

- [ ] **Step 3: Implementar**

`app/Support/Navigation.php` — añadir `use App\Enums\Role;` y el método:

```php
    public static function forRole(Role $role, string $active): array
    {
        return match ($role) {
            Role::Estudiante => self::studentPortal($active),
            Role::Tutor => self::coordinatorWithTutorExtras($active),
            default => self::coordinator($active),
        };
    }
```

Controladores — en cada `view(...)` reemplazar las líneas `'user' => [...]` y `'navGroups' => Navigation::…` por (con `$active` propio de cada pantalla):

```php
// PanelController::index
'user' => auth()->user()->viewData(),
'navGroups' => Navigation::forRole(auth()->user()->role, 'panel'),

// StudentController::index
'user' => auth()->user()->viewData(),
'navGroups' => Navigation::forRole(auth()->user()->role, 'estudiantes'),

// StudentController::show (mismo reemplazo, 'estudiantes')
// IndicatorController::index (mismo reemplazo, 'indicadores')
// RequestController::create (mismo reemplazo, 'solicitudes')
```

`CLAUDE.md` — reemplazar el párrafo "**Current scope is visual/presentational only** …" por:

```
**Current scope**: PostgreSQL (`trayectoria_estudiantil`, tests en `trayectoria_estudiantil_test`)
con las 25 tablas del PDF §5.3, un modelo Eloquent por tabla y seeders
(`php artisan migrate:fresh --seed`; usuarios demo `coordinacion@`, `tutor@`,
`profesor@`, `estudiante@unal.edu.co`, contraseña `password`). Login real con
sesión y 4 roles (`App\Enums\Role`, middleware `role:`). Las pantallas siguen
mostrando datos mock de los controladores salvo el usuario y el menú, que salen
de la sesión (`Navigation::forRole`); conectarlas a la BD es el sub-proyecto 2.
```

Y en la sección Commands añadir: `php artisan migrate:fresh --seed   # recrea la BD con datos demo` y la nota "los tests usan `RefreshDatabase` sobre `trayectoria_estudiantil_test`; los tests de pantalla usan `actingAsRole()` y no tocan la BD". Quitar de "Project" la frase "no real authentication, database, or server-side validation" y de "Routing pattern" la de "no model/migration layer yet".

- [ ] **Step 4: Run full suite y verificación manual**

Run: `php artisan test`
Expected: toda la suite PASS.

Run: `php artisan migrate:fresh --seed && php artisan serve` y en el navegador entrar con `coordinacion@unal.edu.co` / `password`; verificar `/panel`, cerrar sesión, entrar como `estudiante@unal.edu.co` y comprobar que `/indicadores` da 403.

- [ ] **Step 5: Commit**

```bash
git add app CLAUDE.md tests
git commit -m "feat(auth): mostrar usuario y menú de la sesión en las pantallas y documentar la base de datos"
```

---

## Self-Review (hecha)

- **Cobertura del spec:** BD y `.env`/phpunit (T1); `users.role`, enum, cast, bcrypt (T2); 24 tablas + users (T3–T7); modelos, `SoftDeletes`, FK restrict/cascade (T3–T7); seeders idempotentes y 4 usuarios (T8); login/logout genérico, inactivos, redirección por rol, middleware 403, CSRF 419, correo normalizado, arreglo de `Authenticate`/`HOME` (T9); usuario y menú desde sesión, `Navigation::forRole`, CLAUDE.md (T10); ajuste de tests existentes (T9). Matriz de roles: `/panel` abierto a los 4 roles (spec corregido hoy).
- **Desviación declarada:** `students.user_id` es nullable por el spec, pero los seeders siempre lo llenan porque el nombre del estudiante vive en `users`.
- **Consistencia de tipos:** `viewData()`, `homeRoute()`, `forRole()`, `actingAsRole()`, `makeStudent()/makeTerm()` se definen antes de usarse; relaciones citadas en tests (`followUps`, `statusHistory`, `minute`, `requests`, `company`, `auditable`) están en los modelos de su tarea.
- **Placeholders:** ninguno.
- **Riesgos conocidos:** (1) si el TCP a Postgres exige contraseña, usar `DB_HOST=/tmp` (T1); (2) el test del 419 manipula `env` y tiene alternativa documentada (T9).
