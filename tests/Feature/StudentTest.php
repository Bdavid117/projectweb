<?php

namespace Tests\Feature;

use App\Enums\Role;
use Tests\TestCase;

class StudentTest extends TestCase
{
    public function test_student_show_renders_profile_regardless_of_id(): void
    {
        $this->actingAsRole(Role::Tutor);

        $response = $this->get('/estudiantes/2020123');

        $response->assertOk();
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Evolución del PAPA por periodo');
        $response->assertSee('Información restringida');
    }

    public function test_student_show_does_not_fail_on_unknown_id(): void
    {
        $this->actingAsRole(Role::Tutor);

        $response = $this->get('/estudiantes/999999');

        $response->assertOk();
        $response->assertSee('Pérez Rojas, Juan Sebastián');
    }
}
