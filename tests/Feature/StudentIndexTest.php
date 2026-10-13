<?php

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
