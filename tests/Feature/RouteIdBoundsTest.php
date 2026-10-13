<?php

namespace Tests\Feature;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteIdBoundsTest extends TestCase
{
    use RefreshDatabase;

    public function test_absurdly_large_numeric_ids_return_404_instead_of_a_server_error(): void
    {
        $this->actingAsRole(Role::Coordinacion);
        $huge = '99999999999999999999';

        $this->get('/estudiantes/'.$huge)->assertNotFound();
        $this->get('/solicitudes/'.$huge)->assertNotFound();
        $this->get('/solicitudes/1/soportes/'.$huge)->assertNotFound();
        $this->get('/tutorias/'.$huge.'/editar')->assertNotFound();
        $this->get('/estudiantes/'.$huge.'/tutorias/crear')->assertNotFound();
    }
}
