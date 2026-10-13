<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\CommitteeMinute;
use App\Models\CommitteeRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Services\RequestWorkflow;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class RequestWorkflowTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private RequestWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->workflow = new RequestWorkflow;
    }

    private function coordinator(): User
    {
        return User::factory()->create(['role' => Role::Coordinacion]);
    }

    public function test_file_creates_the_request_its_attachments_and_the_first_history_row(): void
    {
        $student = $this->makeStudent();
        $type = RequestType::create(['nombre' => 'Reingreso al programa']);

        $request = $this->workflow->file($student, $type, $this->makeTerm(), str_repeat('Motivo. ', 10), [
            UploadedFile::fake()->create('incapacidad.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->image('foto.png'),
        ], $student->user);

        $this->assertSame('radicada', $request->estado);
        $this->assertSame($student->id, $request->student_id);
        $this->assertCount(2, $request->attachments);
        Storage::disk('local')->assertExists($request->attachments->first()->ruta);
        $this->assertSame('incapacidad.pdf', $request->attachments->first()->nombre_original);
        $this->assertCount(1, $request->statusHistory);
        $this->assertNull($request->statusHistory->first()->estado_anterior);
        $this->assertSame('radicada', $request->statusHistory->first()->estado_nuevo);
        $this->assertSame($student->user->id, $request->statusHistory->first()->user_id);
    }

    public function test_a_failure_while_storing_leaves_neither_rows_nor_files(): void
    {
        $student = $this->makeStudent();
        $type = RequestType::create(['nombre' => 'Otras']);

        try {
            $this->workflow->file($student, $type, $this->makeTerm(), str_repeat('Motivo. ', 10), [
                UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
                'esto-no-es-un-archivo',
            ], $student->user);
            $this->fail('Debía fallar al guardar el segundo soporte.');
        } catch (\Throwable) {
            $this->assertSame(0, CommitteeRequest::count());
            $this->assertDatabaseCount('request_attachments', 0);
            $this->assertDatabaseCount('request_status_history', 0);
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    public function test_review_then_decision_moves_through_the_three_states_with_history(): void
    {
        $coordinator = $this->coordinator();
        $request = $this->makeRequest($this->makeStudent('2030002'));
        $this->workflow->startReview($request, $coordinator);
        $this->assertSame('en_revision', $request->estado);

        $this->workflow->decide($request, 'aprobada', '12-2026', 'Procede.', $coordinator);

        $request->refresh();
        $this->assertSame('con_decision', $request->estado);
        $this->assertSame('aprobada', $request->decision);
        $this->assertSame('Procede.', $request->observaciones);
        $this->assertSame('12-2026', $request->minute->numero_acta);
        $this->assertSame(
            [['radicada', 'en_revision'], ['en_revision', 'con_decision']],
            $request->statusHistory()->orderBy('id')->get()->map(fn ($h) => [$h->estado_anterior, $h->estado_nuevo])->all()
        );
        $this->assertSame($coordinator->id, $request->statusHistory()->latest('id')->first()->user_id);
    }

    public function test_the_same_minute_resolves_several_requests(): void
    {
        $coordinator = $this->coordinator();
        foreach (['2030003', '2030004'] as $codigo) {
            $request = $this->makeRequest($this->makeStudent($codigo));
            $this->workflow->startReview($request, $coordinator);
            $this->workflow->decide($request, 'rechazada', '99-2026', null, $coordinator);
        }

        $this->assertSame(1, CommitteeMinute::where('numero_acta', '99-2026')->count());
        $this->assertSame(2, CommitteeRequest::where('minute_id', CommitteeMinute::first()->id)->count());
    }

    public function test_review_cannot_be_started_twice(): void
    {
        $request = $this->makeRequest($this->makeStudent());
        $coordinator = $this->coordinator();
        $this->workflow->startReview($request, $coordinator);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('ya fue pasada a revisión');
        $this->workflow->startReview($request, $coordinator);
    }

    public function test_a_decision_requires_a_prior_review(): void
    {
        $request = $this->makeRequest($this->makeStudent());

        try {
            $this->workflow->decide($request, 'aprobada', '1-2026', null, $this->coordinator());
            $this->fail('Debía exigir la revisión previa.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('antes de registrar la decisión', $e->getMessage());
            $this->assertSame('radicada', $request->fresh()->estado);
            $this->assertCount(0, $request->statusHistory);
        }
    }

    public function test_a_second_decision_is_rejected_and_keeps_the_first(): void
    {
        $request = $this->makeRequest($this->makeStudent());
        $coordinator = $this->coordinator();
        $this->workflow->startReview($request, $coordinator);
        $this->workflow->decide($request, 'aprobada', '1-2026', null, $coordinator);

        try {
            $this->workflow->decide($request, 'rechazada', '2-2026', null, $coordinator);
            $this->fail('Debía rechazar la segunda decisión.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('ya tiene una decisión', $e->getMessage());
            $this->assertSame('aprobada', $request->fresh()->decision);
            $this->assertSame(2, $request->statusHistory()->count());
        }
    }

    public function test_an_unknown_decision_value_is_rejected(): void
    {
        $request = $this->makeRequest($this->makeStudent());
        $this->workflow->startReview($request, $this->coordinator());

        $this->expectException(DomainException::class);
        $this->workflow->decide($request, 'quizás', '1-2026', null, $this->coordinator());
    }

    public function test_repeated_count_ignores_itself_other_types_other_terms_and_deleted_requests(): void
    {
        $student = $this->makeStudent();
        $first = $this->makeRequest($student, 'Cancelación de asignatura');
        $this->assertSame(0, $this->workflow->repeatedCount($first));

        $this->makeRequest($student, 'Cancelación de asignatura');
        $this->makeRequest($student, 'Reingreso al programa');
        $this->makeRequest($student, 'Cancelación de asignatura', ['term_id' => $this->makeTerm('2025-2')->id]);
        $this->makeRequest($student, 'Cancelación de asignatura')->delete();

        $this->assertSame(1, $this->workflow->repeatedCount($first));
        $this->assertSame(
            ['Cancelación de asignatura', 'Reingreso al programa'],
            collect($this->workflow->existingTypeNames($student, AcademicTerm::where('codigo', '2026-2')->first()))->sort()->values()->all()
        );
    }
}
