<?php

namespace Tests\Feature;

use App\Enums\Role;
use Tests\TestCase;

class RequestTest extends TestCase
{
    public function test_request_form_renders_fields_and_options(): void
    {
        $this->actingAsRole(Role::Estudiante);

        $response = $this->get('/solicitudes/nueva');

        $response->assertOk();
        $response->assertSee('Nueva solicitud al comité asesor');
        $response->assertSee('Cancelación de asignatura');
        $response->assertSee('Radicar solicitud');
        $response->assertSee('Falta un campo obligatorio');
    }
}
