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
