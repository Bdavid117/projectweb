<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Support\ProgramMetrics;
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
        $response->assertSee('Periodo 2026-2, con datos al');
        $response->assertSee('6 estudiantes activos, 3 con situaciones por atender.');
        $response->assertSee('El PAPA medio es 3,7');
        $response->assertSee('3 décimas menos que el periodo anterior');
        $response->assertSee('el avance medio del plan es 44 %');
        $response->assertSee('Dónde está cada cohorte');
        $response->assertSee('Piden atención');
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Sin atender');
        $response->assertSee('Atendida');
        $response->assertDontSeeText('312');
        $response->assertDontSeeText('20/09/2026');
    }

    public function test_staff_roles_can_open_the_panel(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([Role::Profesor, Role::Coordinacion] as $role) {
            $this->signInSeeded($role);
            $this->get('/panel')->assertOk();
        }

        $this->signInSeededTutor();
        $this->get('/panel')->assertOk();
    }

    public function test_the_student_gets_their_own_panel_not_the_program_one(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $this->get('/panel')->assertOk()->assertSee('Mi panel')->assertDontSee('estudiantes activos');
    }

    public function test_coordination_sees_every_alert_but_a_professor_only_those_of_their_tutored_students(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->signInSeeded(Role::Coordinacion);
        $this->get('/panel')->assertSee('Ruiz Salazar, Andrés')->assertSee('Gómez Ruiz, Laura')->assertSee('Pérez Rojas, Juan Sebastián');

        $this->signInSeededTutor();
        $tutor = $this->get('/panel');
        $tutor->assertSee('Gómez Ruiz, Laura')->assertSee('Pérez Rojas, Juan Sebastián');
        $tutor->assertDontSee('Ruiz Salazar, Andrés');
        $tutor->assertSee('Tutoría registrada a');
    }

    public function test_a_professor_without_tutored_students_sees_no_student_in_alerts_nor_in_recent_activity(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Profesor);

        $response = $this->get('/panel');

        $response->assertOk();
        $response->assertSee('Aún no tiene estudiantes bajo su tutoría.');
        $response->assertSee('Piden atención')->assertSee('Sin situaciones por atender.');
        foreach (['Ruiz Salazar, Andrés', 'Gómez Ruiz, Laura', 'Pérez Rojas, Juan Sebastián'] as $name) {
            $response->assertDontSee($name);
        }
        $response->assertDontSee('Tutoría registrada a');
        $response->assertDontSee('Experiencia validada:');
        $response->assertDontSee('radicada');
    }

    public function test_alert_counter_follows_the_same_scope(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->signInSeededTutor();
        $this->assertSame(2, (new ProgramMetrics)->panel(auth()->user())['headline']['open']);

        $this->signInSeeded(Role::Coordinacion);
        $this->assertSame(3, (new ProgramMetrics)->panel(auth()->user())['headline']['open']);
    }
}
