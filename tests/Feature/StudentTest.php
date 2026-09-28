<?php

namespace Tests\Feature;

use Tests\TestCase;

class StudentTest extends TestCase
{
    public function test_students_index_renders_table_and_filters(): void
    {
        $response = $this->get('/estudiantes');

        $response->assertOk();
        $response->assertSee('Estudiantes');
        $response->assertSee('312 estudiantes activos en el programa · RF-07');
        $response->assertSee('Ruiz Salazar, Andrés');
        $response->assertSee('Mostrando 1–8 de 312 estudiantes');
    }

    public function test_student_show_renders_profile_regardless_of_id(): void
    {
        $response = $this->get('/estudiantes/2020123');

        $response->assertOk();
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Evolución del PAPA por periodo');
        $response->assertSee('Información restringida');
    }

    public function test_student_show_does_not_fail_on_unknown_id(): void
    {
        $response = $this->get('/estudiantes/999999');

        $response->assertOk();
        $response->assertSee('Pérez Rojas, Juan Sebastián');
    }
}
