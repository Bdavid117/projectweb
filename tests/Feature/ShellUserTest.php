<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Support\Navigation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShellUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_topbar_shows_the_authenticated_user_and_role(): void
    {
        $this->actingAsRole(Role::Coordinacion, ['name' => 'Laura Mejía']);

        $this->get('/panel')->assertSee('Laura Mejía')->assertSee('Coordinación')->assertSee('Cerrar sesión', false);
    }

    public function test_every_screen_uses_the_session_user_not_the_mock_one(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAsRole(Role::Profesor, ['name' => 'Tomás Gil']);
        $this->get('/estudiantes')->assertSee('Tomás Gil')->assertDontSee('Ana Restrepo');
        $this->get('/mis-asesorias')->assertSee('Tomás Gil')->assertDontSee('Carlos Pineda');

        $this->actingAsRole(Role::Coordinacion, ['name' => 'Laura Mejía']);
        $this->get('/indicadores')->assertSee('Laura Mejía')->assertDontSee('Ana Restrepo');

        $this->actingAsRole(Role::Estudiante, ['name' => 'Sara Díaz']);
        $this->get('/solicitudes/nueva')->assertSee('Sara Díaz')->assertDontSee('Juan Pérez');
    }

    public function test_menu_depends_on_the_role(): void
    {
        $this->actingAsRole(Role::Estudiante);
        $this->get('/solicitudes')->assertSee('Mis solicitudes')->assertDontSee('Indicadores');

        $this->actingAsRole(Role::Profesor);
        $this->get('/estudiantes')->assertSee('TUTORÍA')->assertSee('Mis asesorías')->assertSee('Solicitar tutoría')->assertDontSee('Registrar tutoría');

        $this->actingAsRole(Role::Estudiante);
        $this->get('/solicitudes')->assertSee('Mi tutor');

        $this->actingAsRole(Role::Coordinacion);
        $this->get('/panel')->assertSee('Indicadores')->assertSee('Usuarios y roles')->assertDontSee('Mis asesorías');
    }

    public function test_for_role_picks_the_matching_navigation(): void
    {
        $labels = fn (array $groups) => collect($groups)->pluck('items')->flatten(1)->pluck('label')->all();

        $this->assertContains('Mi práctica', $labels(Navigation::forRole(Role::Estudiante, 'panel')));
        $this->assertContains('Mi tutor', $labels(Navigation::forRole(Role::Estudiante, 'panel')));
        $this->assertContains('Mis asesorías', $labels(Navigation::forRole(Role::Profesor, 'panel')));
        $this->assertContains('Solicitar tutoría', $labels(Navigation::forRole(Role::Profesor, 'panel')));
        $this->assertNotContains('Mis asesorías', $labels(Navigation::forRole(Role::Coordinacion, 'panel')));
    }
}
