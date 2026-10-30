<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use App\Models\RequestType;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->seed(DatabaseSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'tipo' => RequestType::where('nombre', 'Homologación de créditos')->firstOrFail()->id,
            'descripcion' => str_repeat('Solicito la homologación de créditos cursados. ', 3),
        ];
    }

    private function assertNothingWasStored(): void
    {
        $this->assertSame(2, CommitteeRequest::count());
        $this->assertDatabaseCount('request_attachments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_the_form_renders_with_type_ids_term_and_enctype(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $response = $this->get('/solicitudes/nueva');

        $response->assertOk();
        $response->assertSee('Nueva solicitud al comité asesor');
        $response->assertSee('multipart/form-data', false);
        $response->assertSee('2026-2');
        $response->assertSee('name="soportes[]"', false);
        $response->assertSee('Homologación de créditos');
        $response->assertSee('Ya radicó este periodo: Cancelación de asignatura');
        $response->assertDontSee('Guardar borrador');
        $response->assertDontSee('Falta un campo obligatorio');
        $response->assertDontSee('codigo_estudiante');
    }

    public function test_a_student_files_a_request_with_attachments(): void
    {
        $user = $this->signInSeeded(Role::Estudiante);

        $response = $this->post('/solicitudes', $this->payload([
            'soportes' => [
                UploadedFile::fake()->create('incapacidad.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->image('foto.png'),
            ],
        ]));

        $request = CommitteeRequest::latest('id')->firstOrFail();
        $response->assertRedirect(route('requests.show', $request));
        $response->assertSessionHas('status', 'Solicitud radicada correctamente.');
        $this->assertSame('radicada', $request->estado);
        $this->assertSame($user->student->id, $request->student_id);
        $this->assertSame('2026-2', $request->term->codigo);
        $this->assertCount(2, $request->attachments);
        Storage::disk('local')->assertExists($request->attachments->first()->ruta);
        $this->assertCount(1, $request->statusHistory);
        $this->assertSame($user->id, $request->statusHistory->first()->user_id);
    }

    public function test_the_student_always_files_for_themselves_even_if_a_code_is_sent(): void
    {
        $user = $this->signInSeeded(Role::Estudiante);

        $this->post('/solicitudes', $this->payload(['codigo_estudiante' => '2021045']))->assertRedirect();

        $this->assertSame($user->student->id, CommitteeRequest::latest('id')->firstOrFail()->student_id);
    }

    public function test_validation_errors_are_shown_next_to_the_field_and_keep_the_typed_text(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $response = $this->from('/solicitudes/nueva')->post('/solicitudes', ['descripcion' => 'Muy corta']);

        $response->assertRedirect('/solicitudes/nueva');
        $response->assertSessionHasErrors(['tipo' => 'Seleccione el tipo de solicitud antes de continuar.', 'descripcion' => 'La descripción debe tener al menos 50 caracteres.']);
        $this->assertNothingWasStored();

        $this->get('/solicitudes/nueva')
            ->assertSee('Seleccione el tipo de solicitud antes de continuar.')
            ->assertSee('Muy corta')
            ->assertSee('Revise los campos marcados');
    }

    public function test_an_unknown_request_type_is_rejected(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $this->post('/solicitudes', $this->payload(['tipo' => 999999]))->assertSessionHasErrors(['tipo' => 'El tipo de solicitud seleccionado no es válido.']);
        $this->assertNothingWasStored();
    }

    public function test_invalid_attachments_leave_nothing_stored(): void
    {
        $this->signInSeeded(Role::Estudiante);

        $this->post('/solicitudes', $this->payload(['soportes' => [UploadedFile::fake()->create('grande.pdf', 6000, 'application/pdf')]]))
            ->assertSessionHasErrors(['soportes.0' => 'Cada soporte puede pesar máximo 5 MB.']);
        $this->post('/solicitudes', $this->payload(['soportes' => [UploadedFile::fake()->create('virus.exe', 10)]]))
            ->assertSessionHasErrors(['soportes.0' => 'Los soportes deben ser PDF, JPG o PNG.']);
        $this->post('/solicitudes', $this->payload(['soportes' => array_map(fn ($i) => UploadedFile::fake()->create("a{$i}.pdf", 10, 'application/pdf'), range(1, 6))]))
            ->assertSessionHasErrors(['soportes' => 'Puede adjuntar hasta 5 archivos.']);

        $this->assertNothingWasStored();
    }

    public function test_coordination_files_on_behalf_of_a_student_by_code(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $laura = Student::where('codigo', '2021045')->firstOrFail();

        $this->get('/solicitudes/nueva')->assertSee('codigo_estudiante');
        $this->post('/solicitudes', $this->payload())->assertSessionHasErrors(['codigo_estudiante' => 'Indique el código del estudiante.']);
        $this->post('/solicitudes', $this->payload(['codigo_estudiante' => '0000000']))->assertSessionHasErrors(['codigo_estudiante' => 'No existe un estudiante con ese código.']);
        $this->assertSame(2, CommitteeRequest::count());

        $this->post('/solicitudes', $this->payload(['codigo_estudiante' => '2021045']))->assertRedirect();

        $this->assertSame($laura->id, CommitteeRequest::latest('id')->firstOrFail()->student_id);
    }

    public function test_professors_cannot_file_and_guests_are_sent_to_login(): void
    {
        foreach ([fn () => $this->signInSeeded(Role::Profesor), fn () => $this->signInSeededTutor()] as $signIn) {
            $signIn();
            $this->post('/solicitudes', $this->payload())->assertForbidden();
            $this->get('/solicitudes/nueva')->assertForbidden();
        }
        $this->assertSame(2, CommitteeRequest::count());

        auth()->logout();
        $this->post('/solicitudes', $this->payload())->assertRedirect('/login');
    }

    public function test_a_student_user_without_a_student_record_cannot_file(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Estudiante]));

        $this->post('/solicitudes', $this->payload())->assertForbidden();
        $this->assertSame(2, CommitteeRequest::count());
    }
}
