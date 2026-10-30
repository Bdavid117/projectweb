<?php

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
        $this->signInSeededTutor();
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
        $response->assertSee('Trabajo de grado · modalidad pasantía');
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

    public function test_tutorship_notes_are_visible_to_the_linked_professor_and_coordination_and_the_unlinked_one_cannot_open_the_profile(): void
    {
        $this->seed(DatabaseSeeder::class);
        $juan = Student::where('codigo', '2020123')->firstOrFail();

        $this->signInSeededTutor();
        $this->get('/estudiantes/'.$juan->id)->assertSee('Últimas tutorías')->assertSee('Acordó reducir a 15 créditos');

        $this->signInSeeded(Role::Coordinacion);
        $this->get('/estudiantes/'.$juan->id)->assertSee('Últimas tutorías')->assertSee('Acordó reducir a 15 créditos');

        // profesor@ no tiene vínculo con Juan: ni la ficha ni las notas.
        $this->signInSeeded(Role::Profesor);
        $this->get('/estudiantes/'.$juan->id)->assertForbidden();
    }
}
