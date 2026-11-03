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
        $response->assertSee('Cada estudiante deja un camino. Aquí se conserva.');
    }

    public function test_the_trace_is_drawn_for_phones_as_well_as_for_desktop(): void
    {
        $html = $this->get('/login')->getContent();

        // Dos motivos (escritorio y móvil), cuatro hilos animados cada uno, y los dos descritos para lectores de pantalla.
        $this->assertSame(8, substr_count($html, 'class="trace-path'));
        $this->assertSame(2, substr_count($html, 'aria-label="Tres corrientes'));
        $this->assertSame(8, substr_count($html, 'pathLength="1"'), 'Sin pathLength el trazo no se dibuja de principio a fin.');
    }

    public function test_no_animated_shape_carries_its_own_transform_attribute(): void
    {
        $html = $this->get('/login')->getContent();

        // La animación CSS usa `transform`: si el nodo lleva el suyo en el atributo (el rombo), lo reemplaza y se ve un cuadrado.
        preg_match_all('/<(?:rect|circle|path|text)\b[^>]*\bclass="[^"]*\btrace-[^"]*"[^>]*>/', $html, $animated);
        $this->assertNotEmpty($animated[0]);
        foreach ($animated[0] as $tag) {
            $this->assertStringNotContainsString('transform=', $tag, 'Envuelva la forma en un <g transform>: '.$tag);
        }
        $this->assertSame(2, substr_count($html, '<g transform="rotate(45'), 'Un rombo por motivo, con su rotación en el grupo.');
    }

    public function test_after_a_failed_login_the_trace_is_already_drawn_instead_of_replaying(): void
    {
        $html = $this->followingRedirects()
            ->from('/login')
            ->post('/login', ['email' => 'nadie@unal.edu.co', 'password' => 'incorrecta'])
            ->getContent();

        $this->assertSame(2, substr_count($html, 'data-static'));
    }
}
