<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use App\Models\RequestAttachment;
use App\Models\RequestType;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class RequestIndexShowTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private function juanRequest(): CommitteeRequest
    {
        return CommitteeRequest::whereHas('student', fn ($q) => $q->where('codigo', '2020123'))->firstOrFail();
    }

    private function lauraRequest(): CommitteeRequest
    {
        return CommitteeRequest::whereHas('student', fn ($q) => $q->where('codigo', '2021045'))->firstOrFail();
    }

    private function signInAs(string $email): void
    {
        $this->actingAs(User::where('email', $email)->firstOrFail());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    // ---- listado ----

    public function test_coordination_sees_every_request_with_status_and_a_create_button(): void
    {
        $this->signInSeeded(Role::Coordinacion);

        $response = $this->get('/solicitudes');

        $response->assertOk();
        $response->assertSee('Solicitudes');
        $response->assertSee('2 solicitudes en el periodo 2026-2S · 2 sin decisión');
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Gómez Ruiz, Laura');
        $response->assertSee('Cancelación de asignatura');
        $response->assertSee('Reingreso al programa');
        $response->assertSee('Radicada');
        $response->assertSee('En revisión');
        $response->assertSee('Nueva solicitud');
        $response->assertSee('Mostrando 1–2 de 2 solicitudes');
    }

    public function test_a_student_only_sees_their_own_requests(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $response = $this->get('/solicitudes');

        $response->assertOk();
        $response->assertSee('Mis solicitudes');
        $response->assertSee('Cancelación de asignatura');
        $response->assertDontSee('Gómez Ruiz, Laura');
        $response->assertDontSee('2021045');
        $response->assertDontSee('ESTUDIANTE');
        $response->assertSee('Mostrando 1–1 de 1 solicitudes');
    }

    public function test_a_student_cannot_widen_the_list_with_the_search_box(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $this->get('/solicitudes?q=Laura')->assertSee('Cancelación de asignatura')->assertDontSee('Gómez Ruiz, Laura');
    }

    public function test_a_professor_sees_only_the_requests_of_their_tutored_students_and_never_the_create_button(): void
    {
        // Ruiz Salazar no tiene tutor: su solicitud no debe verla ningún profesor.
        $this->makeRequest(Student::where('codigo', '2019087')->firstOrFail());

        $this->signInSeededTutor();
        $this->get('/solicitudes')->assertOk()
            ->assertSee('Gómez Ruiz, Laura')->assertSee('Pérez Rojas, Juan Sebastián')
            ->assertDontSee('Ruiz Salazar, Andrés')->assertDontSee('Nueva solicitud');

        $this->signInSeeded(Role::Profesor);
        $this->get('/solicitudes')->assertOk()
            ->assertDontSee('Gómez Ruiz, Laura')->assertDontSee('Pérez Rojas, Juan Sebastián')->assertDontSee('Ruiz Salazar, Andrés')
            ->assertDontSee('Nueva solicitud');
    }

    public function test_filters_by_status_type_and_search(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $reingreso = RequestType::where('nombre', 'Reingreso al programa')->firstOrFail();

        $this->get('/solicitudes?estado=en_revision')->assertSee('Gómez Ruiz, Laura')->assertDontSee('Pérez Rojas, Juan Sebastián');
        $this->get('/solicitudes?estado=radicada')->assertSee('Pérez Rojas, Juan Sebastián')->assertDontSee('Gómez Ruiz, Laura');
        $this->get('/solicitudes?tipo='.$reingreso->id)->assertSee('Gómez Ruiz, Laura')->assertDontSee('Pérez Rojas, Juan Sebastián');
        $this->get('/solicitudes?q=gómez')->assertSee('Gómez Ruiz, Laura')->assertDontSee('Pérez Rojas, Juan Sebastián');
        $this->get('/solicitudes?q=2020123')->assertSee('Pérez Rojas, Juan Sebastián')->assertDontSee('Gómez Ruiz, Laura');
        $this->get('/solicitudes?estado=aprobada')->assertSee('No hay solicitudes');
    }

    public function test_hostile_and_array_query_values_do_not_break_the_list(): void
    {
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/solicitudes?q=%25')->assertSee('No hay solicitudes');
        $this->get('/solicitudes?q[]=x&tipo[]=1&estado[]=z&periodo[]=2')->assertOk();
    }

    public function test_pagination_keeps_filters(): void
    {
        config(['program.page_size' => 1]);
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/solicitudes?page=2')->assertSee('Mostrando 2–2 de 2 solicitudes');
    }

    // ---- detalle ----

    public function test_coordination_opens_the_detail_with_description_and_trail(): void
    {
        $this->signInSeeded(Role::Coordinacion);

        $response = $this->get('/solicitudes/'.$this->juanRequest()->id);

        $response->assertOk();
        $response->assertSee('Cancelación de asignatura');
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Trazabilidad');
        $response->assertSee('Pendiente · la registra Coordinación');
        $response->assertSee('Código 2020123');
    }

    public function test_the_owner_coordination_and_the_linked_professor_can_open_but_nobody_else(): void
    {
        $request = $this->juanRequest();

        $this->signInSeeded(Role::Estudiante);
        $this->get('/solicitudes/'.$request->id)->assertOk()->assertDontSee('Ver ficha del estudiante');

        $this->signInSeeded(Role::Coordinacion);
        $this->get('/solicitudes/'.$request->id)->assertOk();

        // Juan es tutorado de Carlos Pineda; profesor@ no tiene vínculo con él.
        $this->signInSeededTutor();
        $this->get('/solicitudes/'.$request->id)->assertOk();

        $this->signInSeeded(Role::Profesor);
        $this->get('/solicitudes/'.$request->id)->assertForbidden();

        $this->signInAs('est2021045@unal.edu.co');
        $this->get('/solicitudes/'.$request->id)->assertForbidden();
    }

    public function test_coordination_is_warned_about_repeated_requests_but_the_student_is_not(): void
    {
        $juan = Student::where('codigo', '2020123')->firstOrFail();
        $this->makeRequest($juan, 'Cancelación de asignatura');
        $request = $this->juanRequest();

        $this->signInSeeded(Role::Coordinacion);
        $this->get('/solicitudes/'.$request->id)->assertSee('2.ª solicitud de este tipo');

        $this->signInSeeded(Role::Estudiante);
        $this->get('/solicitudes/'.$request->id)->assertDontSee('solicitud de este tipo');
    }

    public function test_deleted_unknown_and_non_numeric_requests_return_404(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $request = $this->juanRequest();
        $request->delete();

        $this->get('/solicitudes/'.$request->id)->assertNotFound();
        $this->get('/solicitudes/999999')->assertNotFound();
        $this->get('/solicitudes/abc')->assertNotFound();
    }

    // ---- soportes ----

    private function attach(CommitteeRequest $request): RequestAttachment
    {
        Storage::fake();
        Storage::put('soportes/'.$request->id.'/incapacidad.pdf', 'contenido');

        return $request->attachments()->create([
            'ruta' => 'soportes/'.$request->id.'/incapacidad.pdf', 'nombre_original' => 'incapacidad.pdf',
            'mime' => 'application/pdf', 'tamano' => 9,
        ]);
    }

    public function test_attachments_are_listed_and_downloadable_only_by_those_who_can_see_the_request(): void
    {
        $request = $this->juanRequest();
        $attachment = $this->attach($request);
        $url = '/solicitudes/'.$request->id.'/soportes/'.$attachment->id;

        $this->signInSeeded(Role::Estudiante);
        $this->get('/solicitudes/'.$request->id)->assertSee('incapacidad.pdf');
        $this->get($url)->assertOk()->assertHeader('content-disposition', 'attachment; filename=incapacidad.pdf');

        $this->signInSeeded(Role::Coordinacion);
        $this->get($url)->assertOk();

        $this->signInAs('est2021045@unal.edu.co');
        $this->get($url)->assertForbidden();
    }

    public function test_an_attachment_of_another_request_is_not_found_under_this_one(): void
    {
        $other = $this->attach($this->lauraRequest());
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/solicitudes/'.$this->juanRequest()->id.'/soportes/'.$other->id)->assertNotFound();
    }

    public function test_a_missing_file_returns_404(): void
    {
        $request = $this->juanRequest();
        $attachment = $this->attach($request);
        Storage::delete($attachment->ruta);
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/solicitudes/'.$request->id.'/soportes/'.$attachment->id)->assertNotFound();
    }
}
