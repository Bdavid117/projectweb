<?php

namespace Tests\Feature;

use App\Enums\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_form_renders_fields_steps_and_breadcrumb(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $response = $this->get('/solicitudes/nueva');

        $response->assertOk();
        $response->assertSee('Tipo de solicitud *');
        $response->assertSee('Descripción y justificación *');
        $response->assertSee('Radicar solicitud');
        $response->assertSee('¿Qué pasa después?');
        $response->assertSee('Mis solicitudes');
        $response->assertSee('PDF, JPG o PNG · máximo 5 MB por archivo');
    }
}
