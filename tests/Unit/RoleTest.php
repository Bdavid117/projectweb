<?php

namespace Tests\Unit;

use App\Enums\Role;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function test_labels_and_home_routes(): void
    {
        $this->assertSame('Coordinación', Role::Coordinacion->label());
        $this->assertSame('Estudiante', Role::Estudiante->label());
        $this->assertSame('requests.create', Role::Estudiante->homeRoute());
        $this->assertSame('panel.index', Role::Profesor->homeRoute());
        $this->assertSame('panel.index', Role::Coordinacion->homeRoute());
    }

    public function test_tutor_is_not_a_role_it_is_a_function_of_the_profesor(): void
    {
        $this->assertSame(['estudiante', 'profesor', 'coordinacion'], array_map(fn (Role $r) => $r->value, Role::cases()));
        $this->assertNull(Role::tryFrom('tutor'));
    }
}
