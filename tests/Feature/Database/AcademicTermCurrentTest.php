<?php

namespace Tests\Feature\Database;

use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class AcademicTermCurrentTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    public function test_current_is_null_without_terms(): void
    {
        $this->assertNull(AcademicTerm::current());
    }

    public function test_current_falls_back_to_the_latest_term_when_no_record_exists(): void
    {
        $this->makeTerm('2025-2');
        $this->makeTerm('2026-1');
        AcademicTerm::where('codigo', '2026-1')->update(['fecha_inicio' => '2026-02-01']);
        AcademicTerm::where('codigo', '2025-2')->update(['fecha_inicio' => '2025-08-01']);

        $this->assertSame('2026-1', AcademicTerm::current()->codigo);
    }

    public function test_current_prefers_the_latest_term_that_has_academic_records(): void
    {
        $student = $this->makeStudent();
        $withRecords = $this->makeTerm('2026-1');
        $empty = $this->makeTerm('2026-2');
        $withRecords->update(['fecha_inicio' => '2026-02-01']);
        $empty->update(['fecha_inicio' => '2026-08-01']);
        AcademicRecord::create(['student_id' => $student->id, 'term_id' => $withRecords->id, 'papa' => '4.00']);

        $this->assertSame('2026-1', AcademicTerm::current()->codigo);
    }
}
