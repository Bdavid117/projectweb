<?php

namespace Tests\Feature\Database;

use App\Enums\Role;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class SyncLogTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    public function test_sync_logs_have_the_columns_for_the_api_synchronization_trail(): void
    {
        $this->assertTrue(Schema::hasColumns('sync_logs', ['fuente', 'term_id', 'estado', 'mensaje_error', 'registros', 'ejecutado_en']));
    }

    public function test_a_sync_run_is_recorded_with_its_term_and_result(): void
    {
        $term = $this->makeTerm();

        $log = SyncLog::create(['fuente' => 'api institucional', 'term_id' => $term->id, 'estado' => 'error', 'mensaje_error' => 'Tiempo de espera agotado', 'registros' => 0]);

        $this->assertSame('error', $log->fresh()->estado);
        $this->assertSame($term->id, $log->fresh()->term->id);
        $this->assertNotNull($log->fresh()->ejecutado_en);
    }

    public function test_deleting_a_term_keeps_the_log_without_it(): void
    {
        $term = $this->makeTerm('2025-1');
        $log = SyncLog::create(['fuente' => 'api institucional', 'term_id' => $term->id]);

        $term->delete();

        $this->assertNull($log->fresh()->term_id);
    }

    public function test_tutor_assignments_are_reachable_from_the_tutor_user(): void
    {
        $student = $this->makeStudent();
        $tutor = User::factory()->create(['role' => Role::Tutor]);
        \App\Models\TutorAssignment::create(['student_id' => $student->id, 'tutor_id' => $tutor->id, 'term_id' => $this->makeTerm()->id]);

        $this->assertSame(1, $tutor->tutorAssignments()->count());
    }
}
