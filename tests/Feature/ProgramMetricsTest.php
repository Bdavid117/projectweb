<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\TutorAssignment;
use App\Models\User;
use App\Support\ProgramMetrics;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_panel_headline_is_computed_from_the_current_term(): void
    {
        $this->seed(DatabaseSeeder::class);

        $headline = (new ProgramMetrics)->panel()['headline'];

        $this->assertFalse($headline['scoped']);
        $this->assertSame(6, $headline['active']);
        $this->assertSame('2026-2', $headline['term']);
        $this->assertSame('3,7', $headline['papa']);
        $this->assertSame(-0.3, $headline['papaDelta']);
        $this->assertSame(44, $headline['advance']);
        $this->assertSame(3, $headline['open']);
    }

    public function test_the_headline_of_a_professor_only_counts_their_tutored_students(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tutor = User::where('email', 'profesor2@unal.edu.co')->first() ?? User::where('role', 'profesor')->first();

        $headline = (new ProgramMetrics)->panel($tutor)['headline'];

        $this->assertTrue($headline['scoped']);
        $this->assertLessThanOrEqual(6, $headline['active']);
    }

    public function test_attention_rows_list_alerts_newest_first_with_status_and_link(): void
    {
        $this->seed(DatabaseSeeder::class);

        $rows = (new ProgramMetrics)->panel()['attentionRows'];

        $this->assertCount(4, $rows);
        $this->assertSame('Pérez Rojas, Juan Sebastián', $rows[0]['name']);
        $this->assertSame('Sin atender', $rows[0]['status']);
        $this->assertSame('danger', $rows[0]['tone']);
        $this->assertSame('Martínez Cruz, Diana', $rows[3]['name']);
        $this->assertSame('Atendida', $rows[3]['status']);
        $this->assertStringContainsString('/estudiantes/', $rows[0]['url']);
    }

    public function test_every_dot_knows_how_far_its_cohort_should_be_and_who_lags(): void
    {
        $this->seed(DatabaseSeeder::class);

        $map = (new ProgramMetrics)->panel()['cohortMap'];
        $dots = collect($map['rows'])->pluck('dots')->flatten(1)->keyBy('code');

        // 10 semestres de plan (supuesto en config) y periodo 2026-2: quien ingresó en 2019-2 ya debería ir al 100 %, quien ingresó en 2023-1, al 70 %.
        $this->assertSame(100, $dots['2019087']['expected']);
        $this->assertSame(70, $dots['2023019']['expected']);
        $this->assertSame(60, $dots['2023044']['expected']);
        $this->assertFalse($dots['2019087']['behind'], 'A 12 puntos de lo esperado todavía está dentro del margen de 15.');
        $this->assertTrue($dots['2023019']['behind']);
        $this->assertContains('rezagado', $dots['2023019']['tags']);
        $this->assertFalse($dots['2023044']['behind'], 'Quien está de intercambio no se mide contra el plan.');
        $this->assertSame(['2019' => 100, '2023' => 65], collect($map['rows'])->pluck('expected', 'label')->only(['2019', '2023'])->all(), 'La fila usa el promedio de sus dos cohortes.');
        $this->assertSame(collect($dots)->filter(fn ($d) => $d['behind'])->count(), collect($map['filters'])->firstWhere('key', 'rezagado')['count']);
    }

    public function test_dots_carry_the_previous_period_and_the_last_tutorship(): void
    {
        $this->seed(DatabaseSeeder::class);

        $dots = collect((new ProgramMetrics)->panel()['cohortMap']['rows'])->pluck('dots')->flatten(1)->keyBy('code');

        $this->assertSame(63, $dots['2020123']['prevPct'], 'El registro anterior de Juan es 2023-1, al 63 % del plan.');
        $this->assertNull($dots['2019087']['prevPct'], 'Sin periodo anterior no hay flecha.');
        $this->assertSame('12/09/2026', $dots['2020123']['lastTutorship']);
        $this->assertNull($dots['2019087']['lastTutorship']);
    }

    public function test_sin_tutoria_only_flags_tutored_students_whose_last_tutorship_is_older_than_the_threshold(): void
    {
        $this->seed(DatabaseSeeder::class);

        $map = (new ProgramMetrics)->panel()['cohortMap'];
        $quiet = collect($map['rows'])->pluck('dots')->flatten(1)->filter(fn ($d) => in_array('sin-tutoria', $d['tags']))->pluck('code')->sort()->values()->all();

        // 2021098 (última tutoría 20/05, 145 días) y 2021045 (03/07, 101 días). Los demás son recientes o no tienen tutor asignado.
        $this->assertSame(['2021045', '2021098'], $quiet);
        $this->assertSame(2, collect($map['filters'])->firstWhere('key', 'sin-tutoria')['count']);
    }

    public function test_the_attention_list_carries_the_tags_that_let_it_follow_the_map_filter(): void
    {
        $this->seed(DatabaseSeeder::class);

        $rows = collect((new ProgramMetrics)->panel()['attentionRows']);

        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertIsArray($row['tags']);
        }
        $this->assertContains('con-alerta', $rows->firstWhere('name', 'Pérez Rojas, Juan Sebastián')['tags']);
    }

    public function test_each_map_dot_carries_what_the_card_and_the_link_need(): void
    {
        $this->seed(DatabaseSeeder::class);

        $dots = collect((new ProgramMetrics)->panel()['cohortMap']['rows'])->pluck('dots')->flatten(1);
        $ruiz = $dots->firstWhere('code', '2019087');

        $this->assertNotNull($ruiz);
        $this->assertSame('Ruiz Salazar, Andrés', $ruiz['name']);
        $this->assertSame('2019-2', $ruiz['cohort']);
        $this->assertSame(88, $ruiz['pct']);
        $this->assertSame('3,2', $ruiz['papa'], 'Decimales con coma, como en el resto de la interfaz.');
        $this->assertFalse($ruiz['papaLow'], 'El umbral es 3,0: un 3,2 no es bajo.');
        $cardona = $dots->firstWhere('code', '2021098');
        $this->assertSame('2,9', $cardona['papa']);
        $this->assertTrue($cardona['papaLow']);
        $this->assertContains('en-riesgo', $cardona['tags']);
        $this->assertSame('Activo', $ruiz['stateLabel']);
        $this->assertContains('activo', $ruiz['tags']);
        $this->assertContains('con-alerta', $ruiz['tags']);
        $this->assertSame(1, $ruiz['alerts']);
        $this->assertStringEndsWith('/estudiantes/'.$ruiz['id'], $ruiz['url']);
        $this->assertStringContainsString('% del plan', $ruiz['label']);
    }

    public function test_the_map_offers_one_filter_per_kind_of_student_with_its_count(): void
    {
        $this->seed(DatabaseSeeder::class);

        $map = (new ProgramMetrics)->panel()['cohortMap'];
        $filters = collect($map['filters'])->pluck('count', 'key');

        $this->assertSame(8, $map['total']);
        $this->assertSame('todos', $map['filters'][0]['key'], 'Todos va primero.');
        $this->assertSame(8, $filters['todos']);
        $this->assertSame(6, $filters['activo']);
        $this->assertSame(1, $filters['en-riesgo']);
        $this->assertSame(1, $filters['intercambio']);
        $this->assertSame(Alert::where('atendida', false)->distinct()->count('student_id'), $filters['con-alerta']);
        $this->assertSame(1, $filters['en-practica']);
        $this->assertSame(1, $filters['trabajo-de-grado']);
        $this->assertFalse($filters->has('egresado'), 'Un tipo sin estudiantes no ocupa un botón.');
        foreach ($map['filters'] as $filter) {
            $this->assertNotSame('', $filter['label']);
        }
    }

    public function test_a_professor_only_gets_dots_and_counts_for_their_own_tutees(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tutor = $this->signInSeededTutor();

        $map = (new ProgramMetrics)->panel($tutor)['cohortMap'];
        $mine = TutorAssignment::studentIdsOf($tutor);
        $dots = collect($map['rows'])->pluck('dots')->flatten(1);

        $this->assertNotEmpty($mine);
        $this->assertEqualsCanonicalizing($mine, $dots->pluck('id')->all());
        $this->assertSame(count($mine), $map['total']);
        $this->assertSame(count($mine), collect($map['filters'])->firstWhere('key', 'todos')['count']);
    }

    public function test_the_map_places_one_dot_per_student_in_the_row_of_their_cohort(): void
    {
        $this->seed(DatabaseSeeder::class);

        $map = (new ProgramMetrics)->panel()['cohortMap'];
        $rows = collect($map['rows']);

        $this->assertSame(['2019', '2020', '2021', '2022', '2023'], $rows->pluck('label')->all());
        $this->assertSame(8, $rows->sum(fn ($row) => count($row['dots'])), 'Un punto por cada estudiante con registro en el periodo, también en riesgo o en intercambio (la leyenda no dice «activo»).');

        foreach ($rows->pluck('dots')->flatten(1) as $dot) {
            $this->assertGreaterThanOrEqual(0, $dot['pct']);
            $this->assertLessThanOrEqual(100, $dot['pct']);
            $this->assertStringContainsString('% del plan', $dot['label']);
        }
        $this->assertSame(88, $rows->firstWhere('label', '2019')['dots'][0]['pct']);
        $this->assertNotNull($map['reading']);
    }

    public function test_recent_activity_mixes_tutorships_requests_and_validated_experiences(): void
    {
        $this->seed(DatabaseSeeder::class);

        $activity = (new ProgramMetrics)->panel()['recentActivity'];

        $this->assertCount(5, $activity);
        foreach ($activity as $item) {
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('meta', $item);
        }
    }

    public function test_indicator_screen_metrics(): void
    {
        $this->seed(DatabaseSeeder::class);

        $data = (new ProgramMetrics)->indicators();

        $this->assertSame(['active' => 6, 'internships' => 1, 'theses' => 1, 'filed' => 2, 'pending' => 2], $data['headline']);

        $histogram = collect($data['papaHistogram']);
        $this->assertSame(['Menos de 3,0', '3,0 a 3,3', '3,3 a 3,6', '3,6 a 4,0', '4,0 a 4,3', 'Más de 4,3'], $histogram->pluck('range')->all());
        $this->assertSame([1, 1, 1, 2, 2, 1], $histogram->pluck('count')->all());
        $this->assertSame(1, $data['papaUnit']);

        $participation = collect($data['participation']);
        $this->assertCount(4, $participation);
        $this->assertSame('1 · 17%', $participation->first()['value']);
        $this->assertSame(1, $participation->first()['count']);

        $byType = collect($data['requestsByType'])->keyBy('type');
        $this->assertSame(['filed' => '1', 'review' => '0', 'decided' => '0', 'avgTime' => '—'], collect($byType['Cancelación de asignatura'])->only(['filed', 'review', 'decided', 'avgTime'])->all());
        $this->assertSame('1', $byType['Reingreso al programa']['review']);
        $this->assertSame('0', $byType['Otras']['filed']);
    }

    public function test_dots_are_scaled_so_the_longest_row_never_exceeds_the_cap(): void
    {
        $unit = new \ReflectionMethod(ProgramMetrics::class, 'dotUnit');
        $unit->setAccessible(true);
        $metrics = new ProgramMetrics;

        $this->assertSame(1, $unit->invoke($metrics, [0, 5, 24]));
        $this->assertSame(2, $unit->invoke($metrics, [25]));
        $this->assertSame(5, $unit->invoke($metrics, [120]));
        $this->assertSame(1, $unit->invoke($metrics, []));
    }

    public function test_an_empty_database_yields_zeroes_and_dashes_without_errors(): void
    {
        $metrics = new ProgramMetrics;

        $panel = $metrics->panel();
        $this->assertSame(0, $panel['headline']['active']);
        $this->assertNull($panel['headline']['papa']);
        $this->assertNull($panel['headline']['advance']);
        $this->assertNull($panel['headline']['papaDelta']);
        $this->assertSame(0, $panel['headline']['open']);
        $this->assertSame([], $panel['attentionRows']);
        $this->assertSame(['rows' => [], 'reading' => null, 'filters' => [], 'total' => 0], $panel['cohortMap']);
        $this->assertSame([], $panel['recentActivity']);

        $data = $metrics->indicators();
        $this->assertCount(6, $data['papaHistogram']);
        $this->assertSame([0, 0, 0, 0, 0, 0], collect($data['papaHistogram'])->pluck('count')->all());
        $this->assertSame(1, $data['papaUnit']);
        $this->assertSame([], $data['participation']);
        $this->assertSame([], $data['requestsByType']);
    }
}
