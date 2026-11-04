<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Intervention;
use App\Models\Student;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** RF-36: intervenciones de acompañamiento con su origen y su resultado. */
class InterventionTest extends TestCase
{
    use RefreshDatabase;

    private Student $juan;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-04 12:00:00');
        $this->seed(DatabaseSeeder::class);
        $this->juan = Student::where('codigo', '2020123')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function valid(array $overrides = []): array
    {
        return $overrides + [
            'origen' => 'observacion_profesor',
            'descripcion' => 'Acompañamiento cercano para revisar su carga académica.',
            'fecha' => '2026-11-04',
        ];
    }

    private function storeUrl(): string
    {
        return '/estudiantes/'.$this->juan->id.'/intervenciones';
    }

    private function createUrl(): string
    {
        return '/estudiantes/'.$this->juan->id.'/intervenciones/crear';
    }

    public function test_coordination_registers_an_open_intervention(): void
    {
        $coord = $this->signInSeeded(Role::Coordinacion);

        $this->post($this->storeUrl(), $this->valid())
            ->assertRedirect('/estudiantes/'.$this->juan->id)
            ->assertSessionHas('status', 'Intervención registrada correctamente.');

        $intervention = Intervention::firstOrFail();
        $this->assertSame($this->juan->id, $intervention->student_id);
        $this->assertSame('abierta', $intervention->estado);
        $this->assertSame($coord->id, $intervention->responsable_id);
    }

    public function test_the_linked_professor_registers_and_the_unlinked_one_cannot(): void
    {
        $tutor = $this->signInSeededTutor();
        $this->post($this->storeUrl(), $this->valid())->assertRedirect();
        $this->assertSame($tutor->id, Intervention::latest('id')->firstOrFail()->responsable_id);

        // profesor@ no tiene vínculo con Juan.
        $this->signInSeeded(Role::Profesor);
        $this->get($this->createUrl())->assertForbidden();
        $this->post($this->storeUrl(), $this->valid())->assertForbidden();
    }

    public function test_students_cannot_register_interventions(): void
    {
        $this->signInSeeded(Role::Estudiante);
        $this->get($this->createUrl())->assertForbidden();
        $this->post($this->storeUrl(), $this->valid())->assertForbidden();
    }

    public function test_validation_messages_in_spanish_and_nothing_is_stored(): void
    {
        $this->signInSeeded(Role::Coordinacion);

        $this->from($this->createUrl())
            ->post($this->storeUrl(), ['origen' => 'rector', 'descripcion' => 'corta', 'fecha' => '2030-01-01'])
            ->assertRedirect($this->createUrl())
            ->assertSessionHasErrors([
                'origen' => 'Seleccione un origen de la lista.',
                'descripcion' => 'Describa la intervención (mínimo 10 caracteres).',
                'fecha' => 'La fecha no puede ser posterior a hoy.',
            ]);

        $this->assertSame(0, Intervention::count());
    }

    public function test_an_origin_of_another_student_is_rejected(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $otherAlert = Alert::where('student_id', '!=', $this->juan->id)->firstOrFail();

        $this->post($this->storeUrl(), $this->valid(['origen' => 'alerta', 'origen_id' => $otherAlert->id]))
            ->assertSessionHasErrors(['origen_id' => 'El origen seleccionado no pertenece a este estudiante.']);

        $this->assertSame(0, Intervention::count());
    }

    public function test_the_form_prefills_the_origin_from_an_alert(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $alert = Alert::where('student_id', $this->juan->id)->firstOrFail();

        $this->get($this->createUrl().'?origen=alerta&origen_id='.$alert->id)
            ->assertOk()
            ->assertSee('Enlazada con su origen')
            ->assertSee('value="'.$alert->id.'"', false);
    }

    public function test_registering_from_an_alert_marks_it_attended(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $alert = Alert::where('student_id', $this->juan->id)->where('atendida', false)->firstOrFail();

        $this->post($this->storeUrl(), $this->valid(['origen' => 'alerta', 'origen_id' => $alert->id]))->assertRedirect();

        $this->assertTrue($alert->fresh()->atendida);
        $this->assertSame('alerta', Intervention::latest('id')->firstOrFail()->origen);
    }

    public function test_closing_registers_the_result_and_state(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $this->post($this->storeUrl(), $this->valid());
        $intervention = Intervention::firstOrFail();

        $this->get('/intervenciones/'.$intervention->id.'/cerrar')->assertOk()->assertSee('Cerrar intervención');

        $this->put('/intervenciones/'.$intervention->id.'/cierre', ['resultado' => 'Se acordó un plan de estudio con la familia.'])
            ->assertRedirect('/estudiantes/'.$this->juan->id)
            ->assertSessionHas('status', 'Intervención cerrada correctamente.');

        $intervention->refresh();
        $this->assertSame('cerrada', $intervention->estado);
        $this->assertNotNull($intervention->cerrada_en);
        $this->assertStringContainsString('plan de estudio', $intervention->resultado);
    }

    public function test_closing_an_already_closed_intervention_does_not_change_it(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $this->post($this->storeUrl(), $this->valid());
        $intervention = Intervention::firstOrFail();
        $this->put('/intervenciones/'.$intervention->id.'/cierre', ['resultado' => 'Primer resultado del acompañamiento.']);

        $this->put('/intervenciones/'.$intervention->id.'/cierre', ['resultado' => 'Segundo intento de resultado distinto.'])
            ->assertRedirect();

        $this->assertStringContainsString('Primer resultado', $intervention->fresh()->resultado);
    }

    public function test_the_ficha_lists_interventions_for_the_staff(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $this->post($this->storeUrl(), $this->valid());

        $this->get('/estudiantes/'.$this->juan->id)
            ->assertOk()
            ->assertSee('Intervenciones de acompañamiento')
            ->assertSee('Origen: Observación de un profesor')
            ->assertSee('id="panel-intervenciones"', false);
    }

    public function test_registering_and_closing_are_audited(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $this->post($this->storeUrl(), $this->valid());
        $this->assertSame(1, AuditLog::where('accion', 'intervencion_registrada')->count());

        $intervention = Intervention::firstOrFail();
        $this->put('/intervenciones/'.$intervention->id.'/cierre', ['resultado' => 'Resultado del acompañamiento registrado.']);
        $this->assertSame(1, AuditLog::where('accion', 'intervencion_cerrada')->count());
    }
}
