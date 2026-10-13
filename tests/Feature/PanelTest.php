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

    public function test_the_student_is_redirected_away_from_the_panel(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $this->get('/panel')->assertRedirect('/solicitudes');
    }

    public function test_coordination_sees_every_alert_but_a_tutor_only_those_of_their_students(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->signInSeeded(Role::Coordinacion);
        $this->get('/panel')->assertSee('Ruiz Salazar, Andrés')->assertSee('Gómez Ruiz, Laura')->assertSee('Pérez Rojas, Juan Sebastián');

        $this->signInSeeded(Role::Tutor);
        $tutor = $this->get('/panel');
        $tutor->assertSee('Gómez Ruiz, Laura')->assertSee('Pérez Rojas, Juan Sebastián');
        $tutor->assertDontSee('Ruiz Salazar, Andrés');
        $tutor->assertSee('Tutoría registrada a');
    }

    public function test_a_professor_does_not_see_the_alert_table_nor_tutorship_activity(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Profesor);

        $response = $this->get('/panel');

        $response->assertOk();
        $response->assertSee('ESTUDIANTES ACTIVOS');
        $response->assertDontSee('Situaciones que requieren atención');
        $response->assertDontSee('Ruiz Salazar, Andrés');
        $response->assertDontSee('Tutoría registrada a');
    }

    public function test_alert_counter_follows_the_same_scope(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->signInSeeded(Role::Tutor);
        $this->assertSame('2', collect((new \App\Support\ProgramMetrics)->panel(auth()->user())['indicators'])->firstWhere('label', 'ALERTAS ABIERTAS')['value']);

        $this->signInSeeded(Role::Coordinacion);
        $this->assertSame('3', collect((new \App\Support\ProgramMetrics)->panel(auth()->user())['indicators'])->firstWhere('label', 'ALERTAS ABIERTAS')['value']);
    }
}
