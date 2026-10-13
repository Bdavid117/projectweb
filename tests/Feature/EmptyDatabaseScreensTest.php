<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use App\Models\RequestType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class EmptyDatabaseScreensTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

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

    public function test_filing_without_a_configured_term_is_refused_with_a_message(): void
    {
        $student = $this->makeStudent();
        $this->actingAs($student->user);
        $type = RequestType::create(['nombre' => 'Otras']);

        $this->post('/solicitudes', ['tipo' => $type->id, 'descripcion' => str_repeat('Motivo válido. ', 6)])
            ->assertSessionHasErrors(['tipo' => 'No hay un periodo académico vigente configurado.']);

        $this->assertSame(0, CommitteeRequest::count());
    }
}
