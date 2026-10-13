<?php

namespace Tests\Feature\Database;

use App\Models\CommitteeMinute;
use App\Models\CommitteeRequest;
use App\Models\RequestAttachment;
use App\Models\RequestStatusHistory;
use App\Models\RequestType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class RequestSchemaTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private function makeRequest(): CommitteeRequest
    {
        return CommitteeRequest::create([
            'student_id' => $this->makeStudent()->id,
            'request_type_id' => RequestType::create(['nombre' => 'Cancelación de asignatura'])->id,
            'term_id' => $this->makeTerm()->id,
            'descripcion' => 'Solicito cancelar la asignatura.',
            'estado' => 'radicada',
            'fecha_radicacion' => '2026-09-10',
        ]);
    }

    public function test_tables_have_the_columns_from_the_analysis_document(): void
    {
        $expected = [
            'requests' => ['student_id', 'request_type_id', 'term_id', 'descripcion', 'estado', 'fecha_radicacion', 'minute_id', 'decision', 'observaciones', 'deleted_at'],
            'request_attachments' => ['request_id', 'ruta', 'nombre_original', 'mime', 'tamano'],
            'request_status_history' => ['request_id', 'estado_anterior', 'estado_nuevo', 'user_id', 'fecha'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasColumns($table, $columns), "Faltan columnas en $table");
        }
    }

    public function test_request_keeps_status_trail_and_can_be_resolved_by_a_shared_minute(): void
    {
        $request = $this->makeRequest();
        $user = User::factory()->create();
        RequestStatusHistory::create(['request_id' => $request->id, 'estado_anterior' => null, 'estado_nuevo' => 'radicada', 'user_id' => $user->id, 'fecha' => now()]);
        RequestStatusHistory::create(['request_id' => $request->id, 'estado_anterior' => 'radicada', 'estado_nuevo' => 'en_revision', 'user_id' => $user->id, 'fecha' => now()]);

        $minute = CommitteeMinute::create(['numero_acta' => '12-2026', 'fecha' => '2026-09-20', 'instancia' => 'Comité asesor']);
        $request->update(['minute_id' => $minute->id, 'decision' => 'aprobada']);

        $this->assertSame(2, $request->statusHistory()->count());
        $this->assertSame('12-2026', $request->fresh()->minute->numero_acta);
        $this->assertSame(1, $minute->requests()->count());
    }

    public function test_filing_date_keeps_the_time_of_day(): void
    {
        $request = $this->makeRequest();
        $request->update(['fecha_radicacion' => '2026-09-10 14:30:00']);

        $this->assertSame('2026-09-10 14:30', $request->fresh()->fecha_radicacion->format('Y-m-d H:i'));
    }

    public function test_soft_deleted_requests_are_hidden_but_kept(): void
    {
        $request = $this->makeRequest();

        $request->delete();

        $this->assertSame(0, CommitteeRequest::count());
        $this->assertSame(1, CommitteeRequest::withTrashed()->count());
    }

    public function test_attachments_are_removed_with_their_request_row(): void
    {
        $request = $this->makeRequest();
        RequestAttachment::create(['request_id' => $request->id, 'ruta' => 'soportes/a.pdf', 'nombre_original' => 'a.pdf', 'mime' => 'application/pdf', 'tamano' => 1024]);

        $request->forceDelete();

        $this->assertDatabaseCount('request_attachments', 0);
    }
}
