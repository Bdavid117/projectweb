<?php

namespace Tests\Feature\Database;

use App\Models\AcademicRecord;
use App\Models\TutorAssignment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class StudentSchemaTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private function record(int $studentId, int $termId, string $papa = '4.10'): AcademicRecord
    {
        return AcademicRecord::create([
            'student_id' => $studentId, 'term_id' => $termId, 'papa' => $papa,
            'promedio_periodo' => '4.00', 'cupo_creditos' => 21, 'creditos_cursados' => 116,
            'porcentaje_avance' => '72.00', 'numero_matricula' => 7,
        ]);
    }

    public function test_tables_have_the_columns_from_the_analysis_document(): void
    {
        $expected = [
            'students' => ['user_id', 'codigo', 'documento', 'cohorte', 'estado', 'fecha_ingreso'],
            'admission_profiles' => ['student_id', 'school_id', 'puntaje_saber11', 'puntaje_admision', 'hobby_formal'],
            'health_profiles' => ['student_id', 'resultado_oso', 'resultado_salud_mental', 'resultado_salud_fisica', 'observaciones'],
            'saber_pro_results' => ['student_id', 'fecha_presentacion', 'puntaje_global', 'resultados_json'],
            'academic_records' => ['student_id', 'term_id', 'papa', 'promedio_periodo', 'cupo_creditos', 'creditos_cursados', 'porcentaje_avance', 'numero_matricula', 'sincronizado_en'],
            'tutor_assignments' => ['student_id', 'tutor_id', 'term_id'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasColumns($table, $columns), "Faltan columnas en $table");
        }
    }

    public function test_one_academic_record_per_student_and_term(): void
    {
        $student = $this->makeStudent();
        $term = $this->makeTerm();
        $this->record($student->id, $term->id);

        $this->expectException(QueryException::class);
        $this->record($student->id, $term->id, '3.00');
    }

    public function test_a_new_term_adds_a_record_without_overwriting_history(): void
    {
        $student = $this->makeStudent();
        $this->record($student->id, $this->makeTerm('2026-1')->id, '3.90');
        $this->record($student->id, $this->makeTerm('2026-2')->id, '4.10');

        $this->assertSame(2, $student->academicRecords()->count());
        $this->assertSame('3.90', $student->academicRecords()->whereHas('term', fn ($q) => $q->where('codigo', '2026-1'))->first()->papa);
    }

    public function test_one_tutor_per_student_per_term(): void
    {
        $student = $this->makeStudent();
        $term = $this->makeTerm();
        $tutor = User::factory()->create();
        TutorAssignment::create(['student_id' => $student->id, 'tutor_id' => $tutor->id, 'term_id' => $term->id]);

        $this->expectException(QueryException::class);
        TutorAssignment::create(['student_id' => $student->id, 'tutor_id' => User::factory()->create()->id, 'term_id' => $term->id]);
    }

    public function test_student_with_academic_history_cannot_be_deleted(): void
    {
        $student = $this->makeStudent();
        $this->record($student->id, $this->makeTerm()->id);

        try {
            DB::transaction(fn () => $student->delete());
            $this->fail('La historia académica no debe borrarse en cascada.');
        } catch (QueryException) {
            $this->assertDatabaseCount('academic_records', 1);
        }
    }

    public function test_documento_and_codigo_are_unique(): void
    {
        $this->makeStudent('2020123');

        $this->expectException(QueryException::class);
        $this->makeStudent('2020123');
    }
}
