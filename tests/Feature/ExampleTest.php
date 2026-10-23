<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Acompañamos cada paso de tu trayectoria estudiantil.');
        $response->assertSee('Accesos principales');
        $response->assertSee('Panel del programa');
        $response->assertSee(route('login.show'), false);
    }
}
