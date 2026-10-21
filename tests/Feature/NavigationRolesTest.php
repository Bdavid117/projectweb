<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Support\Navigation;
use Tests\TestCase;

class NavigationRolesTest extends TestCase
{
    private function items(Role $role): array
    {
        return collect(Navigation::forRole($role, 'panel'))->pluck('items')->flatten(1)->keyBy('key')->all();
    }

    public function test_tutor_and_profesor_do_not_see_coordination_only_links(): void
    {
        foreach ([Role::Tutor, Role::Profesor] as $role) {
            $items = $this->items($role);
            $this->assertArrayNotHasKey('indicadores', $items);
            $this->assertArrayNotHasKey('usuarios', $items);
            $this->assertArrayNotHasKey('bitacora', $items);
            $this->assertSame('/solicitudes', $items['solicitudes']['href']);
        }
    }

    public function test_only_tutor_gets_the_tutor_extras(): void
    {
        $this->assertSame('/mis-asesorias', $this->items(Role::Tutor)['mis-asesorias']['href']);
        $this->assertSame('/mis-asesorias', $this->items(Role::Tutor)['registrar-tutoria']['href']);
        $this->assertArrayNotHasKey('mis-asesorias', $this->items(Role::Profesor));
        $this->assertArrayNotHasKey('mis-asesorias', $this->items(Role::Coordinacion));
    }

    public function test_coordinacion_keeps_the_full_menu_and_student_keeps_the_portal(): void
    {
        $coord = $this->items(Role::Coordinacion);
        $this->assertSame('/indicadores', $coord['indicadores']['href']);
        $this->assertArrayHasKey('usuarios', $coord);
        $this->assertSame('/solicitudes', $coord['solicitudes']['href']);

        $student = $this->items(Role::Estudiante);
        $this->assertSame('/solicitudes', $student['solicitudes']['href']);
        $this->assertArrayHasKey('practica', $student);
        $this->assertArrayNotHasKey('indicadores', $student);
        foreach (['usuarios', 'cargas', 'catalogos', 'bitacora'] as $admin) {
            $this->assertArrayNotHasKey($admin, $student);
        }
    }

    public function test_active_item_is_marked(): void
    {
        $items = collect(Navigation::forRole(Role::Tutor, 'mis-asesorias'))->pluck('items')->flatten(1)->keyBy('key');

        $this->assertTrue($items['mis-asesorias']['active']);
        $this->assertFalse($items['panel']['active']);
    }
}
