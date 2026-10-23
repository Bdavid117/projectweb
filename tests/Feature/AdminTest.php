<?php

namespace Tests\Feature;

use App\Enums\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinacion_opens_every_admin_screen(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/admin')->assertRedirect('/admin/usuarios');
        $this->get('/admin/usuarios')->assertOk()->assertSee('Usuarios y roles')->assertSee('coordinacion@unal.edu.co');
        $this->get('/admin/cargas')->assertOk()->assertSee('Aún no se ha importado ningún archivo.');
        $this->get('/admin/catalogos')->assertOk()->assertSee('Periodos académicos')->assertSee('Tipos de solicitud');
        $this->get('/admin/bitacora')->assertOk()->assertSee('Bitácora');
    }

    public function test_users_can_be_filtered_by_role_and_text(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/admin/usuarios?rol=tutor')->assertOk()->assertSee('tutor@unal.edu.co')->assertDontSee('estudiante@unal.edu.co');
        $this->get('/admin/usuarios?q[]=x&rol[]=y')->assertOk();
    }

    public function test_other_roles_cannot_open_admin_screens(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([Role::Estudiante, Role::Profesor, Role::Tutor] as $role) {
            $this->signInSeeded($role);
            foreach (['usuarios', 'cargas', 'catalogos', 'bitacora'] as $screen) {
                $this->get('/admin/'.$screen)->assertForbidden();
            }
        }
    }
}
