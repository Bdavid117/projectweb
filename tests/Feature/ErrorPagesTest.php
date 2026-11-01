<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    /** Textos por defecto de Laravel que no deben aparecer (RNF-20: interfaz en español). */
    private const ENGLISH_DEFAULTS = [
        'Forbidden', 'Not Found', 'Page Expired', 'Server Error',
        'Too Many Requests', 'Service Unavailable', 'Whoops',
    ];

    private function render(string $code, array $data = []): string
    {
        return view("errors.$code", $data + ['exception' => null])->render();
    }

    private function assertSpanishAndNoEnglish(string $html, string $expectedPhrase): void
    {
        $this->assertStringContainsString($expectedPhrase, $html);

        foreach (self::ENGLISH_DEFAULTS as $english) {
            $this->assertStringNotContainsString($english, $html, "La página de error no debe contener «{$english}».");
        }
    }

    public function test_every_error_page_renders_in_spanish_without_english_defaults(): void
    {
        $expected = [
            '403' => 'No tiene acceso a esta información',
            '404' => 'No encontramos esta página',
            '419' => 'La sesión expiró',
            '429' => 'Demasiados intentos',
            '500' => 'Ocurrió un error inesperado',
            '503' => 'Sistema en mantenimiento',
        ];

        foreach ($expected as $code => $phrase) {
            $data = $code === '429' ? ['exception' => new TooManyRequestsHttpException(30)] : [];
            $this->assertSpanishAndNoEnglish($this->render($code, $data), $phrase);
        }
    }

    public function test_pages_offer_a_clear_way_out_for_guests_and_signed_in_users(): void
    {
        $guest = $this->render('404');
        $this->assertStringContainsString(route('login.show'), $guest);

        $this->actingAsRole(Role::Coordinacion);
        $signedIn = $this->render('404');
        $this->assertStringContainsString('Volver al panel', $signedIn);
        $this->assertStringContainsString('Salir', $signedIn);
    }

    public function test_403_shows_the_tutoring_hint_only_to_professors(): void
    {
        $this->assertStringNotContainsString('Solicitar tutoría', $this->render('403'));

        $this->actingAsRole(Role::Estudiante);
        $this->assertStringNotContainsString('Solicitar tutoría', $this->render('403'));

        $this->actingAsRole(Role::Profesor);
        $professor = $this->render('403');
        $this->assertStringContainsString('Solicitar tutoría', $professor);
        $this->assertStringContainsString(route('tutor-requests.index'), $professor);
    }

    public function test_429_states_how_long_to_wait(): void
    {
        $this->assertStringContainsString('30 segundos', $this->render('429', ['exception' => new TooManyRequestsHttpException(30)]));
        $this->assertStringContainsString('1 segundo', $this->render('429', ['exception' => new TooManyRequestsHttpException(1)]));
        // Sin encabezado Retry-After cae al texto genérico.
        $this->assertStringContainsString('Espere un minuto', $this->render('429'));
    }

    public function test_unknown_urls_render_the_spanish_404(): void
    {
        $this->actingAsRole(Role::Coordinacion);

        $response = $this->get('/esta-ruta-no-existe');

        $response->assertStatus(404);
        $response->assertSee('No encontramos esta página');
        $response->assertDontSee('Not Found');
    }

    public function test_the_login_throttle_renders_the_spanish_429(): void
    {
        User::factory()->create(['email' => 'coordinacion@unal.edu.co', 'role' => Role::Coordinacion, 'active' => true]);

        foreach (range(1, 5) as $_) {
            $this->post('/login', ['email' => 'coordinacion@unal.edu.co', 'password' => 'mala'])->assertStatus(302);
        }

        $response = $this->post('/login', ['email' => 'coordinacion@unal.edu.co', 'password' => 'password']);

        $response->assertStatus(429);
        $response->assertSee('Demasiados intentos');
        $response->assertDontSee('Too Many Requests');
        $this->assertGuest();
    }

    public function test_a_professor_without_a_link_gets_the_spanish_403_with_the_hint(): void
    {
        $this->seed(DatabaseSeeder::class);
        $maria = User::where('email', 'profesor@unal.edu.co')->firstOrFail();
        $ruiz = Student::where('codigo', '2019087')->firstOrFail();

        $response = $this->actingAs($maria)->get(route('students.show', $ruiz));

        $response->assertStatus(403);
        $response->assertSee('No tiene acceso a esta información');
        $response->assertSee('Solicitar tutoría');
        $response->assertDontSee('Forbidden');
        // No se revelan datos del estudiante.
        $response->assertDontSee('102019087');
    }

    public function test_a_non_professor_gets_the_403_without_the_hint(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $response = $this->get('/estudiantes');

        $response->assertStatus(403);
        $response->assertSee('No tiene acceso a esta información');
        $response->assertDontSee('Solicitar tutoría');
    }
}
