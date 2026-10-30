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

    public function test_profesor_does_not_see_coordination_only_links(): void
    {
        $items = $this->items(Role::Profesor);
        $this->assertArrayNotHasKey('indicadores', $items);
        $this->assertArrayNotHasKey('usuarios', $items);
        $this->assertArrayNotHasKey('bitacora', $items);
        $this->assertSame('/solicitudes', $items['solicitudes']['href']);
    }

    public function test_the_profesor_gets_the_tutor_group_as_an_extra_menu_section_and_nobody_else_does(): void
    {
        $groups = collect(Navigation::forRole(Role::Profesor, 'panel'));
        $tutoring = $groups->firstWhere('label', 'TUTORÍA');

        $this->assertNotNull($tutoring);
        $this->assertSame(['mis-asesorias', 'solicitar-tutoria'], collect($tutoring['items'])->pluck('key')->all());
        $this->assertSame('/mis-asesorias', $this->items(Role::Profesor)['mis-asesorias']['href']);
        $this->assertSame('/tutoria/solicitudes', $this->items(Role::Profesor)['solicitar-tutoria']['href']);
        // «Registrar tutoría» apuntaba a la misma página que «Mis asesorías»: ahora se registra desde cada estudiante vinculado.
        $this->assertArrayNotHasKey('registrar-tutoria', $this->items(Role::Profesor));

        foreach ([Role::Coordinacion, Role::Estudiante] as $role) {
            $this->assertArrayNotHasKey('mis-asesorias', $this->items($role));
            $this->assertArrayNotHasKey('solicitar-tutoria', $this->items($role));
        }
    }

    public function test_the_student_gets_my_tutor_to_decide_who_sees_their_information(): void
    {
        $this->assertSame('/mi-tutor', $this->items(Role::Estudiante)['mi-tutor']['href']);
        $this->assertArrayNotHasKey('mi-tutor', $this->items(Role::Profesor));
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
        $items = collect(Navigation::forRole(Role::Profesor, 'mis-asesorias'))->pluck('items')->flatten(1)->keyBy('key');

        $this->assertTrue($items['mis-asesorias']['active']);
        $this->assertFalse($items['panel']['active']);
    }

    public function test_no_role_gets_a_dead_link(): void
    {
        foreach (Role::cases() as $role) {
            foreach ($this->items($role) as $item) {
                $this->assertNotSame('#', $item['href'], $role->value.' → '.$item['key']);
            }
        }
    }
}
