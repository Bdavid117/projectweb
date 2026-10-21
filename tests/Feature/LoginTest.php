<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Iniciar sesión');
        $response->assertSee('Una visión integral de la trayectoria de cada estudiante.');
    }

    public function test_valid_login_authenticates_the_user(): void
    {
        $this->user(Role::Coordinacion);

        $this->post('/login', ['email' => 'coordinacion@unal.edu.co', 'password' => 'password'])
            ->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_unknown_email_and_wrong_password_give_the_same_generic_error(): void
    {
        $this->user(Role::Tutor);

        $unknown = $this->from('/login')->post('/login', ['email' => 'nadie@unal.edu.co', 'password' => 'password']);
        $wrong = $this->from('/login')->post('/login', ['email' => 'tutor@unal.edu.co', 'password' => 'incorrecta']);

        $unknown->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $wrong->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->assertGuest();
    }

    public function test_empty_or_missing_fields_give_the_generic_error_instead_of_failing(): void
    {
        $this->from('/login')->post('/login', [])->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        $this->from('/login')->post('/login', ['email' => 'tutor@unal.edu.co'])->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
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

        $this->post('/login', ['email' => '  Coordinacion@UNAL.edu.co ', 'password' => 'password'])->assertRedirect();
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

    public function test_authenticated_users_leave_the_login_page(): void
    {
        $this->actingAsRole(Role::Tutor);

        $this->get('/login')->assertRedirect();
    }

    public function test_logout_ends_the_session(): void
    {
        $this->user(Role::Coordinacion);
        $this->post('/login', ['email' => 'coordinacion@unal.edu.co', 'password' => 'password']);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_post_without_csrf_token_returns_419(): void
    {
        // Laravel omite la verificación CSRF cuando el entorno es "testing".
        $this->app['env'] = 'production';

        $this->post('/login', ['email' => 'a@b.co', 'password' => 'x'])->assertStatus(419);
    }

    private function user(Role $role, array $attrs = []): User
    {
        return User::factory()->create($attrs + ['role' => $role, 'email' => $role->value.'@unal.edu.co', 'password' => 'password']);
    }
}