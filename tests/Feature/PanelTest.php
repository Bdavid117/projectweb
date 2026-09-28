<?php

namespace Tests\Feature;

use Tests\TestCase;

class PanelTest extends TestCase
{
    public function test_panel_renders_indicators_and_attention_table(): void
    {
        $response = $this->get('/panel');

        $response->assertOk();
        $response->assertSee('Panel del programa');
        $response->assertSee('ESTUDIANTES ACTIVOS');
        $response->assertSee('312');
        $response->assertSee('Pérez Rojas, Juan');
        $response->assertSee('Sin atender');
    }
}
