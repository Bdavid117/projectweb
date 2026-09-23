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
}
