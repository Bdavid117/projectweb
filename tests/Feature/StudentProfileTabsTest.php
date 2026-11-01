<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pestañas de la ficha del estudiante: renderizado por rol, alcance y accesibilidad (RF-08, RNF-12). */
class StudentProfileTabsTest extends TestCase
{
    use RefreshDatabase;

    private function juan(): Student
    {
        return Student::where('codigo', '2020123')->firstOrFail();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_the_tabs_expose_accessible_roles_and_keyboard_wiring(): void
    {
        $this->signInSeeded(Role::Coordinacion);

        $response = $this->get('/estudiantes/'.$this->juan()->id)->assertOk();

        $response->assertSee('role="tablist"', false);
        $response->assertSee('role="tab"', false);
        $response->assertSee('role="tabpanel"', false);
        $response->assertSee('aria-controls="panel-general"', false);
        $response->assertSee('aria-labelledby="tab-academica"', false);
        $response->assertSee('@keydown', false); // navegación por teclado (onKey)
    }

    public function test_every_section_renders_its_content_for_coordination(): void
    {
        $this->signInSeeded(Role::Coordinacion);

        $response = $this->get('/estudiantes/'.$this->juan()->id)->assertOk();

        // Etiquetas de las pestañas.
        foreach (['General', 'Académica', 'Experiencias', 'Trabajo de grado', 'Práctica', 'Solicitudes', 'Tutorías'] as $tab) {
            $response->assertSee($tab);
        }

        // Contenido de cada sección.
        $response->assertSee('Evolución del PAPA por periodo');
        $response->assertSee('Historia académica por periodo');
        $response->assertSee('Semillero de investigación SIGMA');
        $response->assertSee('Trabajo de grado · modalidad pasantía');
        $response->assertSee('Práctica empresarial · Softlogic S.A.S.');
        $response->assertSee('Información restringida');
    }

    public function test_the_requests_tab_lists_the_student_committee_requests(): void
    {
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/estudiantes/'.$this->juan()->id)
            ->assertOk()
            ->assertSee('Solicitudes ante el comité')
            ->assertSee('Cancelación de asignatura')
            ->assertSee('Radicada')
            ->assertSee('Ver detalle');
    }

    public function test_a_student_without_requests_sees_the_empty_state(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        // Ruiz (2019087) no tiene solicitudes sembradas.
        $ruiz = Student::where('codigo', '2019087')->firstOrFail();

        $this->get('/estudiantes/'.$ruiz->id)->assertOk()->assertSee('Sin solicitudes radicadas.');
    }

    public function test_the_linked_professor_sees_every_tab_including_tutorias(): void
    {
        $this->signInSeededTutor();

        $response = $this->get('/estudiantes/'.$this->juan()->id)->assertOk();

        $response->assertSee('id="panel-tutorias"', false);
        $response->assertSee('Cancelación de asignatura');
        $response->assertSee('Seguimiento a carga académica');
    }

    public function test_the_mobile_more_menu_is_present(): void
    {
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/estudiantes/'.$this->juan()->id)->assertOk()->assertSee('Sección:');
    }

    public function test_the_profile_is_forbidden_without_a_link_and_for_students(): void
    {
        // Profesor sin vínculo con Juan.
        $this->signInSeeded(Role::Profesor);
        $this->get('/estudiantes/'.$this->juan()->id)->assertForbidden();

        // El estudiante no abre fichas (usa su portal).
        $this->signInSeeded(Role::Estudiante);
        $this->get('/estudiantes/'.$this->juan()->id)->assertForbidden();
    }

    public function test_the_history_screen_charts_the_papa_and_credit_series(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $this->get('/historia-academica')
            ->assertOk()
            ->assertSee('Evolución del PAPA por periodo')
            ->assertSee('Avance en créditos por periodo');
    }
}
