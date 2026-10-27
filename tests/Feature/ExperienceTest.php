<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class ExperienceTest extends TestCase
{
    use CreatesStudents;
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'experience_type_id' => ExperienceType::where('categoria', 'Deporte y cultura')->firstOrFail()->id,
            'nombre' => 'Semillero de prueba',
            'fecha_inicio' => '2026-02-01',
        ];
    }

    public function test_student_sees_only_their_own_experiences_and_coordination_sees_all(): void
    {
        $this->seed(DatabaseSeeder::class);

        $student = $this->signInSeeded(Role::Estudiante);
        $own = Experience::where('student_id', $student->student->id)->count();
        $this->assertGreaterThan(0, $own);
        $this->get('/experiencias')->assertOk()->assertSee('Mis experiencias formativas')->assertSee("{$own} registradas");

        $this->signInSeeded(Role::Coordinacion);
        $this->get('/experiencias')->assertOk()->assertSee(Experience::count().' registradas');
    }

    public function test_student_registers_edits_and_deletes_a_pending_experience(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->signInSeeded(Role::Estudiante);

        $this->get('/experiencias/crear')->assertOk()->assertSee('Registrar experiencia');
        $this->post('/experiencias', $this->payload())->assertRedirect('/experiencias');

        $experience = Experience::where('nombre', 'Semillero de prueba')->firstOrFail();
        $this->assertSame($user->student->id, $experience->student_id);
        $this->assertFalse($experience->validada);

        $this->get("/experiencias/{$experience->id}/editar")->assertOk()->assertSee('Semillero de prueba');
        $this->put("/experiencias/{$experience->id}", $this->payload(['nombre' => 'Nombre nuevo']))->assertRedirect('/experiencias');
        $this->assertSame('Nombre nuevo', $experience->fresh()->nombre);

        $this->delete("/experiencias/{$experience->id}")->assertRedirect('/experiencias');
        $this->assertModelMissing($experience);
    }

    public function test_validation_messages_are_in_spanish(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $this->from('/experiencias/crear')->post('/experiencias', ['fecha_inicio' => '2999-01-01'])
            ->assertRedirect('/experiencias/crear')
            ->assertSessionHasErrors(['experience_type_id' => 'Seleccione el tipo de experiencia.', 'nombre' => 'Indique el nombre de la experiencia.', 'fecha_inicio' => 'La fecha de inicio no puede ser posterior a hoy.']);
    }

    public function test_student_cannot_touch_validated_or_foreign_experiences(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->signInSeeded(Role::Estudiante);

        $validated = Experience::where('student_id', $user->student->id)->where('validada', true)->firstOrFail();
        $foreign = $this->makeStudent('2020999')->experiences()->create($this->payload(['validada' => false]));

        foreach ([$validated, $foreign] as $experience) {
            $this->get("/experiencias/{$experience->id}/editar")->assertForbidden();
            $this->put("/experiencias/{$experience->id}", $this->payload())->assertForbidden();
            $this->delete("/experiencias/{$experience->id}")->assertForbidden();
        }
        $this->post("/experiencias/{$foreign->id}/validacion")->assertForbidden();
    }

    public function test_coordination_validates_but_cannot_register(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);
        $this->post('/experiencias', $this->payload());
        $experience = Experience::where('nombre', 'Semillero de prueba')->firstOrFail();

        $coordinator = $this->signInSeeded(Role::Coordinacion);
        $this->get('/experiencias/crear')->assertForbidden();
        $this->post("/experiencias/{$experience->id}/validacion")->assertRedirect('/experiencias');

        $this->assertTrue($experience->fresh()->validada);
        $this->assertSame($coordinator->id, $experience->fresh()->validada_por);
    }

    public function test_tutor_and_profesor_read_experiences_but_cannot_change_them(): void
    {
        $this->seed(DatabaseSeeder::class);
        $experience = Experience::firstOrFail();

        foreach ([Role::Tutor, Role::Profesor] as $role) {
            $this->signInSeeded($role);
            $this->get('/experiencias')->assertOk()->assertSee(Experience::count().' registradas')->assertSee('solo lectura')->assertDontSee('Registrar experiencia');
            $this->get('/experiencias/crear')->assertForbidden();
            $this->get("/experiencias/{$experience->id}/editar")->assertForbidden();
            $this->post("/experiencias/{$experience->id}/validacion")->assertForbidden();
            $this->post("/experiencias/{$experience->id}/rechazo", ['motivo' => 'x'])->assertForbidden();
        }
    }

    public function test_form_fields_depend_on_the_experience_type(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);
        $internship = ExperienceType::where('categoria', 'Pasantía')->firstOrFail();
        $research = ExperienceType::where('categoria', 'Investigación')->firstOrFail();

        $this->from('/experiencias/crear')->post('/experiencias', $this->payload(['experience_type_id' => $internship->id]))
            ->assertSessionHasErrors(['institucion' => 'Indique la institución.', 'rol' => 'Indique su rol en la experiencia.']);

        $this->post('/experiencias', $this->payload([
            'experience_type_id' => $research->id,
            'docente_id' => User::where('role', Role::Profesor->value)->value('id'),
            'convenio' => 'No aplica a investigación',
            'institucion' => 'Tampoco aplica',
        ]))->assertRedirect('/experiencias');

        $saved = Experience::where('nombre', 'Semillero de prueba')->firstOrFail();
        $this->assertNull($saved->convenio);
        $this->assertNull($saved->institucion);
        $this->assertNotNull($saved->docente_id);
    }

    public function test_coordination_rejects_with_reason_and_student_corrects_it(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);
        $this->post('/experiencias', $this->payload());
        $experience = Experience::where('nombre', 'Semillero de prueba')->firstOrFail();

        $coordinator = $this->signInSeeded(Role::Coordinacion);
        $this->post("/experiencias/{$experience->id}/rechazo", [])->assertSessionHasErrors(['motivo' => 'Indique el motivo del rechazo.']);
        $this->post("/experiencias/{$experience->id}/rechazo", ['motivo' => 'Falta soporte'])->assertRedirect('/experiencias');

        $experience->refresh();
        $this->assertSame('rechazada', $experience->estado());
        $this->assertSame($coordinator->id, $experience->rechazada_por);
        $this->get('/experiencias?estado=rechazada')->assertSee('Motivo del rechazo: Falta soporte');
        $this->get('/experiencias?estado=pendiente')->assertDontSee('Semillero de prueba');
        $this->post("/experiencias/{$experience->id}/validacion")->assertForbidden();

        $this->signInSeeded(Role::Estudiante);
        $this->get("/experiencias/{$experience->id}/editar")->assertOk()->assertSee('Falta soporte');
        $this->put("/experiencias/{$experience->id}", $this->payload(['nombre' => 'Corregida']))->assertRedirect('/experiencias');
        $this->assertSame('pendiente', $experience->fresh()->estado());
    }
}
