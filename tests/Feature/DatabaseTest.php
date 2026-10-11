<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_estudiante_con_historia_por_periodo(): void
    {
        $term = AcademicTerm::create(['code' => '2026-2S', 'name' => 'Periodo 2026-2S']);
        $user = User::create([
            'name' => 'Pérez Rojas, Juan',
            'email' => 'juan@example.com',
            'password' => 'password',
            'role' => 'estudiante',
        ]);
        $student = Student::create([
            'user_id' => $user->id,
            'codigo' => '2020123',
            'documento' => '123456789',
            'cohorte' => '2020-1',
            'estado' => 'Activo',
        ]);

        AcademicRecord::create([
            'student_id' => $student->id,
            'term_id' => $term->id,
            'papa' => 4.1,
            'porcentaje_avance' => 72,
        ]);

        $this->assertDatabaseHas('students', ['codigo' => '2020123']);
        $this->assertDatabaseHas('academic_records', [
            'student_id' => $student->id,
            'term_id' => $term->id,
        ]);

        // Historicidad (RNF-08): duplicar periodo debe violar la UK.
        $this->expectException(\Illuminate\Database\QueryException::class);
        AcademicRecord::create([
            'student_id' => $student->id,
            'term_id' => $term->id,
            'papa' => 3.0,
        ]);
    }
}
