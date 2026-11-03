<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\CommitteeRequest;
use App\Models\RequestType;
use App\Models\Student;
use App\Models\TutorAssignment;
use App\Models\Tutorship;
use App\Models\User;
use App\Support\AlertRules;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RF-37 «Generar alertas tempranas»: una tarea programada evalúa reglas parametrizables y deja las alertas en el panel.
 * Estas pruebas arman sus propios estudiantes: el sembrador de demostración tiene alertas escritas a mano que no se derivan de reglas.
 */
class AlertEngineTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<AcademicTerm> */
    private array $terms;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 10:00:00');
        $this->terms = [
            AcademicTerm::create(['codigo' => '2025-2', 'fecha_inicio' => '2025-07-15', 'fecha_fin' => '2025-12-15']),
            AcademicTerm::create(['codigo' => '2026-1', 'fecha_inicio' => '2026-01-20', 'fecha_fin' => '2026-06-15']),
            AcademicTerm::create(['codigo' => '2026-2', 'fecha_inicio' => '2026-07-20', 'fecha_fin' => '2026-12-15']),
        ];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @param array<int, float> $papa PAPA por índice de periodo (0 = 2025-2 … 2 = 2026-2) @param array<int, int> $credits créditos cursados por periodo */
    private function student(array $papa, array $credits = [], string $estado = 'activo'): Student
    {
        $n = ++$this->seq;
        $user = User::factory()->create(['role' => Role::Estudiante, 'name' => "Estudiante {$n}"]);
        $student = Student::create(['user_id' => $user->id, 'codigo' => "2022{$n}", 'documento' => "100{$n}", 'cohorte' => '2022-1', 'estado' => $estado]);
        foreach ($papa as $i => $value) {
            AcademicRecord::create([
                'student_id' => $student->id, 'term_id' => $this->terms[$i]->id, 'papa' => $value,
                'creditos_cursados' => $credits[$i] ?? 40 + 20 * $i, 'porcentaje_avance' => 30,
            ]);
        }

        return $student;
    }

    private function types(Student $student): array
    {
        return Alert::where('student_id', $student->id)->pluck('tipo')->all();
    }

    public function test_a_papa_below_the_minimum_raises_an_alert_and_exactly_the_minimum_does_not(): void
    {
        $low = $this->student([2 => 2.9]);
        $edge = $this->student([2 => 3.0]);

        (new AlertRules)->run();

        $this->assertSame(['papa_bajo'], $this->types($low));
        $this->assertSame([], $this->types($edge), 'El mínimo es 3,0: tenerlo exacto no es estar por debajo.');
        $detail = Alert::where('student_id', $low->id)->value('detalle');
        $this->assertStringContainsString('2,9', $detail);
        $this->assertStringContainsString('3,0', $detail);
    }

    public function test_a_papa_drop_needs_a_previous_period_and_at_least_the_configured_tenths(): void
    {
        $drop = $this->student([1 => 3.8, 2 => 3.4]);
        $small = $this->student([1 => 3.8, 2 => 3.6]);
        $rise = $this->student([1 => 3.4, 2 => 3.9]);
        $first = $this->student([2 => 3.4]);

        (new AlertRules)->run();

        $this->assertSame(['caida_papa'], $this->types($drop));
        $this->assertSame([], $this->types($small));
        $this->assertSame([], $this->types($rise), 'Una subida no es una caída.');
        $this->assertSame([], $this->types($first), 'Sin periodo anterior no hay con qué comparar.');
        $this->assertStringContainsString('3,8', Alert::where('student_id', $drop->id)->value('detalle'));
    }

    public function test_an_enrollment_without_new_credits_raises_an_alert(): void
    {
        $stuck = $this->student([1 => 3.5, 2 => 3.5], [1 => 100, 2 => 100]);
        $moving = $this->student([1 => 3.5, 2 => 3.5], [1 => 100, 2 => 116]);
        $exchange = $this->student([1 => 3.5, 2 => 3.5], [1 => 100, 2 => 100], 'intercambio');

        (new AlertRules)->run();

        $this->assertSame(['matricula_sin_avance'], $this->types($stuck));
        $this->assertSame([], $this->types($moving));
        $this->assertSame([], $this->types($exchange), 'Quien está de intercambio no suma créditos aquí: no es una señal.');
    }

    public function test_the_same_request_type_filed_three_times_in_the_term_raises_an_alert(): void
    {
        $repeat = $this->student([2 => 3.5]);
        $two = $this->student([2 => 3.5]);
        $mixed = $this->student([2 => 3.5]);
        $cancel = RequestType::create(['nombre' => 'Cancelación de asignatura']);
        $reentry = RequestType::create(['nombre' => 'Reingreso al programa']);
        $file = fn (Student $s, RequestType $t, bool $deleted = false) => tap(CommitteeRequest::create([
            'student_id' => $s->id, 'request_type_id' => $t->id, 'term_id' => $this->terms[2]->id,
            'descripcion' => str_repeat('x', 60), 'estado' => 'radicada', 'fecha_radicacion' => now(),
        ]), fn ($r) => $deleted && $r->delete());
        foreach (range(1, 3) as $_) {
            $file($repeat, $cancel);
        }
        $file($two, $cancel);
        $file($two, $cancel);
        $file($two, $cancel, deleted: true);
        $file($mixed, $cancel);
        $file($mixed, $reentry);
        $file($mixed, $cancel);

        (new AlertRules)->run();

        $this->assertSame(['solicitudes_recurrentes'], $this->types($repeat));
        $this->assertSame([], $this->types($two), 'Las solicitudes eliminadas no cuentan.');
        $this->assertSame([], $this->types($mixed), 'Tres solicitudes, pero de tipos distintos.');
        $this->assertStringContainsString('Cancelación de asignatura', Alert::where('student_id', $repeat->id)->value('detalle'));
    }

    public function test_a_tutored_student_without_a_recent_tutorship_raises_an_alert(): void
    {
        $tutor = User::factory()->create(['role' => Role::Profesor]);
        $quiet = $this->student([2 => 3.5]);
        $recent = $this->student([2 => 3.5]);
        $untutored = $this->student([2 => 3.5]);
        foreach ([$quiet, $recent] as $s) {
            TutorAssignment::create(['student_id' => $s->id, 'tutor_id' => $tutor->id, 'term_id' => $this->terms[2]->id]);
        }
        Tutorship::create(['student_id' => $quiet->id, 'tutor_id' => $tutor->id, 'fecha' => '2026-02-01', 'motivo' => 'Revisión de avance']);
        Tutorship::create(['student_id' => $recent->id, 'tutor_id' => $tutor->id, 'fecha' => '2026-09-20', 'motivo' => 'Revisión de avance']);

        (new AlertRules)->run();

        $this->assertSame(['sin_tutoria'], $this->types($quiet));
        $this->assertSame([], $this->types($recent));
        $this->assertSame([], $this->types($untutored), 'Sin tutor asignado no hay a quién avisar.');
    }

    public function test_running_twice_never_duplicates_an_open_alert(): void
    {
        $low = $this->student([2 => 2.5]);

        $first = (new AlertRules)->run();
        $second = (new AlertRules)->run();

        $this->assertCount(1, $first);
        $this->assertSame([], $second);
        $this->assertSame(1, Alert::where('student_id', $low->id)->count());
    }

    public function test_an_attended_alert_is_not_raised_again_in_the_same_term_but_is_in_a_later_one(): void
    {
        $low = $this->student([2 => 2.5]);
        (new AlertRules)->run();
        Alert::where('student_id', $low->id)->update(['atendida' => true]);

        $this->assertSame([], (new AlertRules)->run(), 'Ya se atendió en este periodo: no se vuelve a avisar cada día.');

        Alert::where('student_id', $low->id)->update(['fecha_deteccion' => '2026-03-01']);
        $this->assertCount(1, (new AlertRules)->run(), 'Un periodo después, si el PAPA sigue bajo, se avisa de nuevo.');
        $this->assertSame(2, Alert::where('student_id', $low->id)->count());
    }

    public function test_graduated_students_and_a_dry_run_leave_no_alerts(): void
    {
        $graduated = $this->student([2 => 2.0], [], 'egresado');
        $low = $this->student([2 => 2.5]);

        $planned = (new AlertRules)->run(dryRun: true);

        $this->assertCount(1, $planned);
        $this->assertSame($low->id, $planned[0]['student_id']);
        $this->assertSame(0, Alert::count(), 'La simulación no guarda nada.');
        (new AlertRules)->run();
        $this->assertSame([], $this->types($graduated));
    }

    public function test_every_rule_can_be_switched_off_from_the_configuration(): void
    {
        $this->student([1 => 3.9, 2 => 2.5]);
        config(['program.alert_rules.papa_bajo.activa' => false, 'program.alert_rules.caida_papa.activa' => false]);

        $this->assertSame([], (new AlertRules)->run());
    }

    public function test_the_command_evaluates_reports_and_supports_a_dry_run(): void
    {
        $this->student([2 => 2.5]);

        $this->artisan('alertas:evaluar', ['--simular' => true])->expectsOutputToContain('1 alerta')->assertSuccessful();
        $this->assertSame(0, Alert::count());

        $this->artisan('alertas:evaluar')->expectsOutputToContain('1 alerta')->assertSuccessful();
        $this->assertSame(1, Alert::count());
        $this->artisan('alertas:evaluar')->expectsOutputToContain('Ninguna alerta nueva')->assertSuccessful();
    }

    public function test_the_task_is_scheduled_every_day(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($e) => str_contains($e->command, 'alertas:evaluar'));

        $this->assertCount(1, $events);
        $this->assertSame('0 6 * * *', $events->first()->expression);
    }

    public function test_coordination_and_the_students_tutor_can_mark_an_alert_attended_and_it_is_audited(): void
    {
        $this->seed(DatabaseSeeder::class);
        $alert = Alert::where('atendida', false)->firstOrFail();

        $coordination = $this->signInSeeded(Role::Coordinacion);
        $this->post(route('alerts.attend', $alert))->assertRedirect()->assertSessionHas('status');

        $this->assertTrue($alert->fresh()->atendida);
        $this->assertSame(1, AuditLog::where('auditable_type', (new Alert)->getMorphClass())->where('auditable_id', $alert->id)->where('user_id', $coordination->id)->count());

        $other = Alert::where('atendida', false)->firstOrFail();
        $tutor = User::find(TutorAssignment::where('student_id', $other->student_id)->value('tutor_id'));
        $this->actingAs($tutor)->post(route('alerts.attend', $other))->assertRedirect();
        $this->assertTrue($other->fresh()->atendida);
    }

    public function test_anyone_else_is_refused(): void
    {
        $this->seed(DatabaseSeeder::class);
        $alert = Alert::where('atendida', false)->firstOrFail();

        $this->signInSeeded(Role::Estudiante);
        $this->post(route('alerts.attend', $alert))->assertForbidden();

        $linked = TutorAssignment::where('student_id', $alert->student_id)->value('tutor_id');
        $stranger = User::where('role', Role::Profesor)->where('id', '!=', $linked)->firstOrFail();
        $this->actingAs($stranger)->post(route('alerts.attend', $alert))->assertForbidden();
        $this->assertFalse($alert->fresh()->atendida);

        auth()->logout();
        $this->post(route('alerts.attend', $alert))->assertRedirect('/login');
    }

    public function test_the_attention_list_offers_the_action_only_for_open_alerts_and_no_longer_claims_rules_that_did_not_exist(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $html = $this->get('/panel')->getContent();

        $this->assertSame(Alert::where('atendida', false)->count(), substr_count($html, 'Marcar atendida'));
        $this->assertStringContainsString('se evalúan cada día', $html);
        $this->assertStringNotContainsString('Detectadas por las reglas del programa', $html);
    }
}
