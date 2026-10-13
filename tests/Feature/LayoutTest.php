<?php

namespace Tests\Feature;

use App\Support\Navigation;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    public function test_app_layout_renders_topbar_sidebar_and_slot_content(): void
    {
        $html = Blade::render(
            '<x-layouts.app :user="$user" :nav-groups="$navGroups">CONTENIDO_DE_PRUEBA</x-layouts.app>',
            [
                'user' => ['initials' => 'AR', 'name' => 'Ana Restrepo', 'role' => 'Coordinación'],
                'navGroups' => Navigation::coordinator('panel'),
            ]
        );

        $this->assertStringContainsString('Trayectoria Estudiantil', $html);
        $this->assertStringContainsString('Ana Restrepo', $html);
        $this->assertStringContainsString('Estudiantes', $html);
        $this->assertStringContainsString('CONTENIDO_DE_PRUEBA', $html);
    }

    public function test_guest_layout_renders_slot_content(): void
    {
        $html = Blade::render('<x-layouts.guest>CONTENIDO_LOGIN</x-layouts.guest>');

        $this->assertStringContainsString('CONTENIDO_LOGIN', $html);
        $this->assertStringContainsString('<html', $html);
    }
}
