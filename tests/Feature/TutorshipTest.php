<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use App\Models\Tutorship;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutorshipTest extends TestCase
{
    use RefreshDatabase;

    private Student $juan;

    private User $tutor;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 12:00:00');
        $this->seed(DatabaseSeeder::class);
        $this->juan = Student::where('codigo', '2020123')->firstOrFail();
        $this->tutor = $this->signInSeeded(Role::Tutor);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function valid(array $overrides = []): array
    {
        return $overrides + [
            'fecha' => '2026-10-12',
            'motivo' => 'Revisión de avance',
            'observaciones' => 'Se revisó el avance del semestre.',
            'compromisos' => 'Entregar el informe parcial.',
        ];
    }

    private function createTutorship(): Tutorship
    {
        return Tutorship::create([
            'student_id' => $this->juan->id, 'tutor_id' => $this->tutor->id, 'fecha' => '2026-10-12',
            'motivo' => 'Alerta temprana', 'observaciones' => 'Observación de prueba.',
        ]);
    }

    private function storeUrl(): string
    {
        return '/estudiantes/'.$this->juan->id.'/tutorias';
    }

    // ---- crear ----

    public function test_the_form_shows_the_student_history_and_the_immutability_notice(): void
    {
        $response = $this->get($this->storeUrl().'/crear');

        $response->assertOk();
        $response->assertSee('Registrar tutoría');
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Tutor asignado: Carlos Pineda');
        $response->assertSee('2026-2');
        $response->assertSee('Seguimiento a carga académica');
        $response->assertSee('Podrá corregir esta sesión durante 24 horas');
    }

    public function test_a_tutor_registers_a_tutorship_and_sees_it_in_the_profile(): void
    {
        $response = $this->post($this->storeUrl(), $this->valid());

        $response->assertRedirect(route('students.show', $this->juan));
        $response->assertSessionHas('status', 'Tutoría registrada correctamente.');
        $tutorship = Tutorship::where('observaciones', 'Se revisó el avance del semestre.')->firstOrFail();
        $this->assertSame($this->tutor->id, $tutorship->tutor_id);
        $this->assertSame($this->juan->id, $tutorship->student_id);
        $this->assertSame('Entregar el informe parcial.', $tutorship->compromisos);

        $this->get(route('students.show', $this->juan))->assertSee('Se revisó el avance del semestre.')->assertSee('Tutoría registrada correctamente.');
    }

    public function test_the_author_is_always_the_signed_in_user_even_if_another_is_sent(): void
    {
        $other = User::factory()->create(['role' => Role::Tutor]);

        $this->post($this->storeUrl(), $this->valid(['tutor_id' => $other->id]))->assertRedirect();

        $this->assertSame($this->tutor->id, Tutorship::latest('id')->firstOrFail()->tutor_id);
    }

    public function test_validation_messages_and_old_input_and_nothing_is_stored(): void
    {
        $before = Tutorship::count();

        $this->from($this->storeUrl().'/crear')->post($this->storeUrl(), ['fecha' => '2026-10-13', 'motivo' => 'Inventado', 'observaciones' => 'corta'])
            ->assertSessionHasErrors([
                'fecha' => 'La fecha de la sesión no puede ser posterior a hoy.',
                'motivo' => 'Seleccione un motivo de la lista.',
                'observaciones' => 'Describa lo tratado en la sesión (mínimo 10 caracteres).',
            ]);
        $this->post($this->storeUrl(), [])->assertSessionHasErrors(['fecha', 'motivo', 'observaciones']);
        $this->assertSame($before, Tutorship::count());

        $this->post($this->storeUrl(), ['fecha' => '2026-10-13', 'motivo' => 'Inventado', 'observaciones' => 'corta']);
        $this->get($this->storeUrl().'/crear')->assertSee('corta')->assertSee('La fecha de la sesión no puede ser posterior a hoy.');
    }

    public function test_commitments_are_optional(): void
    {
        $this->post($this->storeUrl(), $this->valid(['compromisos' => null]))->assertSessionHasNoErrors();
    }

    public function test_coordination_can_register_but_professors_and_students_cannot(): void
    {
        $coordinator = $this->signInSeeded(Role::Coordinacion);
        $this->post($this->storeUrl(), $this->valid())->assertRedirect();
        $this->assertSame($coordinator->id, Tutorship::latest('id')->firstOrFail()->tutor_id);

        $count = Tutorship::count();
        foreach ([Role::Profesor, Role::Estudiante] as $role) {
            $this->signInSeeded($role);
            $this->get($this->storeUrl().'/crear')->assertForbidden();
            $this->post($this->storeUrl(), $this->valid())->assertForbidden();
        }
        $this->assertSame($count, Tutorship::count());
    }

    public function test_unknown_and_non_numeric_students_return_404(): void
    {
        $this->get('/estudiantes/999999/tutorias/crear')->assertNotFound();
        $this->post('/estudiantes/999999/tutorias', $this->valid())->assertNotFound();
        $this->get('/estudiantes/abc/tutorias/crear')->assertNotFound();
    }

    // ---- editar / eliminar ----

    public function test_the_author_edits_up_to_the_last_minute_of_the_window(): void
    {
        $tutorship = $this->createTutorship();
        Carbon::setTestNow('2026-10-13 11:59:00');

        $this->get('/tutorias/'.$tutorship->id.'/editar')->assertOk()->assertSee('Observación de prueba.')->assertSee('Editar tutoría');
        $this->put('/tutorias/'.$tutorship->id, $this->valid(['observaciones' => 'Observación corregida a tiempo.']))
            ->assertRedirect(route('students.show', $this->juan))
            ->assertSessionHas('status', 'Tutoría actualizada correctamente.');

        $this->assertSame('Observación corregida a tiempo.', $tutorship->fresh()->observaciones);
        $this->assertSame($this->tutor->id, $tutorship->fresh()->tutor_id);
    }

    public function test_after_24_hours_the_tutorship_is_immutable(): void
    {
        $tutorship = $this->createTutorship();
        Carbon::setTestNow('2026-10-13 12:01:00');

        $this->get('/tutorias/'.$tutorship->id.'/editar')->assertForbidden();
        $this->put('/tutorias/'.$tutorship->id, $this->valid(['observaciones' => 'Intento tardío de corrección.']))->assertForbidden();
        $this->delete('/tutorias/'.$tutorship->id)->assertForbidden();

        $this->assertSame('Observación de prueba.', $tutorship->fresh()->observaciones);
        $this->assertNull($tutorship->fresh()->deleted_at);
    }

    public function test_another_tutor_or_coordination_cannot_touch_it(): void
    {
        $tutorship = $this->createTutorship();

        foreach ([User::factory()->create(['role' => Role::Tutor]), $this->signInSeeded(Role::Coordinacion)] as $user) {
            $this->actingAs($user);
            $this->get('/tutorias/'.$tutorship->id.'/editar')->assertForbidden();
            $this->put('/tutorias/'.$tutorship->id, $this->valid())->assertForbidden();
            $this->delete('/tutorias/'.$tutorship->id)->assertForbidden();
        }

        $this->assertNull($tutorship->fresh()->deleted_at);
    }

    public function test_invalid_edits_are_rejected_and_keep_the_stored_values(): void
    {
        $tutorship = $this->createTutorship();

        $this->from('/tutorias/'.$tutorship->id.'/editar')->put('/tutorias/'.$tutorship->id, $this->valid(['motivo' => 'Inventado']))
            ->assertSessionHasErrors('motivo');

        $this->assertSame('Alerta temprana', $tutorship->fresh()->motivo);
    }

    public function test_deleting_is_logical_and_the_tutorship_disappears_from_the_profile(): void
    {
        $tutorship = $this->createTutorship();

        $this->delete('/tutorias/'.$tutorship->id)->assertRedirect(route('students.show', $this->juan))->assertSessionHas('status', 'Tutoría eliminada.');

        $this->assertSame(1, Tutorship::withTrashed()->whereKey($tutorship->id)->count());
        $this->assertSame(0, Tutorship::whereKey($tutorship->id)->count());
        $this->get(route('students.show', $this->juan))->assertDontSee('Observación de prueba.');
        $this->get('/tutorias/'.$tutorship->id.'/editar')->assertNotFound();
        $this->get('/tutorias/999999/editar')->assertNotFound();
        $this->get('/tutorias/abc/editar')->assertNotFound();
    }

    // ---- ficha ----

    public function test_the_profile_offers_register_and_edit_only_to_those_who_can_use_them(): void
    {
        $create = 'href="'.route('tutorships.create', $this->juan).'"';
        $edit = 'href="'.route('tutorships.edit', Tutorship::where('student_id', $this->juan->id)->firstOrFail()).'"';

        $this->get(route('students.show', $this->juan))->assertSee($create, false)->assertSee($edit, false);

        $this->signInSeeded(Role::Coordinacion);
        $this->get(route('students.show', $this->juan))->assertSee($create, false)->assertDontSee($edit, false);

        $this->signInSeeded(Role::Profesor);
        $this->get(route('students.show', $this->juan))->assertDontSee($create, false)->assertDontSee($edit, false);

        $this->signInSeeded(Role::Tutor);
        Carbon::setTestNow('2026-10-13 12:01:00');
        $this->get(route('students.show', $this->juan))->assertSee($create, false)->assertDontSee($edit, false);
    }
}
