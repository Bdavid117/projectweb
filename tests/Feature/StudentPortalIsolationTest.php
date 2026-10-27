<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Company;
use App\Models\Internship;
use App\Models\Thesis;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class StudentPortalIsolationTest extends TestCase
{
    use CreatesStudents;
    use RefreshDatabase;

    public function test_each_student_sees_and_edits_only_their_own_portal_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $other = $this->makeStudent('2020999');
        $thesis = Thesis::create(['student_id' => $other->id, 'modalidad' => 'trabajo_de_investigacion', 'estado' => 'en_desarrollo', 'titulo' => 'Tesis ajena secreta', 'term_id' => AcademicTerm::firstOrFail()->id]);
        $internship = Internship::create(['student_id' => $other->id, 'company_id' => Company::firstOrFail()->id, 'jefe_nombre' => 'Jefe ajeno', 'fecha_inicio' => '2026-02-01', 'actividades' => 'Actividades ajenas']);

        $mine = $this->signInSeeded(Role::Estudiante);

        $this->get('/trabajo-de-grado')->assertOk()->assertDontSee('Tesis ajena secreta');
        $this->get('/practica')->assertOk()->assertDontSee('Jefe ajeno')->assertDontSee('Actividades ajenas');
        $this->get('/perfil')->assertOk()->assertDontSee('2020999');
        $this->get('/panel')->assertOk()->assertDontSee('2020999');

        $this->put('/trabajo-de-grado', ['titulo' => 'Mi título'])->assertRedirect('/trabajo-de-grado');
        $this->put('/practica', ['jefe_nombre' => 'Mi jefe'])->assertRedirect('/practica');
        $this->put('/perfil', ['name' => $mine->name, 'email' => $mine->email, 'telefono' => '3001234567'])->assertRedirect('/perfil');

        $this->assertSame('Tesis ajena secreta', $thesis->fresh()->titulo);
        $this->assertSame('Jefe ajeno', $internship->fresh()->jefe_nombre);
        $this->assertNull($other->fresh()->telefono);
        $this->assertSame('Mi título', Thesis::where('student_id', $mine->student->id)->firstOrFail()->titulo);
        $this->assertSame('Mi jefe', Internship::where('student_id', $mine->student->id)->firstOrFail()->jefe_nombre);
    }

    public function test_a_student_cannot_edit_a_thesis_or_internship_they_do_not_have(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);
        Thesis::query()->delete();
        Internship::query()->delete();

        $this->put('/trabajo-de-grado', ['titulo' => 'X'])->assertForbidden();
        $this->put('/practica', ['jefe_nombre' => 'X'])->assertForbidden();
    }
}
