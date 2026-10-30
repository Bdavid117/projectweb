<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use App\Models\TutorAssignment;
use App\Models\TutorRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

/** Consentimiento: el profesor solicita ser tutor, el estudiante acepta o rechaza y puede dejar de compartir. */
class TutorLinkTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private User $maria;

    private User $carlos;

    private Student $ruiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->maria = User::where('email', 'profesor@unal.edu.co')->firstOrFail();
        $this->carlos = User::where('email', 'profesor2@unal.edu.co')->firstOrFail();
        // Ruiz Salazar no tiene tutor en el periodo vigente.
        $this->ruiz = Student::where('codigo', '2019087')->firstOrFail();
    }

    private function send(User $professor, Student $student, ?string $message = null): TutorRequest
    {
        $this->actingAs($professor)->post(route('tutor-requests.store'), ['student_id' => $student->id, 'mensaje' => $message])->assertSessionHasNoErrors();

        return TutorRequest::where('tutor_id', $professor->id)->where('student_id', $student->id)->latest('id')->firstOrFail();
    }

    // ---- profesor: buscar y enviar ----

    public function test_the_search_needs_three_letters_and_only_shows_name_code_and_cohort(): void
    {
        $this->actingAs($this->maria);

        $this->get(route('tutor-requests.index', ['q' => 'ru']))->assertOk()->assertSee('Escriba al menos 3 letras')->assertDontSee('Ruiz Salazar, Andrés');

        $response = $this->get(route('tutor-requests.index', ['q' => 'ruiz sal']))->assertOk();
        $response->assertSee('Ruiz Salazar, Andrés')->assertSee('Código 2019087')->assertSee('Cohorte 2019-2');
        // Nada más del estudiante: ni documento, ni PAPA, ni correo.
        $response->assertDontSee('102019087')->assertDontSee('est2019087@unal.edu.co')->assertDontSee('PAPA');

        $this->get(route('tutor-requests.index', ['q' => 'zzzzzz']))->assertSee('No se encontró ningún estudiante');
        $this->get(route('tutor-requests.index', ['q' => ['x']]))->assertOk();
    }

    public function test_the_search_marks_students_already_requested_or_already_tutored(): void
    {
        $this->send($this->maria, $this->ruiz);

        $this->actingAs($this->maria)->get(route('tutor-requests.index', ['q' => 'Ruiz Salazar']))->assertSee('Solicitud pendiente');
        $this->actingAs($this->carlos)->get(route('tutor-requests.index', ['q' => 'Gómez Ruiz']))->assertSee('Ya la acompaña');
    }

    public function test_sending_creates_a_pending_request_without_giving_access_and_leaves_an_audit_trail(): void
    {
        $request = $this->send($this->maria, $this->ruiz, 'Soy su profesora de Bases de Datos.');

        $this->assertSame(TutorRequest::PENDING, $request->estado);
        $this->assertSame('Soy su profesora de Bases de Datos.', $request->mensaje);
        $this->assertNull($request->respondida_en);
        $this->assertSame(0, TutorAssignment::where('student_id', $this->ruiz->id)->count());
        $this->actingAs($this->maria)->get(route('students.show', $this->ruiz))->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['accion' => 'tutoria.solicitud_enviada', 'user_id' => $this->maria->id, 'auditable_id' => $request->id]);
    }

    public function test_one_pending_request_per_pair_and_none_for_a_student_already_tutored(): void
    {
        $this->send($this->maria, $this->ruiz);

        $this->actingAs($this->maria)->post(route('tutor-requests.store'), ['student_id' => $this->ruiz->id])
            ->assertSessionHasErrors(['student_id' => 'Ya tiene una solicitud pendiente para este estudiante.']);
        $this->assertSame(1, TutorRequest::where('student_id', $this->ruiz->id)->count());

        $juan = Student::where('codigo', '2020123')->firstOrFail();
        $this->actingAs($this->carlos)->post(route('tutor-requests.store'), ['student_id' => $juan->id])
            ->assertSessionHasErrors(['student_id' => 'Ya acompaña a este estudiante en el periodo vigente.']);
    }

    public function test_the_database_itself_refuses_two_pending_requests_for_the_same_pair(): void
    {
        $this->send($this->maria, $this->ruiz);

        $this->expectException(UniqueConstraintViolationException::class);
        TutorRequest::create(['student_id' => $this->ruiz->id, 'tutor_id' => $this->maria->id, 'estado' => TutorRequest::PENDING]);
    }

    public function test_sending_validates_in_spanish_and_never_fails_with_odd_input(): void
    {
        $this->actingAs($this->maria);

        $this->post(route('tutor-requests.store'), [])->assertSessionHasErrors(['student_id' => 'Elija al estudiante al que desea acompañar.']);
        $this->post(route('tutor-requests.store'), ['student_id' => 999999])->assertSessionHasErrors(['student_id' => 'El estudiante elegido no existe.']);
        $this->post(route('tutor-requests.store'), ['student_id' => 'abc'])->assertSessionHasErrors(['student_id' => 'El estudiante elegido no es válido.']);
        $this->post(route('tutor-requests.store'), ['student_id' => [1]])->assertSessionHasErrors('student_id');
        $this->post(route('tutor-requests.store'), ['student_id' => $this->ruiz->id, 'mensaje' => str_repeat('a', 501)])
            ->assertSessionHasErrors(['mensaje' => 'El mensaje no puede superar los 500 caracteres.']);
        $this->post(route('tutor-requests.store'), ['student_id' => $this->ruiz->id, 'mensaje' => ['x']])->assertSessionHasErrors('mensaje');
        $this->assertSame(0, TutorRequest::where('tutor_id', $this->maria->id)->count());
    }

    public function test_the_professor_can_withdraw_a_pending_request_and_the_student_can_no_longer_answer_it(): void
    {
        $request = $this->send($this->maria, $this->ruiz);

        $this->actingAs($this->maria)->delete(route('tutor-requests.destroy', $request))->assertRedirect(route('tutor-requests.index'));
        $this->assertSame(TutorRequest::CANCELLED, $request->fresh()->estado);

        $this->actingAs($this->ruiz->user)->post(route('my-tutor.accept', $request))->assertForbidden();
        $this->assertSame(0, TutorAssignment::where('student_id', $this->ruiz->id)->count());
    }

    // ---- estudiante: aceptar, rechazar, dejar de compartir ----

    public function test_accepting_creates_the_link_and_the_professor_gains_access_everywhere(): void
    {
        $request = $this->send($this->maria, $this->ruiz);
        $this->actingAs($this->maria)->get(route('students.show', $this->ruiz))->assertForbidden();

        $this->actingAs($this->ruiz->user)->post(route('my-tutor.accept', $request))->assertRedirect(route('my-tutor.show'));

        $this->assertSame(TutorRequest::ACCEPTED, $request->fresh()->estado);
        $this->assertNotNull($request->fresh()->respondida_en);
        $this->assertSame(1, TutorAssignment::inCurrentTerm()->where('tutor_id', $this->maria->id)->where('student_id', $this->ruiz->id)->count());
        $this->assertDatabaseHas('audit_logs', ['accion' => 'tutoria.vinculo_creado', 'user_id' => $this->ruiz->user_id]);
        $this->assertDatabaseHas('audit_logs', ['accion' => 'tutoria.solicitud_aceptada', 'user_id' => $this->ruiz->user_id, 'auditable_id' => $request->id]);

        $this->actingAs($this->maria);
        $this->get(route('students.show', $this->ruiz))->assertOk()->assertSee('Ruiz Salazar, Andrés');
        $this->get('/estudiantes')->assertSee('Ruiz Salazar, Andrés');
        $this->get('/mis-asesorias')->assertSee('Ruiz Salazar, Andrés')->assertSee('1 estudiantes asignados');
        $this->get(route('tutorships.create', $this->ruiz))->assertOk();
    }

    public function test_rejecting_shares_nothing(): void
    {
        $request = $this->send($this->maria, $this->ruiz);

        $this->actingAs($this->ruiz->user)->post(route('my-tutor.reject', $request))->assertRedirect(route('my-tutor.show'));

        $this->assertSame(TutorRequest::REJECTED, $request->fresh()->estado);
        $this->assertSame(0, TutorAssignment::where('student_id', $this->ruiz->id)->count());
        $this->actingAs($this->maria)->get(route('students.show', $this->ruiz))->assertForbidden();
        $this->actingAs($this->ruiz->user)->get(route('my-tutor.show'))->assertSee('Rechazada');
    }

    public function test_a_student_who_already_has_a_tutor_cannot_accept_another_one(): void
    {
        $juan = Student::where('codigo', '2020123')->firstOrFail();
        $request = $this->send($this->maria, $juan);

        $this->actingAs($juan->user)->post(route('my-tutor.accept', $request))
            ->assertRedirect(route('my-tutor.show'))
            ->assertSessionHasErrors(['tutor' => 'Ya comparte su información con Carlos Pineda en este periodo. Deje de compartir con esa persona antes de aceptar otra solicitud.']);

        $this->assertSame(TutorRequest::PENDING, $request->fresh()->estado);
        $this->assertSame(1, TutorAssignment::where('student_id', $juan->id)->count());
        $this->actingAs($this->maria)->get(route('students.show', $juan))->assertForbidden();
    }

    public function test_revoking_removes_the_access_at_once_including_the_authors_own_tutorships(): void
    {
        $juan = Student::where('codigo', '2020123')->firstOrFail();
        $tutorship = $juan->tutorships()->create(['tutor_id' => $this->carlos->id, 'fecha' => now()->toDateString(), 'motivo' => 'Revisión de avance', 'observaciones' => 'Observación de prueba.']);
        $this->actingAs($this->carlos)->get(route('tutorships.edit', $tutorship))->assertOk();
        $this->actingAs($this->carlos)->get(route('students.show', $juan))->assertOk();

        $this->actingAs($juan->user)->delete(route('my-tutor.revoke'))->assertRedirect(route('my-tutor.show'));

        $this->assertSame(0, TutorAssignment::where('student_id', $juan->id)->count());
        $this->assertSame(0, TutorRequest::where('student_id', $juan->id)->where('estado', TutorRequest::ACCEPTED)->count());
        $this->assertSame(1, TutorRequest::where('student_id', $juan->id)->where('estado', TutorRequest::REVOKED)->count());
        $this->assertDatabaseHas('audit_logs', ['accion' => 'tutoria.vinculo_revocado', 'user_id' => $juan->user_id]);

        $this->actingAs($this->carlos);
        $this->get(route('students.show', $juan))->assertForbidden();
        $this->get(route('tutorships.edit', $tutorship))->assertForbidden();
        $this->put(route('tutorships.update', $tutorship), ['fecha' => now()->toDateString(), 'motivo' => 'Revisión de avance', 'observaciones' => 'Cambio posterior a revocar.'])->assertForbidden();
        $this->delete(route('tutorships.destroy', $tutorship))->assertForbidden();
        $this->get(route('tutorships.create', $juan))->assertForbidden();
        $this->get('/mis-asesorias')->assertDontSee('Pérez Rojas, Juan Sebastián');
        $this->assertNull($tutorship->fresh()->deleted_at);
    }

    public function test_revoking_without_a_tutor_gives_a_message_not_an_error(): void
    {
        $this->actingAs($this->ruiz->user)->delete(route('my-tutor.revoke'))
            ->assertRedirect(route('my-tutor.show'))
            ->assertSessionHasErrors(['tutor' => 'No hay una tutoría vigente que dejar de compartir.']);
    }

    public function test_after_revoking_the_same_professor_can_ask_again(): void
    {
        $juan = Student::where('codigo', '2020123')->firstOrFail();
        $this->actingAs($juan->user)->delete(route('my-tutor.revoke'));

        $again = $this->send($this->carlos, $juan);
        $this->actingAs($juan->user)->post(route('my-tutor.accept', $again))->assertSessionHasNoErrors();

        $this->actingAs($this->carlos)->get(route('students.show', $juan))->assertOk();
    }

    // ---- autorización sobre recursos ajenos ----

    public function test_nobody_but_the_addressee_answers_and_a_request_is_answered_only_once(): void
    {
        $request = $this->send($this->maria, $this->ruiz);
        $laura = Student::where('codigo', '2021045')->firstOrFail();

        // Otro estudiante, otro profesor, la propia autora y Coordinación no pueden responder.
        foreach ([$laura->user, $this->carlos, $this->maria, User::where('email', 'coordinacion@unal.edu.co')->firstOrFail()] as $user) {
            $this->actingAs($user)->post(route('my-tutor.accept', $request))->assertForbidden();
            $this->actingAs($user)->post(route('my-tutor.reject', $request))->assertForbidden();
        }
        $this->assertSame(TutorRequest::PENDING, $request->fresh()->estado);

        $this->actingAs($this->ruiz->user)->post(route('my-tutor.reject', $request))->assertSessionHasNoErrors();
        $this->actingAs($this->ruiz->user)->post(route('my-tutor.accept', $request))->assertForbidden();
        $this->assertSame(TutorRequest::REJECTED, $request->fresh()->estado);
        $this->assertSame(0, TutorAssignment::where('student_id', $this->ruiz->id)->count());
    }

    public function test_only_the_author_withdraws_and_only_while_pending(): void
    {
        $request = $this->send($this->maria, $this->ruiz);

        foreach ([$this->carlos, $this->ruiz->user] as $user) {
            $this->actingAs($user)->delete(route('tutor-requests.destroy', $request))->assertForbidden();
        }
        $this->assertSame(TutorRequest::PENDING, $request->fresh()->estado);

        $this->actingAs($this->ruiz->user)->post(route('my-tutor.reject', $request));
        $this->actingAs($this->maria)->delete(route('tutor-requests.destroy', $request))->assertForbidden();
        $this->assertSame(TutorRequest::REJECTED, $request->fresh()->estado);
    }

    public function test_each_side_only_reaches_its_own_screens(): void
    {
        $request = $this->send($this->maria, $this->ruiz);
        $coordinator = User::where('email', 'coordinacion@unal.edu.co')->firstOrFail();

        $this->actingAs($this->ruiz->user)->get(route('tutor-requests.index'))->assertForbidden();
        $this->actingAs($this->ruiz->user)->post(route('tutor-requests.store'), ['student_id' => $this->ruiz->id])->assertForbidden();
        $this->actingAs($this->maria)->get(route('my-tutor.show'))->assertForbidden();
        $this->actingAs($this->maria)->delete(route('my-tutor.revoke'))->assertForbidden();
        $this->actingAs($coordinator)->get(route('tutor-requests.index'))->assertForbidden();
        $this->actingAs($coordinator)->get(route('my-tutor.show'))->assertForbidden();
        $this->assertSame(TutorRequest::PENDING, $request->fresh()->estado);
    }

    public function test_the_professor_never_sees_requests_sent_by_another_professor(): void
    {
        $this->send($this->maria, $this->ruiz);

        $this->actingAs($this->carlos)->get(route('tutor-requests.index'))->assertDontSee('Ruiz Salazar, Andrés');
        $this->actingAs($this->maria)->get(route('tutor-requests.index'))->assertSee('Ruiz Salazar, Andrés')->assertSee('Pendiente');
    }

    // ---- pantalla del estudiante ----

    public function test_my_tutor_shows_pending_requests_the_current_tutor_and_the_history(): void
    {
        $this->send($this->maria, $this->ruiz, 'Quiero acompañarle este semestre.');

        $this->actingAs($this->ruiz->user)->get(route('my-tutor.show'))->assertOk()
            ->assertSee('María Londoño')->assertSee('Quiero acompañarle este semestre.')->assertSee('Aceptar')->assertSee('Rechazar')
            ->assertSee('No comparte su información con ningún tutor');

        $juan = Student::where('codigo', '2020123')->firstOrFail();
        $this->actingAs($juan->user)->get(route('my-tutor.show'))->assertOk()
            ->assertSee('Carlos Pineda')->assertSee('Dejar de compartir')->assertSee('Aceptada');
    }

    public function test_a_tutor_name_with_quotes_or_markup_can_neither_break_nor_inject_into_the_revoke_confirmation(): void
    {
        $evil = 'O\'Neill "Pepe" </script><img src=x onerror=alert(1)>';
        $this->carlos->update(['name' => $evil]);
        $juan = Student::where('codigo', '2020123')->firstOrFail();

        $response = $this->actingAs($juan->user)->get(route('my-tutor.show'))->assertOk();

        // El nombre solo aparece escapado, dentro de un atributo data-, y no hay ningún onclick con JavaScript interpolado.
        $response->assertSee('data-confirm="¿Dejar de compartir su información con '.e($evil).'?', false);
        $response->assertDontSee('onclick=', false);
        $response->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_the_student_bell_announces_a_pending_request_first(): void
    {
        $this->send($this->maria, $this->ruiz);

        $response = $this->actingAs($this->ruiz->user)->getJson('/notificaciones')->assertOk();

        $this->assertStringContainsString('María Londoño quiere acompañarle como tutor', $response->json('html'));
        $this->assertGreaterThanOrEqual(1, $response->json('count'));
    }

    public function test_a_user_without_a_student_record_sees_a_message_instead_of_an_error(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Estudiante]))
            ->get(route('my-tutor.show'))->assertOk()->assertSee('no tiene una ficha de estudiante');
        $this->delete(route('my-tutor.revoke'))->assertForbidden();
    }

    // ---- sin periodo vigente ----

    public function test_without_a_current_term_the_screens_render_and_the_flow_says_why(): void
    {
        DB::statement('truncate table academic_terms restart identity cascade');

        $this->actingAs($this->maria)->get(route('tutor-requests.index'))->assertOk();
        $this->actingAs($this->maria)->post(route('tutor-requests.store'), ['student_id' => $this->ruiz->id])
            ->assertSessionHasErrors(['student_id' => 'No hay un periodo académico vigente configurado.']);

        $this->actingAs($this->ruiz->user)->get(route('my-tutor.show'))->assertOk()->assertSee('sin periodo');
        $this->actingAs($this->ruiz->user)->delete(route('my-tutor.revoke'))->assertSessionHasErrors('tutor');
        $this->actingAs($this->maria)->get('/estudiantes')->assertOk();
    }
}
