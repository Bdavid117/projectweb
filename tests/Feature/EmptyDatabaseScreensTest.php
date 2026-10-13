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
}
