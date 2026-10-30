<?php

namespace Tests\Feature;

use App\Models\Student;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_application_uses_the_colombian_timezone_for_dates(): void
    {
        // 02:00 UTC del 13/10 = 21:00 del 12/10 en Bogotá.
        Carbon::setTestNow(Carbon::parse('2026-10-13 02:00:00', 'UTC'));
        $this->seed(DatabaseSeeder::class);
        $this->signInSeededTutor();
        $juan = Student::where('codigo', '2020123')->firstOrFail();
        $payload = ['motivo' => 'Revisión de avance', 'observaciones' => 'Sesión de seguimiento del semestre.'];

        $this->assertSame('America/Bogota', config('app.timezone'));
        $this->get('/estudiantes/'.$juan->id.'/tutorias/crear')->assertSee('value="2026-10-12"', false);
        $this->post('/estudiantes/'.$juan->id.'/tutorias', $payload + ['fecha' => '2026-10-13'])
            ->assertSessionHasErrors(['fecha' => 'La fecha de la sesión no puede ser posterior a hoy.']);
        $this->post('/estudiantes/'.$juan->id.'/tutorias', $payload + ['fecha' => '2026-10-12'])->assertSessionHasNoErrors();
    }
}
