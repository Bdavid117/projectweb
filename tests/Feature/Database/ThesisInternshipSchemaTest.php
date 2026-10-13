<?php

namespace Tests\Feature\Database;

use App\Models\Company;
use App\Models\Internship;
use App\Models\InternshipFollowUp;
use App\Models\Thesis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class ThesisInternshipSchemaTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    public function test_tables_have_the_columns_from_the_analysis_document(): void
    {
        $expected = [
            'theses' => ['student_id', 'modalidad', 'estado', 'titulo', 'director_id', 'codirector_id', 'term_id', 'repositorio_url'],
            'internships' => ['student_id', 'company_id', 'jefe_nombre', 'jefe_cargo', 'asesor_id', 'fecha_inicio', 'fecha_fin', 'actividades', 'apreciacion_jefe', 'apreciacion_asesor'],
            'internship_follow_ups' => ['internship_id', 'fecha', 'observacion', 'autor_id'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasColumns($table, $columns), "Faltan columnas en $table");
        }
    }

    public function test_student_can_have_a_thesis_and_an_internship_with_accumulated_follow_ups(): void
    {
        $student = $this->makeStudent();
        $advisor = User::factory()->create();

        Thesis::create([
            'student_id' => $student->id, 'modalidad' => 'pasantia', 'estado' => 'en_desarrollo',
            'director_id' => $advisor->id, 'term_id' => $this->makeTerm()->id,
        ]);
        $internship = Internship::create([
            'student_id' => $student->id,
            'company_id' => Company::create(['nombre' => 'Softlogic S.A.S.'])->id,
            'asesor_id' => $advisor->id, 'fecha_inicio' => '2026-07-01', 'fecha_fin' => '2026-12-15',
        ]);
        foreach (['2026-08-01', '2026-09-01'] as $fecha) {
            InternshipFollowUp::create(['internship_id' => $internship->id, 'fecha' => $fecha, 'observacion' => 'Avance normal', 'autor_id' => $advisor->id]);
        }

        $this->assertSame(1, $student->theses()->count());
        $this->assertSame(1, $student->internships()->count());
        $this->assertSame(2, $internship->followUps()->count());
        $this->assertSame('Softlogic S.A.S.', $internship->company->nombre);
    }

    public function test_follow_ups_are_removed_with_their_internship(): void
    {
        $student = $this->makeStudent();
        $advisor = User::factory()->create();
        $internship = Internship::create([
            'student_id' => $student->id,
            'company_id' => Company::create(['nombre' => 'ACME'])->id,
            'asesor_id' => $advisor->id, 'fecha_inicio' => '2026-07-01',
        ]);
        InternshipFollowUp::create(['internship_id' => $internship->id, 'fecha' => '2026-08-01', 'observacion' => 'x', 'autor_id' => $advisor->id]);

        $internship->delete();

        $this->assertDatabaseCount('internship_follow_ups', 0);
    }
}
