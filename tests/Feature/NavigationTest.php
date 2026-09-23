<?php
// tests/Feature/NavigationTest.php

namespace Tests\Feature;

use Tests\TestCase;

class NavigationTest extends TestCase
{
    public function test_every_internal_screen_links_to_the_other_implemented_screens(): void
    {
        $panel = $this->get('/panel');
        $panel->assertSee('href="/estudiantes"', false);

        $students = $this->get('/estudiantes');
        $students->assertSee('href="/estudiantes/2019087"', false);
        $students->assertSee('href="/indicadores"', false);

        $requestForm = $this->get('/solicitudes/nueva');
        $requestForm->assertSee('href="/panel"', false);
    }

    public function test_all_six_screens_return_200(): void
    {
        foreach (['/login', '/panel', '/estudiantes', '/estudiantes/2019087', '/solicitudes/nueva', '/indicadores'] as $route) {
            $this->get($route)->assertOk();
        }
    }
}
