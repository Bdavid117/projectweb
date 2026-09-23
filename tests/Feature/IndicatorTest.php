<?php

namespace Tests\Feature;

use Tests\TestCase;

class IndicatorTest extends TestCase
{
    public function test_indicators_page_renders_cards_and_tables(): void
    {
        $response = $this->get('/indicadores');

        $response->assertOk();
        $response->assertSee('Indicadores del programa');
        $response->assertSee('PRÁCTICAS EN CURSO');
        $response->assertSee('Distribución del PAPA');
        $response->assertSee('Cancelación de asignatura');
    }
}
