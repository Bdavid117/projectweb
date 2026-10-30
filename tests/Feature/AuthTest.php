<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function user(Role $role, array $attrs = []): User
    {
        return User::factory()->create($attrs + ['role' => $role, 'email' => $role->value.'@unal.edu.co', 'password' => 'password']);
    }

    public function test_valid_login_redirects_by_role(): void
    {
        $this->user(Role::Coordinacion);
        $this->post('/login', ['email' => 'coordinacion@unal.edu.co', 'password' => 'password'])->assertRedirect('/panel');
        $this->assertAuthenticated();

        $this->post('/logout');
        $this->user(Role::Estudiante);
        $this->post('/login', ['email' => 'estudiante@unal.edu.co', 'password' => 'password'])->assertRedirect('/solicitudes/nueva');
    }

    public function test_unknown_email_and_wrong_password_give_the_same_generic_error(): void
    {
        $this->user(Role::Profesor);

        $unknown = $this->from('/login')->post('/login', ['email' => 'nadie@unal.edu.co', 'password' => 'password']);
        $wrong = $this->from('/login')->post('/login', ['email' => 'profesor@unal.edu.co', 'password' => 'incorrecta']);

        $unknown->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $wrong->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->assertGuest();
    }

    public function test_empty_or_missing_fields_give_the_generic_error_instead_of_failing(): void
    {
        $this->from('/login')->post('/login', [])->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->from('/login')->post('/login', ['email' => 'profesor@unal.edu.co'])->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->from('/login')->post('/login', ['email' => '', 'password' => ''])->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->assertGuest();
    }

    public function test_array_inputs_give_the_generic_error_instead_of_a_server_error(): void
    {
        $this->from('/login')->post('/login', ['email' => ['a@b.co'], 'password' => 'x'])
            ->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->from('/login')->post('/login', ['email' => 'a@b.co', 'password' => ['x']])
            ->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $this->user(Role::Coordinacion);

        foreach (range(1, 5) as $_) {
            $this->post('/login', ['email' => 'coordinacion@unal.edu.co', 'password' => 'mala'])->assertStatus(302);
        }

        $this->post('/login', ['email' => 'coordinacion@unal.edu.co', 'password' => 'password'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_email_is_trimmed_and_case_insensitive(): void
    {
        $this->user(Role::Coordinacion);

        $this->post('/login', ['email' => '  Coordinacion@UNAL.edu.co ', 'password' => 'password'])->assertRedirect('/panel');
        $this->assertAuthenticated();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $this->user(Role::Profesor, ['active' => false]);

        $this->from('/login')->post('/login', ['email' => 'profesor@unal.edu.co', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->assertGuest();
    }

    public function test_failed_login_keeps_the_typed_email(): void
    {
        $this->followingRedirects()->from('/login')
            ->post('/login', ['email' => 'alguien@unal.edu.co', 'password' => 'x'])
            ->assertSee('alguien@unal.edu.co')
            ->assertSee('Credenciales inválidas.');
    }

    public function test_guests_are_redirected_to_login_and_authenticated_users_leave_login(): void
    {
        $this->get('/panel')->assertRedirect('/login');
        $this->get('/estudiantes')->assertRedirect('/login');
        $this->post('/logout')->assertRedirect('/login');

        $this->actingAsRole(Role::Profesor);
        $this->get('/login')->assertRedirect('/panel');
    }

    public function test_logout_ends_the_session(): void
    {
        $this->user(Role::Coordinacion);
        $this->post('/login', ['email' => 'coordinacion@unal.edu.co', 'password' => 'password']);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_role_matrix_returns_403_on_foreign_routes_and_200_on_own(): void
    {
        $this->seed(DatabaseSeeder::class);
        $juan = Student::where('codigo', '2020123')->firstOrFail();

        $matrix = [
            ['/solicitudes', [Role::Estudiante, Role::Profesor, Role::Coordinacion]],
            ['/estudiantes', [Role::Profesor, Role::Coordinacion]],
            // Un profesor sin vínculo aceptado no abre la ficha por URL; el vinculado sí (StudentScopeTest).
            ['/estudiantes/'.$juan->id, [Role::Coordinacion]],
            ['/solicitudes/nueva', [Role::Estudiante, Role::Coordinacion]],
            ['/mis-asesorias', [Role::Profesor]],
            ['/tutoria/solicitudes', [Role::Profesor]],
            ['/mi-tutor', [Role::Estudiante]],
            ['/indicadores', [Role::Coordinacion]],
        ];

        foreach ($matrix as [$url, $allowed]) {
            foreach (Role::cases() as $role) {
                $this->actingAsRole($role);
                in_array($role, $allowed, true)
                    ? $this->get($url)->assertOk()
                    : $this->get($url)->assertForbidden();
            }
        }
    }

    public function test_panel_is_open_to_every_role_and_students_get_their_own(): void
    {
        foreach ([Role::Profesor, Role::Coordinacion] as $role) {
            $this->actingAsRole($role);
            $this->get('/panel')->assertOk();
        }

        $this->actingAsRole(Role::Estudiante);
        $this->get('/panel')->assertOk()->assertSee('Mi panel');
    }

    public function test_login_post_without_csrf_token_returns_419(): void
    {
        // Laravel omite la verificación CSRF cuando el entorno es "testing".
        $this->app['env'] = 'production';

        $this->post('/login', ['email' => 'a@b.co', 'password' => 'x'])->assertStatus(419);
    }
}
