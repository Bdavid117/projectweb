<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Thesis;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThesisEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_edits_title_repository_and_codirector_and_it_is_audited(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->signInSeeded(Role::Estudiante);
        $teacher = User::where('role', Role::Profesor->value)->firstOrFail();

        $this->get('/trabajo-de-grado')->assertOk()->assertSee('Actualizar mi trabajo de grado');
        $this->put('/trabajo-de-grado', ['titulo' => 'Título nuevo', 'repositorio_url' => 'https://repo.example.com/tesis', 'codirector_id' => $teacher->id])
            ->assertRedirect('/trabajo-de-grado');

        $thesis = Thesis::where('student_id', $user->student->id)->firstOrFail();
        $this->assertSame('Título nuevo', $thesis->titulo);
        $this->assertSame('https://repo.example.com/tesis', $thesis->repositorio_url);
        $this->assertSame($teacher->id, $thesis->codirector_id);
        $this->assertSame($user->id, AuditLog::where('accion', 'trabajo_grado.actualizado')->firstOrFail()->user_id);
    }

    public function test_thesis_validation_is_in_spanish_and_other_roles_are_forbidden(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $this->from('/trabajo-de-grado')->put('/trabajo-de-grado', ['titulo' => '', 'repositorio_url' => 'javascript:alert(1)', 'codirector_id' => 999999])
            ->assertRedirect('/trabajo-de-grado')
            ->assertSessionHasErrors(['titulo' => 'Indique el título del trabajo de grado.', 'repositorio_url' => 'El repositorio debe ser un enlace http o https válido.', 'codirector_id' => 'El codirector seleccionado no es válido.']);

        $this->signInSeeded(Role::Coordinacion);
        $this->put('/trabajo-de-grado', ['titulo' => 'X'])->assertForbidden();
    }
}
