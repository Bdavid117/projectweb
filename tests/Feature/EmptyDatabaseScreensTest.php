<?php

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

    public function test_panel_and_indicators_render_with_an_empty_database(): void
    {
        $this->actingAsRole(Role::Coordinacion);

        $this->get('/panel')->assertOk()->assertSee('ESTUDIANTES ACTIVOS')->assertSee('sin periodo');
        $this->get('/indicadores')->assertOk()->assertSee('Indicadores del programa');
    }

    public function test_request_list_renders_with_an_empty_database(): void
    {
        $this->actingAsRole(Role::Coordinacion);

        $this->get('/solicitudes')->assertOk()->assertSee('No hay solicitudes')->assertSee('0 solicitudes');
    }
}
