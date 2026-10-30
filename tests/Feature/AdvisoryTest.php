<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvisoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 12:00:00');
        $this->seed(DatabaseSeeder::class);
        $this->signInSeededTutor();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_professor_sees_only_the_students_who_accepted_them(): void
    {
        $response = $this->get('/mis-asesorias');

        $response->assertOk();
        $response->assertSee('Mis asesorías');
        $response->assertSee('5 estudiantes asignados en 2026-2S');
        foreach (['Pérez Rojas, Juan Sebastián', 'Gómez Ruiz, Laura', 'Cardona Vélez, Mateo', 'Martínez Cruz, Diana', 'Ospina Lara, Sofía'] as $name) {
            $response->assertSee($name);
        }
        foreach (['Ruiz Salazar, Andrés', 'Herrera Niño, Camilo', 'Valencia Toro, Ana'] as $name) {
            $response->assertDontSee($name);
        }
        $response->assertSee('Mostrando 1–5 de 5 estudiantes asignados');
    }

    public function test_each_row_shows_the_last_tutorship_and_an_alert_summary(): void
    {
        $response = $this->get('/mis-asesorias');

        $response->assertSee('12/09/2026');
        $response->assertSee('03/07/2026');
        $response->assertSee('1 alerta abierta');
        $response->assertSee('Sin tutoría reciente');
        $response->assertSee('Sin alertas');
        $response->assertSee('font-semibold text-[var(--color-danger)]', false);
    }

    public function test_a_deleted_tutorship_no_longer_counts_as_the_last_one(): void
    {
        Student::where('codigo', '2020123')->firstOrFail()->tutorships()->where('fecha', '2026-09-12')->firstOrFail()->delete();

        $this->get('/mis-asesorias')->assertDontSee('12/09/2026')->assertSee('03/05/2026');
    }

    public function test_filters_by_alerts_and_search(): void
    {
        $this->get('/mis-asesorias?alertas=con')->assertSee('Mostrando 1–2 de 2')->assertSee('Pérez Rojas, Juan Sebastián')->assertSee('Gómez Ruiz, Laura')->assertDontSee('Cardona Vélez, Mateo');
        $this->get('/mis-asesorias?alertas=sin')->assertSee('Mostrando 1–3 de 3')->assertSee('Cardona Vélez, Mateo')->assertDontSee('Gómez Ruiz, Laura');
        $this->get('/mis-asesorias?q=cardona')->assertSee('Cardona Vélez, Mateo')->assertDontSee('Gómez Ruiz, Laura');
        $this->get('/mis-asesorias?q=%25')->assertSee('No hay estudiantes');
        $this->get('/mis-asesorias?q[]=x&alertas[]=y')->assertOk();
    }

    public function test_rows_link_to_the_profile_and_to_the_register_form(): void
    {
        $juan = Student::where('codigo', '2020123')->firstOrFail();

        $response = $this->get('/mis-asesorias');

        $response->assertSee('href="'.route('students.show', $juan).'"', false);
        $response->assertSee('href="'.route('tutorships.create', $juan).'"', false);
    }

    public function test_a_professor_without_tutored_students_sees_the_empty_state(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Profesor]));

        $this->get('/mis-asesorias')->assertOk()->assertSee('0 estudiantes asignados')->assertSee('No hay estudiantes que coincidan');
    }

    public function test_only_professors_can_open_it(): void
    {
        foreach ([Role::Coordinacion, Role::Estudiante] as $role) {
            $this->signInSeeded($role);
            $this->get('/mis-asesorias')->assertForbidden();
        }

        $this->signInSeeded(Role::Profesor);
        $this->get('/mis-asesorias')->assertOk()->assertSee('0 estudiantes asignados');
    }
}
