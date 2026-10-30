<?php

namespace Tests\Feature;

use App\Enums\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->getJson('/notificaciones')->assertUnauthorized();
    }

    public function test_coordination_gets_pending_requests_and_experiences(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $response = $this->getJson('/notificaciones')->assertOk();

        $this->assertGreaterThan(0, $response->json('count'));
        $this->assertStringContainsString('sin decisión', $response->json('html'));
    }

    public function test_a_professor_gets_open_alerts_of_tutored_students_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeededTutor();

        $this->getJson('/notificaciones')->assertOk()->assertJsonPath('count', fn ($count) => $count > 0);
    }

    public function test_student_sees_the_state_of_their_own_requests_and_a_professor_without_tutored_students_gets_none(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->signInSeeded(Role::Estudiante);
        $this->getJson('/notificaciones')->assertOk()->assertJsonPath('count', 1);

        $this->signInSeeded(Role::Profesor);
        $this->getJson('/notificaciones')->assertOk()->assertJsonPath('count', 0)
            ->assertJsonPath('html', fn ($html) => str_contains($html, 'No tiene notificaciones pendientes.'));
    }

    public function test_topbar_exposes_working_menus_and_the_brand_logo(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $this->get('/perfil')
            ->assertSee('aria-label="Abrir menú de navegación"', false)
            ->assertSee('aria-label="Notificaciones"', false)
            ->assertSee('aria-label="Menú de usuario"', false)
            ->assertSee('Mi perfil')
            ->assertSee('href="/favicon.svg"', false);
    }
}
