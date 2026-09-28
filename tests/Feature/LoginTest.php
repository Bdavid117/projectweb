<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Iniciar sesión');
        $response->assertSee('Una visión integral de la trayectoria de cada estudiante.');
    }

    public function test_submitting_login_redirects_to_panel_without_validation(): void
    {
        $response = $this->post('/login', []);

        $response->assertRedirect('/panel');
    }
}
