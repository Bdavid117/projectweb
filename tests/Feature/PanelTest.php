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
}
