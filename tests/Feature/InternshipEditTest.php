<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Internship;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternshipEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_edits_supervisor_and_activities_and_it_is_audited(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->signInSeeded(Role::Estudiante);

        $this->get('/practica')->assertOk()->assertSee('Actualizar mi práctica');
        $this->put('/practica', ['jefe_nombre' => 'Ana Pérez', 'jefe_cargo' => 'Líder técnica', 'actividades' => 'Desarrollo de APIs'])
            ->assertRedirect('/practica');

        $internship = Internship::where('student_id', $user->student->id)->firstOrFail();
        $this->assertSame('Ana Pérez', $internship->jefe_nombre);
        $this->assertSame('Desarrollo de APIs', $internship->actividades);
        $this->assertSame($user->id, AuditLog::where('accion', 'practica.actualizada')->firstOrFail()->user_id);
    }

    public function test_internship_validation_is_in_spanish_and_other_roles_are_forbidden(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $this->from('/practica')->put('/practica', ['jefe_nombre' => ''])
            ->assertRedirect('/practica')
            ->assertSessionHasErrors(['jefe_nombre' => 'Indique el nombre del jefe inmediato.']);

        $this->signInSeededTutor();
        $this->put('/practica', ['jefe_nombre' => 'X'])->assertForbidden();
    }
}
