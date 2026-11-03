<?php

namespace Tests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * GET de una pantalla, pero solo con el contenido de <main>. La barra superior también nombra estudiantes (la campana
     * trae sus avisos en la página), así que un `assertDontSee` sobre un nombre debe mirar solo el cuerpo de la pantalla.
     */
    protected function main(string $uri): TestResponse
    {
        $response = $this->get($uri)->assertOk();
        preg_match('#<main\b[^>]*>(.*)</main>#s', $response->getContent(), $found);

        return TestResponse::fromBaseResponse(new Response($found[1] ?? $response->getContent()));
    }

    /**
     * RefreshDatabase ejecuta migrate:fresh: nunca debe correr contra la base de desarrollo
     * (por ejemplo con un config:cache previo). Se verifica antes de inicializar los traits
     * porque un método heredado como beforeRefreshingDatabase() lo sobrescribe el propio trait.
     */
    protected function setUpTraits()
    {
        $this->assertSafeTestDatabase();

        return parent::setUpTraits();
    }

    protected function assertSafeTestDatabase(): void
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.$connection.database");

        if (! str_ends_with($database, '_test')) {
            throw new \RuntimeException("Los tests destructivos solo pueden correr contra una base que termine en _test (actual: $database).");
        }
    }

    protected function signInSeeded(Role $role): User
    {
        $user = User::where('email', $role->value.'@unal.edu.co')->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    /** Carlos Pineda: el profesor sembrado que ya tiene tutorados (profesor@ no tiene ninguno). */
    protected function signInSeededTutor(): User
    {
        $user = User::where('email', 'profesor2@unal.edu.co')->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    protected function actingAsRole(Role $role, array $attributes = []): User
    {
        $user = User::factory()->make($attributes + ['id' => 1, 'role' => $role]);
        $this->actingAs($user);

        return $user;
    }
}
